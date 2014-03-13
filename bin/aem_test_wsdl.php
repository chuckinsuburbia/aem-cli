#!/usr/bin/php
<?php

/*
Get command line arguments
*/
$shortopts = "";
$longopts = array("function:","name:","incidentId:");

$options = getopt($shortopts, $longopts);
if (!isset($options['function'])) die("Error: function parameter is required.\n");

/*
Function definitions
*/
function doHello($client,$name)
 {
  $parm = array('name' => $name);
  $result = $client->call('hello', $parm);
  if ($client->fault)
   {
    return("Fault");
   }
  else
   {
    $err = $client->getError();
    if ($err)
     {
      return("Error: ".$err);
     }
    else
     {
      return($result);
     }
   }
 }

function doClose($client,$incidentId)
 {
  $parm = array('incidentId' => $incidentId);
  $result = $client->call('closeIncident', $parm);
  if ($client->fault)
   {
    return("Fault");
   }
  else
   {
    $err = $client->getError();
    if ($err)
     {
      return("Error: ".$err);
     }
    else
     {
      return($result);
     }
   }
 }


/*
Begin Processing
*/
// Pull in the NuSOAP code
require_once('/in/AEM/lib/nusoap/lib/nusoap.php');
// Create the client instance
$client = new soapclient('http://aemdev/webservice/index.php?wsdl', true);
// Check for an error
$err = $client->getError();
if ($err) {
    // Display the error
    die("Constructor error: ".$err."\n");
}

switch ($options['function'])
 {
  case "hello":
   if (!isset($options['name'])) die("Error: hello function requires name parameter.");
   $result = doHello($client,$options['name']);
   echo($result."\n");
   break;
  case "closeIncident":
   if (!isset($options['incidentId'])) die("Error: close function requires incidentId parameter.");
   $result = doClose($client,$options['incidentId']);
   echo($result."\n");
   break;
  default:
   echo("Unknown function ".$options['function']."\n");
 }

?>
