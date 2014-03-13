<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AEM - Translation Config</title>
<body style="width: 90%">

<?php 
include('../lib/aemdb.php');
include('../lib/functions.php');

$db_tbl_primary="aem_translation";
$db_tbl_stage=$db_tbl_primary."_stage";
$db_col_prefix="atran_";
$db_col_step=$db_col_prefix."step";
$db_col_seq =$db_col_prefix."sequence";
$db_col_mat =$db_col_prefix."match";
$db_col_val =$db_col_prefix."value";

topOpg();  // load top of page and links from function

if(isset($_REQUEST['step'])) $step = $_REQUEST['step'];
if(isset($_REQUEST['action'])) $action = $_REQUEST['action'];


if ($action == "commit")
 {
  $sql="truncate table ".$db_tbl_primary;
  mysql_query($sql,$aem) or die(mysql_error());
  $sql="insert into ".$db_tbl_primary." select * from ".$db_tbl_stage;
  mysql_query($sql,$aem) or die(mysql_error());
  echo("<h3>Changes committed</h3><a href='/index.php'>Home</a></body></html>");
  die;
 }
elseif ($_REQUEST['continue'] != "true")
 {
  $sql="truncate table ".$db_tbl_stage;
  mysql_query($sql,$aem) or die(mysql_error());
  $sql="insert into ".$db_tbl_stage." select * from ".$db_tbl_primary;
  mysql_query($sql,$aem) or die(mysql_error());
 }

