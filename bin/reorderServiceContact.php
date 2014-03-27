#!/usr/bin/php
<?php
$basepath=getenv("AEMBASE");
require_once($basepath."/conf/config.php");
require_once($basepath."/lib/functions.php");

$db_tbl_trans="aem_translation";
$db_tbl_stage="aem_translation_stage";

if($debug) aemlog("Begin reordering text translation records");

$sql ="drop table if exists ".$db_tbl_stage;
mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

$sql ="create table ".$db_tbl_stage." like ".$db_tbl_trans;
mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

$sql ="insert ".$db_tbl_stage." select * from ".$db_tbl_trans;
mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

$sql ="delete from ".$db_tbl_trans." where atran_step=4";
mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");

$sql ="select * from ".$db_tbl_stage." where atran_step=4 order by atran_match desc";
$res=mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");
$seq=1;
while($step = mysql_fetch_assoc($res))
 {
  foreach ($step as $k => $v)
   {
    $step[$k]=mysql_real_escape_string($v);
   }
  $sql ="insert into ".$db_tbl_trans." (atran_step,atran_sequence,atran_match,atran_value)";
  $sql.=" values (".$step['atran_step'].",".$seq.",'".$step['atran_match']."','".$step['atran_value']."')";
  mysql_query($sql,$aem) or die(mysql_error()."\n".$sql."\n");
  $seq++;
 }


if($debug) aemlog("End of text translation reordering");

?>
