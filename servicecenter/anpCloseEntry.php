#!/usr/bin/php
<?php

function sclog($logfile,$string) {
 $handle = fopen($logfile, 'a');
 $d=date('r');
 fwrite($handle,$d." - ".$string."\n");
 fclose($handle);
}

$basepath = getenv("AEMBASE");
require_once($basepath.'/lib/nusoap/lib/nusoap.php');
require_once($basepath.'/conf/config.php');

$logfile = $basepath."/logs/anpCloseEntry.log";
sclog($logfile,implode(" ",$argv));

$MAP[1] = "aemid";            //AEM Incident ID
$MAP[2] = "number";           //SC Incident ID

foreach ($MAP as $k => $v)
 {
  if (isset($argv[$k]))
   {
    $arguments[$v] = trim(preg_replace('/\s+/', ' ',$argv[$k]));
   }
  else
   {
    die("Error: missing expected parameter ".$v."\n");
   }
 }

#print_r($arguments);



$err = $sc_client->getError();
if ($err) {
	die( '<h2>Constructor error</h2><pre>' . $err . '</pre>');
}

// Doc/lit parameters get wrapped
$IncidentID = new soapval("IncidentID","StringType",$arguments['number'],"http://servicecenter.peregrine.com/PWS/Common");
$keys = new soapval('keys','IncidentKeysType',array($IncidentID),"http://servicecenter.peregrine.com/PWS");
$resupdate = new soapval("Resolution","StringType","AutoClose by AEM at ".date("Y-m-d h:i:s"),"http://servicecenter.peregrine.com/PWS/Common");
$Resolution = new soapval("Resolution","ArrayType",array($resupdate),"http://servicecenter.peregrine.com/PWS/Common");
$closeupdate = new soapval("ClosingComments","StringType","AutoClose by AEM at ".date("Y-m-d h:i:s"),"http://servicecenter.peregrine.com/PWS/Common");
$ClosingComments = new soapval("ClosingComments","ArrayType",array($closeupdate),"http://servicecenter.peregrine.com/PWS/Common");
$update = new soapval("JournalUpdates","StringType","AutoClose by AEM at ".date("Y-m-d h:i:s"),"http://servicecenter.peregrine.com/PWS/Common");
$JournalUpdates = new soapval("JournalUpdates","ArrayType",array($update),"http://servicecenter.peregrine.com/PWS/Common");
$ClosureCode = new soapval("ClosureCode","StringType","AUTOCLOSE","http://servicecenter.peregrine.com/PWS/Common");
$ResolutionFixType = new soapval("ResolutionFixType","StringType","permanent","http://servicecenter.peregrine.com/PWS/Common");
$instance = new soapval("instance","IncidentInstanceType",array($Resolution,$ResolutionFixType,$ClosingComments,$ClosureCode,$JournalUpdates),"http://servicecenter.peregrine.com/PWS");
$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");	
$UpdateIncidentRequest = new soapval("UpdateIncidentRequest","UpdateIncidentRequestType",$model,"http://servicecenter.peregrine.com/PWS");
$CloseIncidentRequest = new soapval("CloseIncidentRequest","CloseIncidentRequestType",$model,"http://servicecenter.peregrine.com/PWS");
$sc_client->setCredentials('pem', 'Pemspassword');
$result = $sc_client->call('CloseIncident',$CloseIncidentRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//, 
// Check for a fault
if ($sc_client->fault) {
	echo '<h2>Update Fault</h2><pre>';
	print_r($result);
	echo '</pre>';
} else {
	// Check for errors
	$err = $sc_client->getError();
	if ($err) {
		// Display the error
		echo '<h2>Create Error</h2><pre>' . $err . '</pre>';
	} else {
		// Display the result
		#echo '<h2>Create Result</h2><pre>';
		#print_r($result);
		echo($result['!status']);
	#	#echo '</pre>';
	}
}
?>
