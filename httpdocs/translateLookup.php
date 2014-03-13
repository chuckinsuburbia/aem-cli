<?php
$basePath="/in/AEM";
require_once($basePath."/lib/aemdb.php");
require_once($basePath."/conf/config.php");
require_once($basePath.'/lib/functions.php');

if(!isset($_REQUEST['stepname'])) { die("ERROR: Step name must be defined."); }
if(!isset($_REQUEST['alertId'])) { die("ERROR: Alert ID must be defined."); }

//Get step number for named step
$sql = "SELECT * from aem_step where as_name = '".$_REQUEST['stepname']."'";
$result = mysql_query($sql,$aem) or die(mysql_error());
switch(mysql_num_rows($result))
 {
  case 0:
   die("ERROR: Step name ".$_REQUEST['stepname']." not found in database.");
   break;
  case 1:
   $row = mysql_fetch_assoc($result);
   if ($row['as_action'] != "function translate") { die("ERROR: Step ".$_REQUEST['stepname']." is not a translate function.");}
   $step = $row['as_id'];
   $returnToken['id'] = $row['as_return_token'];
   // Get step input tokens
   $sql = "select asc_token from aem_step_config where asc_step = ".$step." order by asc_sequence";
   $result = mysql_query($sql,$aem) or die(mysql_error());
   while($row = mysql_fetch_assoc($result))
    {     
     $stepTokens[]['id'] = $row['asc_token'];
     $stepTokens['id'][] = $row['asc_token'];
     
    }
   //Get token names and values
   $alertTokensId = getAlertTokens($_REQUEST['alertId'],'id');
   $allTokens = getTokens();
   $returnToken['name'] = $allTokens[$returnToken['id']];
   foreach($stepTokens as $k => &$token)
    {
     if(is_numeric($k))
      {
       $token['value'] = (isset($alertTokensId[$token['id']])) ? $alertTokensId[$token['id']] : ""; 
       $stepTokens['value'][] = (isset($alertTokensId[$token['id']])) ? $alertTokensId[$token['id']] : "";
       $token['name'] = (isset($allTokens[$token['id']])) ? $allTokens[$token['id']] : "";
       $stepTokens['name'][] = (isset($allTokens[$token['id']])) ? $allTokens[$token['id']] : "";
      }
    }
   //Find matching translation steps
   $alertTokenString = implode("|",$stepTokens['value']);
   $sql = "select atran_match,atran_value from aem_translation where atran_step = ".$step." and '".$alertTokenString."' rlike atran_match order by atran_sequence";
   $result = mysql_query($sql,$aem) or die(mysql_error());
   while ($row = mysql_fetch_assoc($result))
    {
     $row['match'] = explode("\|",$row['atran_match']);
     $translationConfigs[] = $row;
    }
   break;
  default:
   die("ERROR: Step name ".$_REQUEST['stepname']." returned multiple results in database.");
 }

//Construct HTML elements
$htmlColumnNames = "<tr><th></th>";
foreach ($stepTokens['name'] as $name) { $htmlColumnNames .= "<th>".$name."</th>"; }
$htmlColumnNames .= "</tr>";

$htmlTokenValues = "<tr><th>Alert Values</th>";
foreach ($stepTokens['value'] as $value) { $htmlTokenValues .= "<td>".$value."</td>"; }
$htmlTokenValues .= "</tr>";

$htmlMatchValues = "<tr><th>Matching Values</th>";
foreach ($translationConfigs[0]['match'] as $match) { $htmlMatchValues .= "<td>".$match."</td>"; }
$htmlMatchValues .= "</tr>";

$htmlReturnToken = "<tr><th>Return Token</th><td>".$returnToken['name']."</td></tr>";
$htmlTransValue  = "<tr><th>Translation</th><td>".$translationConfigs[0]['atran_value']."</td></tr>";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="css/style1.css">
</head>
<body>

<table style="max-width: 100%">
<?php
echo($htmlColumnNames."\n");
echo($htmlTokenValues."\n");
echo($htmlMatchValues."\n");
?>
</table>
<br />
<table>
<?php
echo($htmlReturnToken."\n");
echo($htmlTransValue."\n");
?>
</table>
</body>
</html>
