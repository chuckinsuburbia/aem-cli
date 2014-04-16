#!/usr/bin/php
<?php
$basepath=getenv("AEMBASE");
include($basepath."/conf/config.php");
include($basepath."/lib/functions.php");
include($basepath.'/lib/CronParser.php');

$spooldir = $basepath."/spool";
$files = scandir($spooldir);
foreach($files as $file) {
	$file = $spooldir."/".$file;
	if($file == "." || $file == ".." || !preg_match("/\.xml$/",$file)) continue;
	$rmfile = true;
	$alerts = simplexml_load_file($file);
	if(is_object($alerts)) {
		foreach($alerts->children() as $alert) {
			$tokens = get_object_vars($alert);
			if($debug) aemlog("Received Alert from ".$tokens['source']);

			$alertId=createAlert($tokens);
			if($debug) aemlog("Alert $alertId created");

			#get the path for this source
			$sPath=getSourcePath($tokens['source']);

			#process each step for this path
			foreach($sPath as $step){
				$stepRc=runStep($alertId,$step);
				if(!$stepRc){
					aemlog("Source Step Failed, aborting!");
//					die();
					$rmfile = false;
				}
			}

			#check if ticket is blacked out if not set status to open
			$returnAlert = processAlert($alertId);

			if($returnAlert != false){
				$destType = $returnAlert['type'];
				$alertId = $returnAlert['alertId'];

				$dPath=getDestPath($tokens['source'],$destType);
				print_r($dPath);
				if($debug) aemlog("running outbound steps");
				#process each step for this path
				foreach($dPath as $step){
					$stepRc=runStep($alertId,$step);
					if(!$stepRc){
						aemlog("Destination Step Failed!");
					}
				}
			}
			if($debug) aemlog("End of Alert Processing for $alertId");
		}
	}
	if($rmfile != false) unlink($file);
}

?>