switch ($action)
 {
  case "moveup":
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=-1";
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".$_REQUEST['sequence']."'";
   mysql_query($sql,$aem) or die(mysql_error());
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=".$_REQUEST['sequence'];
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".($_REQUEST['sequence']-1)."'";
   mysql_query($sql,$aem) or die(mysql_error());
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=".($_REQUEST['sequence']-1);
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='-1'";
   mysql_query($sql,$aem) or die(mysql_error());
   break;
  case "movedown":
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=-1";
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".$_REQUEST['sequence']."'";
   mysql_query($sql,$aem) or die(mysql_error());
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=".$_REQUEST['sequence'];
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".($_REQUEST['sequence']+1)."'";
   mysql_query($sql,$aem) or die(mysql_error());
   $sql="update ".$db_tbl_stage." set ".$db_col_seq."=".($_REQUEST['sequence']+1);
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='-1'";
   mysql_query($sql,$aem) or die(mysql_error());
   break;
  case "delete":
   $sql="delete from ".$db_tbl_stage;
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".$_REQUEST['sequence']."'";
   mysql_query($sql,$aem) or die(mysql_error());
   $sql="select max(".$db_col_seq.") max from ".$db_tbl_stage;
   $sql.=" where ".$db_col_step."='".$step."'";
   $maxres=mysql_query($sql,$aem) or die(mysql_error());
   $max=mysql_fetch_assoc($maxres);
   if ($_REQUEST['sequence'] < $max['max'])
    {
     for ($i=$_REQUEST['sequence']; $i<=$max['max']; $i++)
      {
       $sql="update ".$db_tbl_stage." set ".$db_col_seq."=".($i-1);
       $sql.=" where ".$db_col_step."='".$step."'";
       $sql.=" and ".$db_col_seq."='".$i."'";
       mysql_query($sql,$aem) or die(mysql_error());
      }
    }
   break;
  case "add":
   if (isset($_REQUEST['done']) && $_REQUEST['done'] == 'true')
    {
     $search = '';
     $replace = '.*';
     $_REQUEST['config'] = array_replace($_REQUEST['config'],array_fill_keys(array_keys($_REQUEST['config'], $search),$replace));
     foreach($_REQUEST['config'] as &$config)
      {
       $config = "(".$config.")";
      }
     $match = implode('\|',$_REQUEST['config']);
     $sql="select max(".$db_col_seq.") max from ".$db_tbl_stage;
     $sql.=" where ".$db_col_step."='".$step."'";
     $maxres=mysql_query($sql,$aem) or die(mysql_error());
     $max=mysql_fetch_assoc($maxres);
     $seq = $max['max'] + 1;
     $sched = $_REQUEST['minute']." ".$_REQUEST['hour']." ".$_REQUEST['dom']." ".$_REQUEST['month']." ".$_REQUEST['dow']."|".$_REQUEST['duration'];
     $sql  = "INSERT INTO ".$db_tbl_stage." (".$db_col_step.", ".$db_col_seq.", ".$db_col_mat.", ".$db_col_val.")";
     $sql .= " VALUES ('".$_REQUEST['step']."', '".$seq."', '".mysql_real_escape_string($match)."', '".mysql_real_escape_string($sched)."')";
     mysql_query($sql, $aem) or die(mysql_error());
     echo("<script language='Javascript'> alert ('Translation added, step: ".$_REQUEST['step'].", sequence: ".$seq."') </script>");
     echo("<script language='Javascript'> window.location='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&continue=true' </script>");
    }
   else
    {
     echo("<h4>Insert new step</h4><table><tr>");
     echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
     echo("<input type=hidden name='step' value='".$step."' />");
     echo("<input type=hidden name='action' value='add' />");
     echo("<input type=hidden name='continue' value='true' />");
     foreach($_REQUEST['config'] as $k => $v)
      {
       echo("<td>".$v."<br /><input type=text name='config[".$k."]' /></td>");
      }
     echo("</tr></table>");
     echo("<table>");
     echo("<tr><td colspan=5><h3>Schedule<h3></td></tr>");
     echo("<tr><td>Minute<br /><select name=minute /><option valie='*'>*</option>");
     for ($i = 0; $i <= 59; $i++) {echo("<option value=".$i.">$i</option>");}
     echo("</select></td>");
     echo("<td>Hour<br /><select name=hour /><option valie='*'>*</option>");
     for ($i = 0; $i <= 23; $i++) {echo("<option value=".$i.">$i</option>");}
     echo("</select></td>");
     echo("<td>Day of Month<br /><select name=dom /><option valie='*'>*</option>");
     for ($i = 1; $i <= 31; $i++) {echo("<option value=".$i.">$i</option>");}
     echo("</select></td>");
     echo("<td>Month<br /><select name=month /><option valie='*'>*</option>");
     for ($i = 1; $i <= 12; $i++) {echo("<option value=".$i.">$i</option>");}
     echo("</select></td>");
     echo("<td>Day of Week<br /><select name=dow /><option valie='*'>*</option>");
     for ($i = 1; $i <= 7; $i++) {echo("<option value=".$i.">$i</option>");}
     echo("</select></td></tr>");
     echo("<tr><td colspan=5>Duration<br /><input type=text name=duration /></td></tr>");
     echo("<tr><td>"); 
     echo("<input type='hidden' name='done' value='true' />");
     echo("<input type=submit value='Add' /></form>");
     echo("</td></tr><tr><td>");
     echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
     echo("<input type=hidden name='step' value='".$step."' />");
     echo("<input type=hidden name='continue' value='true' />");
     echo("<input type=submit value='Cancel' /></form>");
     echo("</td></tr></table>");
     die("</body></html>");
    }
   break;
  case "modify":
   if (isset($_REQUEST['done']) && $_REQUEST['done'] == 'true')
    {
     $search = '';
     $replace = '.*';
     $_REQUEST['token'] = array_replace($_REQUEST['token'],array_fill_keys(array_keys($_REQUEST['token'], $search),$replace));
     foreach($_REQUEST['token'] as &$token)
      {
       $token = "(".$token.")";
      }
     $match = implode('\|',$_REQUEST['token']);
     $sched = $_REQUEST['minute']." ".$_REQUEST['hour']." ".$_REQUEST['dom']." ".$_REQUEST['month']." ".$_REQUEST['dow']."|".$_REQUEST['duration'];
     $sql  = "UPDATE ".$db_tbl_stage." SET ".$db_col_mat."='".mysql_real_escape_string($match)."', ".$db_col_val."='".mysql_real_escape_string($sched)."'";
     $sql .= " WHERE ".$db_col_step."='".$_REQUEST['step']."' AND ".$db_col_seq."='".$_REQUEST['sequence']."'";
     mysql_query($sql, $aem) or die(mysql_error());
     echo("<script language='Javascript'> alert ('Translation modified, step: ".$_REQUEST['step'].", sequence: ".$_REQUEST['sequence']."') </script>");
     echo("<script language='Javascript'> window.location='".$_SERVER['PHP_SELF']."?step=".$_REQUEST['step']."&continue=true' </script>");
    }
   else
    {
     list($cron,$duration)=explode("|",$_REQUEST['translation']);
     list($minute,$hour,$dom,$month,$dow)=explode(" ",$cron);
     echo("<table><tr>");
     echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
     echo("<input type=hidden name='step' value='".$step."' />");
     echo("<input type=hidden name='action' value='modify' />");
     echo("<input type=hidden name='done' value='true' />");
     echo("<input type=hidden name='continue' value='true' />");
     echo("<input type=hidden name='sequence' value='".$_REQUEST['sequence']."' />");
     foreach($_REQUEST['config'] as $k => $v)
      {
       echo("<td>".$v."<input type=text name='token[".$k."]' value='".$_REQUEST['token'][$k]."' /></td>");
      }
     echo("</tr></table>");
     echo("<table>");
     echo("<tr><td colspan=5><h3>Schedule<h3></td></tr>");
     echo("<tr><td>Minute<br /><select name=minute /><option valie='*'>*</option>");
     for ($i = 0; $i <= 59; $i++) {echo("<option value=".$i);if($i == $minute) {echo(" selected");}echo(">$i</option>");}
     echo("</select></td>");
     echo("<td>Hour<br /><select name=hour /><option valie='*'>*</option>");
     for ($i = 0; $i <= 23; $i++) {echo("<option value=".$i);if($i == $hour) {echo(" selected");}echo(">$i</option>");}
     echo("</select></td>");
     echo("<td>Day of Month<br /><select name=dom /><option valie='*'>*</option>");
     for ($i = 1; $i <= 31; $i++) {echo("<option value=".$i);if($i == $dom) {echo(" selected");}echo(">$i</option>");}
     echo("</select></td>");
     echo("<td>Month<br /><select name=month /><option valie='*'>*</option>");
     for ($i = 1; $i <= 12; $i++) {echo("<option value=".$i);if($i == $month) {echo(" selected");}echo(">$i</option>");}
     echo("</select></td>");
     echo("<td>Day of Week<br /><select name=dow /><option valie='*'>*</option>");
     for ($i = 1; $i <= 7; $i++) {echo("<option value=".$i);if($i == $dow) {echo(" selected");}echo(">$i</option>");}
     echo("</select></td></tr>");
     echo("<tr><td colspan=5>Duration<br /><input type=text name=duration value='".$duration."' /></td></tr>");
     echo("<tr><td>");
     echo("<input type=submit value='Modify' /></form></td></tr></table>");
     echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
     echo("<input type=hidden name='step' value='".$step."' />");
     echo("<input type=hidden name='continue' value='true' />");
     echo("<input type=submit value='Cancel' /></form>");
     die("</body></html>");
    }
   break;
 }

