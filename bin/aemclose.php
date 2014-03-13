#!/usr/bin/php
<?php
# Example command line
# aemclose.php incident_id=<number>
$basepath="/in/AEM/";
require_once($basepath."lib/aemdb.php");
require_once($basepath."lib/functions.php");
require_once($basepath."conf/config.php");
require_once($basepath.'lib/CronParser.php');

$db_tbl_alert="aem_alert";

array_shift($argv);

# read tokens from command line
foreach($argv as $arg){
	$argA=split('=',$arg);
	$tokens[$argA[0]] = trim($argA[1]);
	if($debug) aemlog($argA[0]." = ".$argA[1]);
}
if(!isset($tokens['incident_id'])) die("Incident ID required.\n");

if($aem_incident=$tokens['incident_id']) unset($tokens['incident_id']);

if($debug) aemlog("Received close for incident ID: ".$aem_incident);

$sql ="select * from ".$db_tbl_alert;
$sql.=" where aa_id='".$aem_incident."'";
$res=mysql_query($sql,$aem) or die(mysql_error());
switch (mysql_num_rows($res))
 {
  case 0:
   if($debug) aemlog("No alerts found with incident ID: ".$aem_incident);
   die;
   break;
  case 1:
   $alert=mysql_fetch_assoc($res);
   break;
  default:
   if($debug) aemlog("Error: ".mysql_num_rows($res)." alerts found with incident ID: ".$aem_incident);
   die;
 }  


$sql ="update aem_alert set aa_update_time=NOW()";
$sql.=" where aa_id='".$aem_incident."'";
mysql_query($sql,$aem) or die(mysql_error());

$sql ="update aem_alert set aa_status='closed'";
$sql.=" where aa_id='".$aem_incident."'";
mysql_query($sql,$aem) or die(mysql_error());

$sql ="select aat_value from aem_tokens left join aem_alert_tokens";
$sql.=" on aem_tokens.at_id=aem_alert_tokens.aat_token";
$sql.=" where at_name='source' and aat_alert='".$aem_incident."'";
$res=mysql_query($sql,$aem) or die(mysql_error());
list($source)=mysql_fetch_array($res);
if($debug) aemlog("Source for incident ID ".$aem_incident." is: ".$source);

/*
Run Steps needed here
*/
$type="close";
$dPath=getDestPath($source,$type);
#process each step for this path
foreach($dPath as $step)
 {
  $stepRc=runStep($aem_incident,$step);
  if(!$stepRc)
   {
    aemlog("Destination Step Failed!");
   }
 }



if($debug) aemlog("End of close Processing for incident ID: ".$aem_incident);

?>
