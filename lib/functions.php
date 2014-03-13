<?php
/* handle login */
if(!isset($local)){
	if(isset($_SERVER['REMOTE_ADDR']) && !isset($_SESSION)) session_start();
	if(isset($_REQUEST['username'])){
		$ldapconfig['host'] = 'moncorpdc1';
		$ldapconfig['port'] = NULL;
		$ldapconfig['basedn'] = 'dc=corp,dc=gaptea,dc=com';
		$ldapconfig['authrealm'] = 'aptea';
		
		function ldap_authenticate($user,$pass) {
			global $ldapconfig;
		//	echo $user." ".$pass;    
			if ($user != "" && $pass != "") {
				$ds=@ldap_connect($ldapconfig['host'],$ldapconfig['port']);
				@ldap_bind($ds, "aptea\pem", "2hard4U");
				$r = @ldap_search( $ds, $ldapconfig['basedn'], 'sAMAccountname=' . $user);
				if ($r) {
					//foreach($r as $key => $value){
					//	print $key."=".$value;
					//}
				   $result = @ldap_get_entries( $ds, $r);
				   //print_r($result);
					if ($result[0]) {
					   if (@ldap_bind( $ds, $result[0]['dn'], $pass) ) {
							return $result[0];
						}
					}
			   }
			}
			return NULL;
		}	
		if (($result = ldap_authenticate($_REQUEST['username'],$_REQUEST['password'])) == NULL) {
			die('Authorization Failed');
		} 
		require_once('aemdb.php');
                $sql="select * from aem_user where au_name='".$_REQUEST['username']."'";
                $res=mysql_query($sql,$aem) or die(mysql_error());
		$aemuser=mysql_fetch_assoc($res);
		if ($aemuser['au_admin'] == 'true') {
			$_SESSION['adminUser'] = true;
			die("SUCCESS");	
		} else {
			die($_REQUEST['username']." is not an AEM admin user.");
		}
		//print_r($result);
	}
	if(isset($_REQUEST['action']) && $_REQUEST['action'] == "logout"){
		$_SESSION['adminUser'] = false;
		die("SUCCESS");	
	}
	
	if(isset($_SESSION['adminUser']) && $_SESSION['adminUser'] == true){
		$adminUser=true;
	}else{
		//print($_SERVER['PHP_SELF']);
		if($_SERVER['PHP_SELF'] != "/index.php")
			header("Location: /index.php");
	}
}
/*end of login stuff */

if(isset($_GET['phpinfo'])) phpinfo();


function aemlog($string){
	global $mainlog;
	file_put_contents($mainlog,date("Y-m-d H:i:s")." - ".$string."\n",FILE_APPEND);
}

function handleError($where,$what){
	aemlog("ERROR in ".$where.": ".$what);
	die();
}

function createAlert($tokens){
	global $aem;
	$sql = "insert into aem_alert (aa_status, aa_received_time) values ('new',NOW())";
	mysql_query($sql,$aem) or handleError("createAlert",mysql_error());
	$alertId = mysql_insert_id($aem);
	foreach($tokens as $name => $value){
		$longValue="NULL";
		$sql = "select at_id from aem_tokens where at_name = '".$name."'";
		aemlog($sql);
		$result = mysql_query($sql,$aem);
		if(mysql_num_rows($result)>0){
			$tokenId = mysql_result($result,0,0);
			if(strlen($value) > 255){
				$sql = "insert into aem_alert_token_longValue (aatl_value) VALUES (".GetSQLValueString($value,'text').")";
				mysql_query($sql,$aem) or handleError("createAlert - token - longValue",mysql_error()." - ".$sql);
				$longValue=mysql_insert_id($aem);
				$value="";
			}
			$sql = "insert into aem_alert_tokens (aat_alert, aat_token, aat_value, aat_long_value) VALUES (".$alertId.",".$tokenId.",".GetSQLValueString($value,'text').",".$longValue.")";
			mysql_query($sql,$aem) or handleError("createAlert - token",mysql_error()." - ".$sql);
		}else{
			aemlog("Token does not exist in aem: ".$name);
		}
	}
	return $alertId;
}