$sql = "SELECT at_name FROM aem_step_config, aem_tokens";
$sql.= " WHERE at_id = asc_token AND asc_step = ".$step;
$sql.= " ORDER BY asc_sequence";
$config =  mysql_query($sql,$aem) or die(mysql_error());
$configs=array();
while($row = mysql_fetch_assoc($config))
 {
  array_push($configs,$row['at_name']);
 }

$sql = "select * from ".$db_tbl_stage." where atran_step = ".$step." order by atran_sequence";
$translations = mysql_query($sql, $aem) or die(mysql_error());

$sql = "select as_name, at_name from aem_step, aem_tokens where as_return_token = at_id and as_id = ".$step;
$result = mysql_query($sql, $aem) or die(mysql_error());
$stepName = mysql_fetch_assoc($result);

echo("You are working in a temporary table.  Nothing will be saved until you click on 'Commit Changes'.<br />");
echo("<h3>".$stepName['as_name']."</h3>");
echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='action' value='commit' />");
echo("<input type=submit value='Commit Changes' /></form>");
echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='step' value='".$step."' />");
echo("<input type=hidden name='action' value='add' />");
echo("<input type=hidden name='continue' value='true' />");
foreach($configs as $k => $v)
 {
  echo("<input type=hidden name='config[".$k."]' value='".$v."' />");
 }
