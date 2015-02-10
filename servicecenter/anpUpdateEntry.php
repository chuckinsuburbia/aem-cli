#!/usr/bin/php
<?php

function sclog($logfile,$string) {
 $handle = fopen($logfile, 'a');
 $d=date('r');
 fwrite($handle,$d." - ".$string."\n");
 fclose($handle);
}

$basepath=getenv("AEMBASE");
require_once($basepath.'/lib/nusoap/lib/nusoap.php');
require_once($basepath.'/conf/config.php');

$logfile = $basepath."/logs/sc_anpUpdateEntry.log";
sclog($logfile,implode(" ",$argv));

$MAP[1] = "aemid";            //AEM Incident ID
$MAP[2] = "number";           //SC Incident ID
$MAP[3] = "update.action";    //comment

foreach ($MAP as $k => $v)
 {
  if (isset($argv[$k]))
   {
    $arguments[$v] = trim($argv[$k]);
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
$sc_client->setCredentials('pem', 'Pemspassword');

// Doc/lit parameters get wrapped
$IncidentID = new soapval("IncidentID","StringType",$arguments['number'],"http://servicecenter.peregrine.com/PWS/Common");
$keys = new soapval('keys','IncidentKeysType',array($IncidentID),"http://servicecenter.peregrine.com/PWS");
$instance = new soapval("instance","IncidentInstanceType",array(),"http://servicecenter.peregrine.com/PWS");

$RetrieveModel = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");
$RetrieveIncidentRequest = new soapval("RetrieveIncidentRequest","RetrieveIncidentRequestType",$RetrieveModel,"http://servicecenter.peregrine.com/PWS");
$incident = $sc_client->call('RetrieveIncident',$RetrieveIncidentRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//,
// Check for a fault
if ($sc_client->fault) {
        echo '<h2>Retreive Fault</h2><pre>';
        print_r($incident);
        echo '</pre>';
        die();
} else {
        // Check for errors
        $err = $sc_client->getError();
        if ($err) {
                // Display the error
                echo '<h2>Retreive Error</h2><pre>' . $err . '</pre>';
                die();
        }
}
#print_r($incident);

if(isset($incident['model']['instance']['JournalUpdates'])) {
	foreach($incident['model']['instance']['JournalUpdates'] as $scdesc) {
		$arguments['update.action'] = str_replace($scdesc,'',$arguments['update.action']);
	}
}
$arguments['update.action'] = preg_replace('/\s\s+/', "\n",$arguments['update.action']);

$JournalUpdates = new soapval("JournalUpdates","ArrayType",$arguments['update.action'],"http://servicecenter.peregrine.com/PWS/Common");
$instance = new soapval("instance","IncidentInstanceType",array($JournalUpdates),"http://servicecenter.peregrine.com/PWS");
$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");	
$UpdateIncidentRequest = new soapval("UpdateIncidentRequest","UpdateIncidentRequestType",$model,"http://servicecenter.peregrine.com/PWS");
#print_r($UpdateIncidentRequest);
$result = $sc_client->call('UpdateIncident',$UpdateIncidentRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//, 

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
		echo '<h2>Update Error</h2><pre>' . $err . '</pre>';
	} else {
		// Display the result
	#	echo '<h2>Create Result</h2><pre>';
	#	print_r($result);
		echo($result['!status']);
	#	echo '</pre>';
	}
}
?>