function processAlert($alertId){
	global $aem,$debug;
	#check Blackout
	$sql = "select aat_value from aem_alert_tokens where aat_alert = $alertId and aat_token = (select at_id from aem_tokens where at_name = 'blackout')";
	$result = mysql_query($sql,$aem) or handleError("processAlert - getBlackoutToken",mysql_error());
	$blackout = mysql_result($result,0,0);
	if($blackout == "true"){
		$status="closed";
		$return=false;
	}else{	
		#Check Clear
		$sql = "select aat_value from aem_alert_tokens where aat_alert = $alertId and aat_token = (select at_id from aem_tokens where at_name = 'aem_severity')";
		$result = mysql_query($sql,$aem) or handleError("processAlert - getAemSeverityToken",mysql_error());
		$aem_severity = mysql_result($result,0,0);
		if($aem_severity == "Clear"){
			$status="closed";
			$return=false;
			$match = checkMatch($alertId);
			if($match != false){
				$sql = "update aem_alert set aa_status = 'closed' where aa_id = $match";
				mysql_query($sql,$aem) or handleError("processAlert - updateStatus - $match",mysql_error());
				$return=array("type" => "close", "alertId" => $match);
			}
		}else{
			#Check Duplicate
			$match = checkMatch($alertId);
			if($match != false){
				if($debug) aemlog("match = ".$match);
				$status="closed";
				$return=false;
				#update count
				$sql = "update aem_alert set aa_count = aa_count + 1 where aa_id = $match";
				mysql_query($sql,$aem) or handleError("processAlert - updateAlertCount",mysql_error()." - ".$sql);

				#check if it is a severity change
				$sql = "select aat_value from aem_alert_tokens where aat_alert = $match and aat_token = (select at_id from aem_tokens where at_name = 'aem_severity')";
				$result = mysql_query($sql,$aem) or handleError("processAlert - getAemSeverityToken",mysql_error());
				$current_severity = mysql_result($result,0,0);
				if($debug) aemlog("aem_severity=$aem_severity current_severity=$current_severity");
				if($aem_severity != $current_severity){
					//update match severity or close match and open alert
					if($debug) aemlog("changing severity from $current_severity to $aem_severity");
					$sql = "update aem_alert_tokens set aat_value = '".$aem_severity."' where aat_alert = $match and aat_token = (select at_id from aem_tokens where at_name = 'aem_severity')";
					if($debug) aemlog("updateSeveritySQL: $sql");
					$result = mysql_query($sql,$aem) or handleError("processAlert - updateAemSeverityToken",mysql_error());
					$return=array("type" => "update", "alertId" => $match);
				}
			}else{
				$status="open";
				$return=array("type" => "open", "alertId" => $alertId);
			}
		}
	}
	$sql = "update aem_alert set aa_status = '".$status."' where aa_id = $alertId";
	mysql_query($sql,$aem) or handleError("processAlert - updateStatus - $alertId",mysql_error());

	if($debug) aemlog("status: $status");
	
	return $return;
}

function checkMatch($alertId){
	global $aem, $debug;
	$tokens = getAlertTokens($alertId,"id");
	$sql = "select ac_value from aem_config where ac_name = 'matching_token' order by ac_sequence";
	$result = mysql_query($sql,$aem) or handleError("checkMatch - getMatchingTokenConfig",mysql_error());
	$sql = "select distinct aa_id from aem_alert where aa_status = 'open'";
	while($row = mysql_fetch_assoc($result)){
		if($tokens[$row['ac_value']] == ''){
			$value = "is null";
		}else{
			$value = "= '".$tokens[$row['ac_value']]."'";
		}
		$sql .= " and aa_id in (select aat_alert from aem_alert_tokens where aat_token = ".$row['ac_value']." and aat_value ".$value.")";
	}
	if($debug) aemlog("findMatchesSQL: ".$sql);
	$result = mysql_query($sql,$aem) or handleError("checkMatch - findMatches", mysql_error()." - ".$sql);
	if(mysql_num_rows($result) >0){
		$return = mysql_result($result,0,0);
	}else{
		$return=false;
	}
	return $return;
}