echo("<input type=submit value='Add new item' /></form>");

echo("<div style='max-width: 85%; height: 400px; overflow-y: scroll;'>");
echo("<table style='display: block max-width: 100%' border=1 cellspacing=0><tr><th></th><th>Sequence</th>");
foreach($configs as $config)
 {
  echo("<th>".$config."</th>");
 }
echo("</tr>");


$sql="select max(".$db_col_seq.") max from ".$db_tbl_stage;
$sql.=" where ".$db_col_step."='".$step."'";
$maxres=mysql_query($sql,$aem) or die(mysql_error());
$max=mysql_fetch_assoc($maxres);

while($row = mysql_fetch_assoc($translations))
 {
  if(empty($row['atran_match']))
   {
    $tokens=$default;
   }
  else
   {
    $tokens = split('\|',$row['atran_match']);
   }
  echo("<tr id=".$row['atran_sequence'].">");
  echo("<td><table><tr>");
  echo("<td>");
  if ($row[$db_col_seq] != 1)
   {
    echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
    echo("<input type=hidden name='step' value='".$step."' />");
    echo("<input type=hidden name='sequence' value='".$row[$db_col_seq]."' />");
    echo("<input type=hidden name='continue' value='true' />");
    echo("<input type=hidden name='action' value='moveup' />");
    echo("<input type=image alt='Submit' src='/images/uparrow.gif' /></form>");
   } else {
    echo("<img src=/images/spacer.gif width='20' />");
   }
  echo("</td>");
  echo("<td>");
  if ($row[$db_col_seq] != $max['max'])
   {
    echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
    echo("<input type=hidden name='step' value='".$step."' />");
    echo("<input type=hidden name='sequence' value='".$row[$db_col_seq]."' />");
    echo("<input type=hidden name='continue' value='true' />");
    echo("<input type=hidden name='action' value='movedown' />");
    echo("<input type=image alt='Submit' src='/images/downarrow.gif' /></form>");
   } else {
    echo("<img src=/images/spacer.gif width='20' />");
   }
  echo("</td>");
  echo("<td><form method='post' action=".$_SERVER['PHP_SELF'].">");
  echo("<input type=hidden name='action' value='delete' />");
  echo("<input type=hidden name='sequence' value='".$row[$db_col_seq]."' />");
  echo("<input type=hidden name='continue' value='true' />");
  echo("<input type=hidden name='step' value='".$step."' />");
  echo("<input type=image alt='Submit' src='/images/delete.png' /></form></td>");
  echo("<td><form method='post' action=".$_SERVER['PHP_SELF'].">");
  echo("<input type=hidden name='action' value='modify' />");
  echo("<input type=hidden name='sequence' value='".$row['atran_sequence']."' />");
  foreach($tokens as $k => $v)
   {
    echo("<input type=hidden name='token[".$k."]' value='".str_replace(array("(",")","\\"),"",$v)."' />");
   }
  foreach($configs as $k => $v)
   {
    echo("<input type=hidden name='config[".$k."]' value='".str_replace(array("(",")","\\"),"",$v)."' />");
   }
  echo("<input type=hidden name='translation' value='".$row['atran_value']."' />");
  echo("<input type=hidden name='continue' value='true' />");
  echo("<input type=hidden name='step' value='".$step."' />");
  echo("<input type=image alt='Submit' src='/images/edit.gif' /></form></td>");
  echo("</tr></table></td>");

  echo("<td>".$row['atran_sequence']."</td>");
  $count=0;
  foreach($tokens as $token)
   {
    echo("<td>".str_replace(array("(",")","\\"),"",$token)."</td>");
    $count++;
   }
  echo("<td>".$row['atran_value']."</td>");
  echo("</tr>");

 }
echo("</table></div>");

echo("</body></html>");

?>
