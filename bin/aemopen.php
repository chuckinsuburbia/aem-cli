#!/usr/bin/php
<?php
# Example command line
# aemopen.php source=SNMP object="blah" objectClass="blah"
$basepath=getenv("AEMBASE");
require_once($basepath."/conf/config.php");
require_once($basepath."/lib/functions.php");
require_once($basepath.'/lib/CronParser.php');

array_shift($argv);
//$source=array_shift($argv);
if($debug) aemlog("Received Alert from ".$tokens['source']);

# read tokens from command line
foreach($argv as $arg){
	$argA=split('=',$arg);
	$tokens[$argA[0]] = trim($argA[1]);
	if($debug) aemlog($argA[0]." = ".$argA[1]);
}
//$tokens['source']=$source;

$alertId=createAlert($tokens);
if($debug) aemlog("Alert $alertId created");

#get the path for this source
$sPath=getSourcePath($tokens['source']);

#process each step for this path
foreach($sPath as $step){
	$stepRc=runStep($alertId,$step);
	if(!$stepRc){
		aemlog("Source Step Failed, aborting!");
		die();
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

?>
