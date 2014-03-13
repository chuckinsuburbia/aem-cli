<?php 
ini_set('memory_limit','-1');
ini_set('max_execution_time', 0); 

if(isset($argv[1])) { $_REQUEST['action'] = $argv[1]; }

$basePath="/in/AEM";
require_once($basePath."/lib/aemdb.php");
require_once($basePath."/conf/config.php");
require_once($basePath.'/lib/functions.php');
require_once($basePath.'/lib/nusoap/lib/nusoap.php');
require_once($basePath.'/sc/conf/sc_connection.php');
$debug = false;

function closeSC($diff,$aem)
 {
  $debug = false;
  $status = "";
  $sql = "select as_id from aem_step where as_name='SC Close'";
  $result = mysql_query($sql,$aem) or die(mysql_error());
  $step = mysql_fetch_assoc($result);
  foreach($diff as $im => $incident)
   {
    $status.="Triggering AEM to close SC ticket ".$im."...";
    $rc = runStep($incident,$step['as_id']);
    $status .= ($rc == true) ? "Success.<br />" : "Failed.<br />";
   }
  $status .= "<form action='".$_SERVER['PHP_SELF']."'><input type=submit value='Refresh'></form></body></html>";
  return($status);
 }

function openSC($diff,$aem)
 {
  $debug = false;
  $status = "";
  $sql = "select as_id from aem_step where as_name='SC Open'";
  $result = mysql_query($sql,$aem) or die(mysql_error());
  $step = mysql_fetch_assoc($result);
  foreach($diff as $im => $incident)
   {
    $status .= "Triggering AEM to open new SC ticket for alert ".$incident."...";
    $rc = runStep($incident,$step['as_id']);
    $status .= ($rc == true) ? "Success.<br />" : "Failed<br />";
   }
  $status .= "<form action='".$_SERVER['PHP_SELF']."'><input type=submit value='Refresh'></form></body></html>";
  return($status);
 }


$query = '(Category="PEM"|Category="BMC")&IMTicketStatus~="Closed"&PrimaryAssignmentGroup~="PEMEMAILTEST"';

//$client = new nusoap_client('http://monsvcctrdr1:12671/IncidentManagement?wsdl', 'wsdl','','','','');
//$client = new nusoap_client('http://scclientprod:12671/IncidentManagement?wsdl', 'wsdl','','','','');
$err = $client->getError();
if ($err) { die( '<h2>Constructor error</h2><pre>' . $err . '</pre>'); }

$client->setCredentials('pem', 'Pemspassword');
// Doc/lit parameters get wrapped
$keys = new soapval('keys','IncidentKeysType',"","http://servicecenter.peregrine.com/PWS",false,array("query" => $query));
$instance = new soapval("instance","IncidentInstanceType","","http://servicecenter.peregrine.com/PWS");
$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");	
	$RetrieveIncidentListRequest = new soapval("RetrieveIncidentListRequest","RetrieveIncidentListRequestType",$model,"http://servicecenter.peregrine.com/PWS");
	$result = $client->call('RetrieveIncidentList',$RetrieveIncidentListRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");
// Check for a fault

if ($client->fault) 
 {
  echo '<h2>Retrieve Fault</h2><pre>';
  print_r($result);
  echo '</pre>';
  die("</body></html");
 } 
$err = $client->getError();
if ($err) 
 {
  // Display the error
  echo '<h2>Retrieve Error</h2><pre>' . $err . '</pre>';
  die("</body></html");
 }
if($result['!status'] == "FAILURE"){ die($result['!message']."</body></html>"); }

foreach($result['instance'] as $ticket){ (isset($ticket['ReferenceNo'])) && $scTickets[$ticket['IncidentID']] = $ticket['ReferenceNo']; }
unset($result);
ksort($scTickets);

$aad = getActiveAlerts();
foreach($aad as $alert) { if (isset($alert['sc_incident_id'])) { $aemTickets[$alert['sc_incident_id']] = $alert['aa_id']; } else { $aemTickets[] = $alert['aa_id']; } }
unset($aad);
ksort($aemTickets);

$diff = array_diff($scTickets,$aemTickets);
$diff1 = array_diff($aemTickets,$scTickets);

if (isset($_REQUEST['action']))
 {
  switch ($_REQUEST['action'])
   {
    case "closeSC":
     $status = closeSC($diff,$aem);
     break;
    case "openSC":
     $status = openSC($diff1,$aem);
     break;
    case "doBoth":
     $status = closeSC($diff,$aem);
     $status.= openSC($diff1,$aem);
    default:
     echo("ERROR: I don't understand action ".$_REQUEST['action'].".");
   }
 }

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ServiceCenter Ticket Cleanup</title>
<link href="css/default.css" rel="stylesheet" type="text/css" />
</head>

<body>

<?php
(isset($status)) && die($status);

echo("<a href=\"".$_SERVER['PHP_SELF']."?action=doBoth\">Close and Open at the same time</a><br />\n\n");
echo "<table border=\"1\"><tr valign=\"top\"><td><pre>AEM\n\n";
print_r($aemTickets);
echo "</pre></td><td><pre>SC\n\n";
print_r($scTickets);
echo "</pre></td><td><pre>In SC not AEM\n\n<a href=\"".$_SERVER['PHP_SELF']."?action=closeSC\">Close These</a>\n\n";
print_r($diff);
echo "</pre></td><td><pre>In AEM not SC\n\n<a href=\"".$_SERVER['PHP_SELF']."?action=openSC\">Open These</a>\n\n";
print_r($diff1);
echo "</pre></td></tr></table>";
?>

</body>
</html>