function getSourcePath($source){
	global $aem;
	$sql = "select asp_step from aem_source_path where asp_source = (select asrc_id from aem_source where asrc_name = '".$source."') order by asp_sequence asc";	
	$result = mysql_query($sql,$aem) or handleError("getSourcePath",mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$steps[] = $row['asp_step'];
	}
	return $steps;
}

function getDestPath($source, $type){
	global $aem;
	$sql = "select adp_step from aem_dest_path where adp_type = '".$type."' and adp_source = (select asrc_id from aem_source where asrc_name = '".$source."') order by adp_sequence asc";	
	$result = mysql_query($sql,$aem) or handleError("getDestPath",mysql_error());
	aemlog($sql);
	while($row = mysql_fetch_assoc($result)){
		$steps[] = $row['adp_step'];
	}
	return $steps;
}

function runStep($alertId,$step){
	global $aem,$debug;
        if($debug) aemlog("runStep invoked - alertId: ".$alertId.", step: ".$step);
	# Get step name, action and return token
	$sql = "select as_name, as_action, as_return_token from aem_step where as_id = $step";
	$result = mysql_query($sql,$aem) or handleError("runStep - getStepInfo",mysql_error()." ".$sql);
	$stepInfo = mysql_fetch_assoc($result);
	# Get step config
	$sql = "select asc_token from aem_step_config where asc_step = $step order by asc_sequence";
	$result = mysql_query($sql,$aem) or handleError("runStep - getStepConfig",mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$stepConfig[] = $row['asc_token'];	
	}
	# if action begins with the word "function" run that function else run script
	if($debug) aemlog("running step ".$stepInfo['as_action']); 
	if(substr($stepInfo['as_action'],0,8) == "function"){
		eval("\$returnToken = ".substr($stepInfo['as_action'],8)."(\$alertId,\$step,\$stepConfig);");	
	}else{
		$tokens = getAlertTokens($alertId,"id");
		$tokenValues = "";
		//print_r($tokens);
		foreach($stepConfig as $token){
			$tokenValues .= isset($tokens[$token]) ? '"'.$tokens[$token].'" ' : '"" ';
		}
		$cmd = $stepInfo['as_action']." ".$alertId." ".$tokenValues;
		if($debug) aemlog($cmd);
		$returnToken = exec($cmd,$sysout,$rc);
	}
	# check if the return token is already a long value
	$sql = "select aat_long_value from aem_alert_tokens where aat_alert = ".$alertId." and aat_token = ".$stepInfo['as_return_token']." and aat_long_value is not null";
        if($debug) aemlog("check existing longValue - query: ".$sql);
	$result = mysql_query($sql,$aem) or handleError("runStep - updateReturn - check existing longValue",mysql_error());
	if(mysql_num_rows($result) >0){
		$sql = "delete from aem_alert_token_longValue where aatl_id = ".mysql_result($result,0,0);
		mysql_query($sql,$aem) or handleError("runStep - updateReturn - delete old longValue",mysql_error());
	}
	
	$longValue="NULL";
	if(strlen($returnToken) > 255){
		$sql = "insert into aem_alert_token_longValue (aatl_value) VALUES (".GetSQLValueString($returnToken,'text').")";
		mysql_query($sql,$aem) or handleError("runStep - updateReturn - longValue",mysql_error()." - ".$sql);
		$longValue=mysql_insert_id($aem);
		$returnToken="";
	}
	$sql = "insert into aem_alert_tokens (aat_alert, aat_token, aat_value, aat_long_value) VALUES (".$alertId.",".$stepInfo['as_return_token'].",".GetSQLValueString($returnToken,'text').",".$longValue.") ON DUPLICATE KEY UPDATE aat_value = ".GetSQLValueString($returnToken,'text').", aat_long_value = ".$longValue;
	mysql_query($sql,$aem) or handleError("runStep - updateReturn",mysql_error());
	return true;
}

