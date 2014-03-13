<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AEM - Step Config</title>
<body>

<?php 
include('../lib/aemdb.php');
include('../lib/functions.php');

$db_tbl_primary="aem_step_config";
$db_tbl_stage=$db_tbl_primary."_stage";
$db_col_prefix="asc_";
$db_col_step=$db_col_prefix."step";
$db_col_seq =$db_col_prefix."sequence";
$db_col_tok =$db_col_prefix."token";

topOpg();  // load top of page and links from function

if(isset($_REQUEST['step'])) $step = $_REQUEST['step'];
if(isset($_REQUEST['action'])) $action = $_REQUEST['action'];

?>
<table>
 <tr>
  <th>Step</th>
  <th>Type</th>
 </tr>
 <tr>
<?php
echo("<form method='post' action=".$_SERVER['PHP_SELF'].">");
echo("<td><select name='source'>");
$sql="select as_id,as_name from aem_step order by as_id asc";
$res=mysql_query($sql,$aem) or die(mysql_error());
while ($row = mysql_fetch_assoc($res))
 {
  echo $row['as_id']==$step ? "<option selected=selected " : "<option ";
  echo("value='".$row['as_id']."'>".$row['as_name']."</option>");
 }
echo("</select></td>");
echo("<td><input type=submit value='Change'></td></tr></table></form>");

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
  case "modify":
   $sql="update ".$db_tbl_stage." set ".$db_col_step."='".$_REQUEST['step']."'";
   $sql.=" where ".$db_col_step."='".$step."'";
   $sql.=" and ".$db_col_seq."='".$_REQUEST['sequence']."'";
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
   $sql="select max(".$db_col_seq.") max from ".$db_tbl_stage;
   $sql.=" where ".$db_col_step."='".$step."'";
   $maxres=mysql_query($sql,$aem) or die(mysql_error());
   $max=mysql_fetch_assoc($maxres);
   $sql="insert into ".$db_tbl_stage." (".$db_col_step.",".$db_col_seq.",".$db_col_tok.") ";
   $sql.="values ('".$step."','".($max['max']+1)."','".$_REQUEST['token']."')";
   mysql_query($sql,$aem) or die(mysql_error());
   break;
 }

?>
<table border=1 cellspacing=0>
 <tr>
  <th>Sequence</th>
  <th>Token</th>
 </tr>
<?php
$sql= "select ".$db_col_seq.",".$db_col_tok.",at_name from ".$db_tbl_stage;
$sql.=" left join aem_tokens on ".$db_tbl_stage.".".$db_col_tok."=aem_tokens.at_id";
$sql.=" where ".$db_col_step."='".$step."'";
$sql.=" order by ".$db_col_seq;
$res=mysql_query($sql,$aem) or die(mysql_error());

while ($row = mysql_fetch_assoc($res))
 {
  echo("<tr>");
  echo("<td>".$row[$db_col_seq]."</td>");
  echo("<td><form method='post' action='".$_SERVER['PHP_SELF']."'>");
  echo("<input type=hidden name='action' value='modify' />");
  echo("<input type=hidden name='sequence' value='".$row[$db_col_seq]."' />");
  echo("<input type=hidden name='continue' value='true' />");
  echo("<input type=hidden name='step' value='".$step."' />");
  echo("<select onchange='this.form.submit()' name='token'>");
  $sql="select at_id,at_name from aem_tokens order by at_name";
  $tokens=mysql_query($sql,$aem) or die(mysql_error());
  while ($token = mysql_fetch_assoc($tokens))
   {
    echo $row[$db_col_tok]==$token['at_id'] ? "<option selected=selected " : "<option ";
    echo("value='".$token['at_id']."'>".$token['at_name']."</option>");
   }
  echo("</select></form></td>");
  echo("<td><table><tr>");
  echo("<td>");
  if ($row[$db_col_seq] != 1)
   {
    echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
    echo("<input type=hidden name='step' value='".$step."' />");
    echo("<input type=hidden name='token' value='".$row[$db_col_tok]."' />");
    echo("<input type=hidden name='sequence' value='".$row[$db_col_seq]."' />");
    echo("<input type=hidden name='continue' value='true' />");
    echo("<input type=hidden name='action' value='moveup' />");
    echo("<input type=image alt='Submit' src='/images/uparrow.gif' /></form>");
   } else {
    echo("<img src=/images/spacer.gif width='20' />");
   }
  echo("</td>");
  echo("<td>");
  $sql="select max(".$db_col_seq.") max from ".$db_tbl_stage;
  $sql.=" where ".$db_col_step."='".$step."'";
  $maxres=mysql_query($sql,$aem) or die(mysql_error());
  $max=mysql_fetch_assoc($maxres);
  if ($row[$db_col_seq] != $max['max'])
   {
    echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
    echo("<input type=hidden name='step' value='".$step."' />");
    echo("<input type=hidden name='token' value='".$row[$db_col_tok]."' />");
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
  echo("</tr></table></td>");
  echo("</tr>");
 }
echo("</table>");

?>
<table>
 <tr>
  <th>Insert new step</th>
 </tr>
 <tr>
  <td>
<?php
echo("<form method='post' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='step' value='".$step."' />");
echo("<input type=hidden name='action' value='add' />");
echo("<input type=hidden name='continue' value='true' />");
echo("<select name='token'>");
$sql="select at_id,at_name from aem_tokens order by at_name";
$tokens=mysql_query($sql,$aem) or die(mysql_error());
while ($token = mysql_fetch_assoc($tokens))
 {
  echo $row[$db_col_tok]==$token['at_id'] ? "<option selected=selected " : "<option ";
  echo("value='".$token['at_id']."'>".$token['at_name']."</option>");
 }
echo("</select><input type=submit value='Add' /></form></td></tr></table>");

echo("</p><p><form method='post' action='".$_SERVER['PHP_SELF']."'>");
echo("<input type=hidden name='action' value='commit' />");
echo("<input type=submit value='Commit Changes' /></form>");

echo("</body></html>");

?>
