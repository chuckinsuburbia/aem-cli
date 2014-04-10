#!/usr/bin/php
<?php
//Set environment
date_default_timezone_set('America/New_York');
$aembase = getenv('AEMBASE');
require_once $aembase.'/conf/config.php';
require_once $aembase.'/lib/Mbox.php';
require_once $aembase.'/lib/mimeDecode.php';

$logfile = $aembase."/logs/emailHandler.log";

$mboxPath = getenv('MAIL');

$decodeParams['include_bodies'] = true;
$decodeParams['decode_bodies']  = true;
$decodeParams['decode_headers'] = true;


//Open log file
function logmsg($msg) {
        global $logfile;
        file_put_contents($logfile,date('Ymd H:i:s').": ".$msg."\n",FILE_APPEND);
}

/******************************************************************************\
		function to process individual mail messages
\******************************************************************************/
function msgProcess($structure) {
	$body = isset($structure->body) ? $structure->body : $structure->parts[0]->body;

	switch(true) {
		case (strstr(strtolower($body),"testtesttesttest")):
			logmsg("Received Test Message");
			require_once "functionsTest.php";
			testProcess($structure);
			return;
		case (strstr(strtolower($structure->headers['from']),"datanex@aptea.com")):
			logmsg("Received Data Nexus message");
			require_once "functionsNexus.php";
			nexusProcess($structure);
			return;
		case (strstr(strtolower($structure->headers['from']),"einvoices@aptea.com")):
			logmsg("Received E-Invoice message");
			require_once "functionsEinvoices.php";
			einvoiceProcess($structure);
			return;
		case (strstr(strtolower($body),"from: emergency alert")):
			logmsg("Received ELert");
			require_once "functionsElert.php";
			elertProcess($body);
			return;	
		case (strstr(strtolower($body),"subject: t-log store transactions")):
			logmsg("Received T-Log Store Transactions");
			require_once "functionsTlog.php";
			tlogProcess($body);
			return;
		case (strstr(strtolower($body),"from: narrowcast administrator")):
			logmsg("Received Narrowcast Report");
			require_once "functionsRDW.php";
			narrowProcess();
			return;
		case (strstr($body,"Subject: EMS Alarm")):
			logmsg("Received EMS Alarm");
			require_once "functionsDanfoss.php";
			$newbody=emsAlarmProcess($body,$structure->headers['subject']);
			$body = $newbody;
			break;
		case (strstr(strtolower($body),"subject: device manager device error")):
			logmsg("Received Kronos Device Error");
			require_once "functionsKronos.php";
			$newbody=kronosClockProcess($body);
			if ($newbody == "") return;
			$body = $newbody;
			break;
		case (strstr(strtolower($body),"subject: alarmpoint message")):
			logmsg("Received AlarmPoint Message");
			//alarmpointProcess($structure);
			break;
		case (strstr(strtolower($body),"from: qflex@")):
			logmsg("Received QFlex Message");
			require_once "functionsQflex.php";
			$newbody=qflexProcess($body);
			$body=$newbody;
			break;
		case (strstr(strtolower($body),"from: rdbf@")):
		case (strstr(strtolower($body),"subject: isp tbstat")):
			logmsg("Received TBStat Message");
			//require_once "functionsIsp.php";
			//tbstatProcess($body,$structure->headers['from'],$structure->headers['subject']);
			//return;
			break;
		case (strstr(strtolower($body),"subject: ctm_condition")):
			logmsg("Received CTM_CONDITION");
			//ctmCondProcess($structure);
			break;
		case (strstr(strtolower($body),"subject: ctm_order")):
			logmsg("Received CTM_ORDER");
			//ctmOrderProcess($structure);
			break;
		case (strstr(strtolower($body),"from: storage_manager")):
		case (strstr(strtolower($body),"from: storage_mgr_")):
			logmsg("Received SAN Storage Manager message");
			require_once "functionsSan.php";
			$newbody=sanErrorProcess($body);
			$body=$newbody;
			break;
		case (strstr(strtolower($body),"from: google voice")):
			logmsg("Received Google Voice message");
			require_once "functionsGoogle.php";
			googleVoiceProcess();
			return;
		case (strstr(strtolower($body),"subject: expired plum batches found")):
			logmsg("Received Expired PLUM Batches");
			if (date('H') < 9) {
				logmsg("Prior to 9am, emailing store");
				require_once("functionsIsp.php");
				plumProcess($structure->headers['subject']);
				return;
			} else {
				$body.=",O=PLUM";
				logmsg("Passing alert to AEM");
			}
			break;
		case (strstr(strtolower($body),"subject: consecutive backup failures (isp")):
			logmsg("Received consecutive backup failures message");
			require_once("functionsIsp.php");
			$newbody=backupFailureProcess($body);
			$body=$newbody;
                        break;
	}

	//Parse Email body into tokens
	global $fieldMap, $fieldPem, $defaults;
	$validAlert=0;
	foreach(explode("\n",$body) as $line) {
		if(!preg_match("/=/",$line)) continue;
		foreach(explode(",",$line) as $pair) {
			if(strstr($pair,"=")) {
				list($k,$v) = explode("=",$pair);
				$pairs[trim($k)]=trim($v);
			}
		}
		if($debug) logmsg(print_r($pairs,TRUE));
		foreach($fieldMap as $k => $v) {
			if(isset($pairs[$k])) {
				$tokens[$k] = $pairs[$k];
				continue;
			}
			if(isset($pairs[$v])) {
				$tokens[$k] = $pairs[$v];
				continue;
			}
			if(isset($fieldPem[$k])) {
				$p = $fieldPem[$k];
				if(isset($pairs[$p])) {
					$tokens[$k] = $pairs[$p];
					continue;
				}
			}
			if(isset($defaults[$k])) {
				$tokens[$k] = $defaults[$k];
				continue;
			}
			$tokens[$k] = "";
		}
		$tokens['source'] = "email";
		$tokens['enterprise'] = "1031";
		if($debug) logmsg(print_r($tokens,TRUE));

		//If valid Alert, create XML output from tokens
		if($tokens['origin'] != ""  && $tokens['objectClass'] != "") {
			$validAlert++;
			global $xml;
			if (!isset($xml)) {
				$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
				$xml[] = '<alerts>';
			}
			$xml[] = '<alert>';
			foreach($tokens as $k => $v) {
				$xml[] = "\t<".$k.">".htmlspecialchars($v)."</".$k.">";
			}
			$xml[] = '</alert>';
		}
	}
	//If message contained no valid alerts, forward to other mailbox
	if ($validAlert == 0) {
		global $fwdInvalid;
		logmsg("Received message with invalid structure.  Forwarding to ".$fwdInvalid);
		$fwdBody = "";
		foreach ($structure->headers as $k => $v) {
			$fwdBody .= $k.": ".print_r($v,TRUE)."\n";
		}
		$fwdBody .= "\n\n".$body;
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/plain; charset=iso-8859-1' . "\r\n";

		mail($fwdInvalid,"Unknown Message Structure: ".$structure->headers['subject'],$fwdBody,$headers);
	}
}

/******************************************************************************\
                Begin Processing
\******************************************************************************/

//Open mailbox file & fetch messages
logmsg("Opening mailbox ".$mboxPath);
$mbox = new Mail_Mbox($mboxPath);
$mbox->open() or logmsg("Unable to open mailbox ".$mboxPath) && die;
while ($mbox->size() > 0) {
	$message = $mbox->get(0);

	//MIME decode the message text
	$decoder = new Mail_mimeDecode($message);
	$structure = $decoder->decode($decodeParams);
	
	//Pass the message to msgProcess function
	msgProcess($structure);

	//remove message from mailbox
	$mbox->remove(0);
}
$mbox->close();

//Create xml spool file
if (isset($xml)) {
	$xml[] = '</alerts>';
	logmsg("Creating XML file.");

	//Output file - in a loop to ensure unique file name
	do {
		$outfile = $basePath."/spool/mail_".date('YmdHis')."_".rand().".xml";
	} while (file_exists($outfile));

	foreach($xml as $l) {
		file_put_contents($outfile,$l."\n",FILE_APPEND);
	}
}

logmsg("Processing Complete");

?>
