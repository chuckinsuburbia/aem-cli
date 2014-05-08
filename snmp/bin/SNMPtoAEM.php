#!/usr/bin/php
<?php

//Load environment
//$basePath=getenv("AEMBASE");
$basePath="/in/AEM";
require_once($basePath."/snmp/conf/config.php");

//Logging function
function logmsg($string){
        global $snmplog;
        file_put_contents($snmplog,date("Y-m-d H:i:s")." - ".$string."\n",FILE_APPEND);
}
function logerr($string){
        global $faillog;
        file_put_contents($faillog,date("Y-m-d H:i:s")." - ".$string."\n",FILE_APPEND);
}

function mailAlert($subj,$body) {
	$to="collishc@aptea.com";
	mail($to,$subj,$body);
}


//Begin Processing
logmsg("Begin Processing...");

//Output file name
do {
	$outfile = $basePath."/spool/snmp_".date('YmdHis')."_".rand().".xml";
} while (file_exists($outfile));

//Get input from STDIN
$input = file_get_contents("php://stdin");
$lines = explode("\n",$input);

if($debug) logmsg("\n".print_r($lines,TRUE));

//Source Host is the first line of SNMP trap
$SNMPsourceHost = array_shift($lines);
logmsg("source host = ".$SNMPsourceHost);
$tokens['eventType'] = $SNMPsourceHost;

//take off next 3 lines
//array_shift($lines);array_shift($lines);array_shift($lines);

//Loop through remaining lines and fetch valid messages into array 
foreach($lines as $line){
	switch (true) {
		case preg_match('/^SNMPv2-SMI::enterprises./',$line):
			$firstSpace = strpos($line," ");
			$oidStart = strpos($line,"enterprises.")+12;
			if($oidStart>12 && $firstSpace > $oidStart){
				$oid = substr($line,$oidStart,$firstSpace-$oidStart);
				$value = substr($line,$firstSpace+1);
				$oidValues[$oid] = $value;
			}
			break;
		default:
			break;
	}

}
if(!isset($oidValues)) {
	logmsg("ERROR: No valid tokens found");
	logerr("Invalid SNMP:\n\n".$input);
	die;
}

//Loop through array of messages and look up SNMP field mapping in database
foreach($oidValues as $key=>$val){
	if($debug) logmsg($key." = ".$val);
	$sql = "select at_name from aem_snmp_mapping, aem_tokens, aem_snmp_objects where at_id = asm_token and asm_object = aso_id and aso_oid = '".$key."'";
	$result = mysql_query($sql,$aem) or file_put_contents($snmplog,"SNMPtoAEM - getTokenMapping: ".mysql_error(),FILE_APPEND);
	if(mysql_num_rows($result) > 0) {
		$tokens[mysql_result($result,0,0)] = str_replace('"',"",$val);
	} else {
		$unmapped[] = $key." = ".$val;
	}
}
$tokens['source'] = "SNMP";
$tokens['enterprise'] = substr($key,0,strpos($key, "."));

if($debug) logmsg("TOKENS = \n".print_r($tokens,TRUE));
if(isset($unmapped)) {
        logmsg("ERROR: SNMP mapping not found for a passed line");
        logerr("Invalid SNMP\n\n".$input."\n\n".print_r($unmapped,TRUE));
}


//Create XML output from tokens
$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
$xml[] = '<alerts>';
$xml[] = '<alert>';
foreach($tokens as $k => $v) {
	$xml[] = "\t<".$k.'>'.htmlspecialchars(trim($v)).'</'.$k.'>';
}
$xml[] = '</alert>';
$xml[] = '</alerts>';
if($debug) logmsg("XML = \n".print_r($xml,TRUE));
foreach($xml as $l) file_put_contents($outfile,$l."\n",FILE_APPEND);

?>
