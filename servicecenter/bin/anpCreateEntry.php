#!/usr/bin/php
<?php

//Set time zone so date() doesn't throw errors.
date_default_timezone_set('America/New_York');

function sclog($logfile,$string) {
 $handle = fopen($logfile, 'a');
 $d=date('r');
 fwrite($handle,$d." - ".$string."\n");
 fclose($handle);
}

$pwd = dirname($_SERVER['PHP_SELF']);

$logfile = $pwd."/../logs/anpCreateEntry.log";
sclog($logfile,implode(" ",$argv));

$MAP[1] = "alertId";           //AEM Incident ID
$MAP[2] = "logical.name";      //source
$MAP[3] = "network.name";      //domain
$MAP[4] = "action,1";          //text
$MAP[5] = "type";              //aem_severity
$MAP[6] = "contact.name";      //service
$MAP[7] = "objid";             //objectClass
$MAP[8] = "location";          //origin
$MAP[9] = "failing.component"; //object

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

//Generate priority code from severity
switch ($arguments['type'])
 {
  case "Critical":
   $arguments['priority.code'] = 1;
   break;
  case "Warning":
   $arguments['priority.code'] = 2;
   break;
  default:
   $arguments['priority.code'] = 3;
 }

#print_r($arguments);

require_once($pwd.'/../../lib/nusoap/lib/nusoap.php');
require_once($pwd.'/../conf/sc_connection.php');

#$client = new nusoap_client('http://'.$arguments['scserver'].':12671/IncidentManagement?wsdl', 'wsdl','','','','');
#$client = new nusoap_client('http://monsvcctrdr1:12671/IncidentManagement?wsdl', 'wsdl','','','','');

$err = $client->getError();
if ($err) {
	die( '<h2>Constructor error</h2><pre>' . $err . '</pre>');
}

// Doc/lit parameters get wrapped
# $group = trim(file_get_contents("http://controlm/bip/scexport.php?type=link&group=".$arguments['contact.name']));
# $group="PATROL SERVICE SUPPORT";

$keys = new soapval('keys','IncidentKeysType',"","http://servicecenter.peregrine.com/PWS");
$Category = new soapval("Category","StringType","AEM","http://servicecenter.peregrine.com/PWS/Common");
$ConfigurationItem = new soapval("ConfigurationItem","StringType",$arguments['logical.name'],"http://servicecenter.peregrine.com/PWS/Common");
$Contact = new soapval("Contact","StringType",$arguments['contact.name'],"http://servicecenter.peregrine.com/PWS/Common");
$DevType = new soapval("DevType","StringType",$arguments['objid'],"http://servicecenter.peregrine.com/PWS/Common");
$IncDesc = new soapval("IncidentDescription","StringType",$arguments['action,1'],"http://servicecenter.peregrine.com/PWS/Common");
$IncidentDescription = new soapval("IncidentDescription","ArrayType",$IncDesc,"http://servicecenter.peregrine.com/PWS/Common");
$Location = new soapval("Location","StringType",$arguments['location'],"http://servicecenter.peregrine.com/PWS/Common");
$NetworkName = new soapval("NetworkName","StringType",$arguments['network.name'],"http://servicecenter.peregrine.com/PWS/Common");
$PrimaryAssignmentGroup = new soapval("PrimaryAssignmentGroup","StringType",$arguments['contact.name'],"http://servicecenter.peregrine.com/PWS/Common");
$Priority = new soapval("Priority","StringType",$arguments['priority.code'],"http://servicecenter.peregrine.com/PWS/Common");
$ReferenceNo = new soapval("ReferenceNo","StringType",$arguments['alertId'],"http://servicecenter.peregrine.com/PWS/Common");
$FailingComponent= new soapval("FailingComponent","StringType",$arguments['failing.component'],"http://servicecenter.peregrine.com/PWS/Common");

$instance = new soapval("instance","IncidentInstanceType",array(
$Category,
$ConfigurationItem,
$Contact,
$DevType,
$IncidentDescription,
$Location,
$NetworkName,
$PrimaryAssignmentGroup,
$Priority,
$FailingComponent,
$ReferenceNo),"http://servicecenter.peregrine.com/PWS/Common");
$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");	
$CreateIncidentRequest = new soapval("CreateIncidentRequest","CreateIncidentRequestType",$model,"http://servicecenter.peregrine.com/PWS");
//$client->loadWSDL();
$client->setCredentials('pem', 'Pemspassword');
$result = $client->call('CreateIncident',$CreateIncidentRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//, '', 'Create', false, true,'rpc');
// Check for a fault
if ($client->fault) {
	sclog('Create FAILED - Client Fault:');
	sclog(print_r($result,TRUE));
} else {
	// Check for errors
	$err = $client->getError();
	if ($err) {
		// Display the error
		sclog("Create FAILED - Client Error: $err");
#		print_r($client);
		sclog(print_r($result,TRUE));
	} else {
		if($result['!status'] != "SUCCESS" ){
			sclog($logfile,'Create FAILED - SC Error:');
			sclog($logfile,print_r($result,TRUE));
		}
	}
}

if(isset($result['model']['keys']['IncidentID']))
 {
  $im = $result['model']['keys']['IncidentID'];
  # echo $arguments['alertId']." ".$im;
  echo($im);
 }

?>