function translate($alertId,$step,$config){
	global $aem,$debug;
	
	$tokens = getAlertTokens($alertId, 'id');
	$tokenValues = "";
	foreach($config as $token){
		$tokenValues .= isset($tokens[$token]) ? addslashes($tokens[$token]).'|' : addslashes('|');
	}
	$tokenValues = substr($tokenValues,0,strlen($tokenValues)-1);
	$sql = "select atran_value from aem_translation where atran_step = ".$step." and '".$tokenValues."' rlike atran_match order by atran_sequence limit 1";
	$result = mysql_query($sql,$aem) or handleError("translate - getMatch",mysql_error()." ".$sql);
	$return = mysql_result($result,0,0);
	if($debug) aemlog("translateSQL: ".$sql);
	$allTokens=getTokens();
	$tokenNames = getAlertTokens($alertId,'names');
	//$tokenNames['domain']='m074lp01';
	foreach($allTokens as $token){
		if(!isset($tokenNames[$token])) continue;
		$search[] = '%%'.$token.'%%';
		$replace[] = $tokenNames[$token];
	}
	$return = str_replace($search, $replace, $return);
	return $return;
	
}

function blackout($alertId,$step,$config){
	global $aem, $debug;
	
	#get received time
	$sql = "select unix_timestamp(aa_received_time) from aem_alert where aa_id = $alertId";
	$result = mysql_query($sql,$aem) or handleError("translate - getReceivdedTime",mysql_error());
	$receivedTime = mysql_result($result,0,0);
	
	$tokens = getAlertTokens($alertId, 'id');
	$tokenValues = "";
	foreach($config as $token){
		$tokenValues .= addslashes($tokens[$token]).'|';
	}
	$tokenValues = substr($tokenValues,0,strlen($tokenValues)-2);
	$sql = "select atran_value from aem_translation where  atran_step = ".$step." and '".$tokenValues."' rlike atran_match order by atran_sequence limit 1";
	$result = mysql_query($sql,$aem) or handleError("blackout - getMatch",mysql_error());
	$return= "false";
        if($debug) aemlog("blackoutSQL: ".$sql);
	while($row = mysql_fetch_assoc($result)){
		$blackout = explode('|', $row['atran_value']);
		#$blackout[0] is the cron string for start time
		#$blackout[1] is the duration in seconds
                if($debug) aemlog("Blackout cron string: ".$blackout[0].", duration: ".$blackout[1]);
		$cron = new CronParser();
		$cron->calcLastRan($blackout[0]);
		$lastRun=$cron->getLastRanUnix();
		if($debug) aemlog("CronParser LastRan: ".$lastrun);
		if($lastRun <= $receivedTime && $lastRun + $blackout[1] >= $receivedTime){
			$return = "true";
			break;
		}
		unset($cron);
	}
	return $return;
	
}

function getAlertTokens($alertId,$reference = "name"){
	global $aem;
	if($reference == "id"){
		$sql = "select aat_token token, ifnull(aat_value,aatl_value) value from aem_alert_tokens left join aem_alert_token_longValue on aat_long_value = aatl_id where aat_alert = $alertId";
	}else{
		$sql = "select at_name token, ifnull(aat_value,aatl_value) value from aem_alert_tokens left join aem_alert_token_longValue on aat_long_value = aatl_id, aem_tokens where aat_token = at_id and aat_alert = $alertId";
	}
	$result = mysql_query($sql,$aem) or handleError("getAlertTokens - $reference",mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$return[$row['token']] = $row['value'];
	}
	return $return;
}

function acknowledgeAlert($alertId)
{
	global $aem;

	$sql = "update aem_alert set aem_ack_time = NOW() where aa_id = $alertId";
	$result = mysql_query($sql,$aem) or handleError("Acknowledge Alert - $alertId",mysql_error());
}

function getSource($alertId)
{
	global $aem;

	$sql = "select aat_value from aem_alert_tokens, aem_tokens where at_id = aat_token and at_name = 'source' and aa_id = $alertId";
	$result = mysql_query($sql,$aem) or handleError("getSource - $alertId",mysql_error());
	return mysql_result($result,0,0);
}

function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

  switch ($theType) {
    case "text":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;    
    case "long":
    case "int":
      $theValue = ($theValue != "") ? intval($theValue) : "NULL";
      break;
    case "double":
      $theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
      break;
    case "date":
      $theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
      break;
    case "defined":
      $theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
      break;
  }
  return $theValue;
}

