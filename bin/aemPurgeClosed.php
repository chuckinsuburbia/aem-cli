#!/usr/bin/php
<?php
$basepath=getenv("AEMBASE");
require_once($basepath."/conf/config.php");
require_once($basepath."/lib/functions.php");
require_once($basepath.'/lib/CronParser.php');

$db_tbl_alert="aem_alert";
$db_tbl_token="aem_alert_tokens";
$db_tbl_longv="aem_alert_token_longValue";
$interval="1 month";

if($debug) aemlog("Begin purge of closed incidents");

$sql ="select aa_id from ".$db_tbl_alert;
$sql.=" where aa_status='closed' and aa_update_time<date_sub(now(), interval ".$interval.")";
$res=mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

while ($alert = mysql_fetch_assoc($res))
 {
  if($debug) aemlog("Alert ".$alert['aa_id']." has been closed for ".$interval." and will be purged from database.");

  //Get token long value ID if set, and delete long values
  $sql ="select aat_long_value from ".$db_tbl_token;
  $sql.=" where aat_alert=".$alert['aa_id']." and aat_long_value is not NULL";
  $resToken=mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");
  while ($token = mysql_fetch_assoc($resToken))
   {
    $sql = "delete from ".$db_tbl_longv." where aatl_id=".$token['aat_long_value'];
    mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");
   }
  
  //Delete alert tokens
  $sql = "delete from ".$db_tbl_token." where aat_alert=".$alert['aa_id'];
  mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

  //Delete alert
  $sql = "delete from ".$db_tbl_alert." where aa_id=".$alert['aa_id'];
  mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");
 }

if($debug) aemlog("End of purge processing for closed incidents");

?>