#################################################
# FUNCTIONS FOR WEB INTERFACE
#################################################
function getActiveAlerts($orderBy="aa_received_time desc"){
	global $aem, $debug;
	if(empty($orderBy)) $orderBy =  "aa_received_time desc";
	$sql = "select * from aem_alert where aa_status = 'open' order by $orderBy";
	$result = mysql_query($sql,$aem) or handleError("getActiveAlerts - alert",mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$alerts[$row['aa_id']] = array_merge($row,getAlertTokens($row['aa_id'],"name"));
	}
	if($debug){ print "<pre>"; print_r($alerts); print "</pre>"; }
	return $alerts;
}

function getTokens(){
	global $aem;
	$sql="select * from aem_tokens";
	$result = mysql_query($sql,$aem) or handleError("getTokens",mysql_error());
	while($row = mysql_fetch_assoc($result)){
		$return[$row['at_id']] = $row['at_name'];
	}
	return $return;
}

###################
## central manage links
###################
function htmlHead(){
/*	print '<script type="text/javascript" src="/include/lightbox/js/prototype.js"></script>
<script type="text/javascript" src="/include/lightbox/js/scriptaculous.js?load=effects,builder"></script>
<script type="text/javascript" src="/include/lightbox/js/lightbox.js"></script>
<link rel="stylesheet" href="/include/lightbox/css/lightbox.css" type="text/css" media="screen" />';	*/
	print '<script type="text/javascript" src="/include/jquery-1.6.2.min.js"></script>
	<script type="text/javascript" src="/include/thickbox/thickbox.js"></script>
<script type="text/javascript" src="include/aem.js"></script>
	<link rel="stylesheet" href="/include/thickbox/thickbox.css" type="text/css" media="screen" />
	<script language="javascript">	setTimeout("r_u_there(\'on\')",30000);
	</script>';
}
function topOpg(){
	global $adminUser;

	print '<div id="login" style="visibility:hidden; width: 200px; height:150px; background-color: #FFFFFF; border: medium double #666666; display: none;" align="center">
  <form method="post" name="form1" id="form1" onSubmit="login();return false;">
    <p align="center">Username
      <input type="text" name="username" id="username" />
    </p>
    <p align="center">Password
      <input type="password" name="password" id="password" />
    </p>
    <p align="center">
	  <input type="hidden" name="return" value="'.$_SERVER['REQUEST_URI'].'">
      <input type="submit" name="loginButton" id="loginButton" value="Login" onClick="login();return false;" />
    </p>
  </form>
</div>
<div id="msg" style="color: red; text-align:center; font-size:16px;"></div><table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
    <table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>
    <td height="100" align="center" nowrap="nowrap" width="100%"><p><a href="index.php" style="text-decoration:none"><img src="images/anp.gif" border="0" width="210" height="108" /></a><img src="images/eventMgr.gif" width="400" height="108" /><img src="images/crosshairs.gif" width="88" height="88" />AEM</p></td>
    </tr>
  <tr>
    <td align="center">';
	print '<div id="admin" align="center" style="';
	print $adminUser ? "visibility: visible; display: block" : "visibility:hidden;display:none";
	print '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <a href="aemi.php?pg=tokens" class="linktxt">Tokens</a> &nbsp;&nbsp;
      <a href="aemi.php?pg=steps" class="linktxt">Steps</a>&nbsp;&nbsp;
      <a href="aemi.php?pg=source" class="linktxt">Source</a> &nbsp;&nbsp;
      <a href="snmpconfig.php" class="linktxt">SNMP</a> &nbsp;&nbsp;
      <a href="javascript:logout();" class="linktxt">Log out</a></div>';
	print '<div id="guest" align="center" style="';
	print !$adminUser ? "visibility: visible; display: block" : "visibility:hidden;display:none";
	print '"><a href="#TB_inline?height=155&width=300&inlineId=login&modal=true" class="thickbox" onClick="document.getElementById(\'username\').focus();">Log in</a></div>';
	  print '</td>
    </tr>
	  <tr>
    <td align="center">
      <p>&nbsp;';	
}

function bottomOpg(){
	print '      </p></td>
  </tr>
</table>
&nbsp;
   </td>
  </tr>
</table>';
}
?>
