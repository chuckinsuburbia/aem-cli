<?php 
include("../lib/functions.php");
include("../lib/aemdb.php");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>SNMP Config</title>
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/ico" href="/favicon.ico">
<style type="text/css">
.linktxt {
	font-family: Tahoma, Geneva, sans-serif;
}
.a_noline {
}
a {
	text-decoration: none;
}
</style>
<script language="javascript">
actionUrl="<?php echo $_SERVER['PHP_SELF'] ?>";
</script>
<?php htmlHead(); ?>
</head>

<body>
    <?php // load top of page and links from function
topOpg();
if($_POST['action'] == "upload" || $_GET['update'] == "true"){
	$uploaddir = '/in/AEM/snmp/mibs/';
	if($_POST['action'] == "upload"){
		$file = str_replace(array(".txt",".mib"),"",basename($_FILES['mibfile']['name'])).".txt";
	}else{
		$sql = "select asmf_name from aem_snmp_mibfiles where asmf_id = ".$_GET['id'];
		$result = mysql_query($sql, $aem) or die(mysql_error());
		$file = mysql_result($result,0,0);
	}
	$uploadfile = $uploaddir . $file;
	
	if ($_GET['update'] == "true" || move_uploaded_file($_FILES['mibfile']['tmp_name'], $uploadfile)) {
		if(empty($_POST['mibname'])){
			$mibname = str_replace(array(".txt",".mib"),"",$file);
		}else{
			$mibname = $_POST['mibname'];
		}
		
		//echo "/usr/bin/snmptranslate -Lo -To -m $mibname -M$uploaddir:/usr/share/snmp/mibs 2>&1 <br>";
		exec("/usr/bin/snmptranslate -Lo -To -m $mibname -M$uploaddir:/usr/share/snmp/mibs",$oids);
		$missingMibs = array();
		foreach($oids as $oid){
			if(substr($oid,0,18) == "Cannot find module"){
				$missingMibs[] = array_pop(split('\(',array_shift(split('\)',$oid))));
			}
		}
		if(sizeof($missingMibs) > 0){
			$error = "The following mibs are missing, please upload:<br><br>";
			foreach($missingMibs as $mib){
				$error .= $mib."<br>";
			}
		}else{
			exec("/usr/bin/snmptranslate -Ts -m $mibname -M$uploaddir:/usr/share/snmp/mibs",$names);
			#strip off up to enterprises
			while(substr($names[0],strlen($names[0])-11) != "enterprises"){
				array_shift($names);
				array_shift($oids);
			}
			array_shift($names);
			array_shift($oids);

			$enterpriseName = array_pop(split('\.',array_shift($names)));
			$enterpriseNum = array_pop(split('\.',array_shift($oids)));
			print "<h2>Processing Mib<br>Enterprise: ".$enterpriseName." (".$enterpriseNum.")<br />";
			$startName = array_shift($names);
			$productName = array_pop(split('\.',$startName));
			$startOID = array_shift($oids);
			$productNum = array_pop(split('\.',$startOID));
			print "Product: ".$productName." (".$productNum.")</h2>";
			#strip off bottom of list
			while(substr($oids[sizeof($oids)-1],0,strlen($startOID)) != $startOID){
				array_pop($names);
				array_pop($oids);
			}
			
			#insert mibfile and objects into db
			$sql = "insert into aem_snmp_mibfiles (`asmf_name`, `asmf_enterprise_name`, `asmf_enterprise_num`, `asmf_product_name`, `asmf_product_num`) VALUES ('".$mibname."','".$enterpriseName."',".$enterpriseNum.",'".$productName."',".$productNum.") ON DUPLICATE KEY UPDATE asmf_name = '".$mibname."', asmf_enterprise_name = '".$enterpriseName."', asmf_product_name = '".$productName."'";
			mysql_query($sql, $aem) or die(mysql_error());
			$sql = "select asmf_id from aem_snmp_mibfiles where asmf_enterprise_num = ".$enterpriseNum." and asmf_product_num = ".$productNum;
			$result = mysql_query($sql,$aem) or die(mysql_error());
			$mibfile_id = mysql_result($result,0,0);
			
			for($i=0; $i<sizeof($oids); $i++){
				$sql = "insert into aem_snmp_objects (`aso_mib`, `aso_oid`, `aso_name`) VALUES (".$mibfile_id.",'".str_replace(".1.3.6.1.4.1.","",$oids[$i])."','".str_replace(".iso.org.dod.internet.private.enterprises.","",$names[$i])."') ON DUPLICATE KEY UPDATE aso_mib = ".$mibfile_id.", aso_name = '".str_replace(".iso.org.dod.internet.private.enterprises.","",$names[$i])."'";
				mysql_query($sql, $aem) or die(mysql_error());
				$sql = "select aso_id from aem_snmp_objects where aso_oid = '".str_replace(".1.3.6.1.4.1.","",$oids[$i])."'";
				$result = mysql_query($sql,$aem) or die(mysql_error());
				$object_ids[$i] = mysql_result($result,0,0);
			}
			//print "<pre>";print_r($object_ids);print "</pre>";
			$sql = "select * from aem_tokens order by at_name asc";
			$result = mysql_query($sql, $aem) or die(mysql_error());
			$options="<option value=\"NULL\">None</option>\n";
			while( $row = mysql_fetch_assoc($result)){
				$options .= "<option value=\"".$row['at_id']."\">".$row['at_name']."</option>\n";
			}
			$sql = "select asm_object, asm_token from aem_snmp_mapping, aem_snmp_objects where asm_object = aso_id and aso_mib = ".$mibfile_id;
			$result = mysql_query($sql, $aem) or die(mysql_error());

			while( $row = mysql_fetch_assoc($result)){
				$selected[$row['asm_object']] = $row['asm_token'];
			}
			print "<form name=\"oidmap\" method=\"post\" action=\"".$_SERVER['PHP_SELF']."\">\n";
			print "<table cellspacing=\"0\"><tr><th>Object ID</th><th>Object Name</th><th>Mapped Token</th></tr>";
			for($i=0; $i<sizeof($oids); $i++){
				if($i % 3 == 2){
					$bgcolor="style=\"border-bottom: 1px solid black;\"";//bgcolor=\"#CCCCCC\"";
				}else{
					$bgcolor="";
				}

				print "<tr><td align=\"left\" ".$bgcolor.">".str_replace($startOID.".","",$oids[$i])."<input type=\"hidden\" name=\"object_ids[".$i."]\" value=\"".$object_ids[$i]."\"></td>";
				print "<td align=\"left\" ".$bgcolor.">".str_replace($startName.".","",$names[$i])."</td>";
				$newoptions = str_replace("value=\"".$selected[$object_ids[$i]]."\"","value=\"".$selected[$object_ids[$i]]."\" selected=\"selected\"",$options);
				print "<td ".$bgcolor."><select name=\"tokens[".$i."]\">".$newoptions."</select></td></tr>";
			}
			print "  <input name=\"action\" type=\"hidden\" value=\"map\" /></table><input type=\"submit\" value=\"Map it!\" name=\"submit\"></form></body></html>";
			die();
		}
	} else {
		echo '<pre>';
		echo "Error uploading file\n";
		print_r($_FILES);
		echo "</pre>";
	}
	
}elseif($_POST['action'] == "map"){
	#insert into toe snmp mapping table
	for($i=0; $i<sizeof($_POST['object_ids']); $i++){
		$sql = "insert into aem_snmp_mapping (`asm_object`, `asm_token`) VALUES (".$_POST['object_ids'][$i].",".$_POST['tokens'][$i].") ON DUPLICATE KEY UPDATE asm_token = ".$_POST['tokens'][$i];
		mysql_query($sql, $aem) or die(mysql_error());
	}
	print "<h3>MIB Mapped Successfully!</h3>\n";
}else{
	$sql = "select * from aem_snmp_mibfiles order by asmf_name";
	$result = mysql_query($sql, $aem) or die(mysql_error());
?>
<?php echo $error ?>
<form id="form1" name="form1" enctype="multipart/form-data" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>">
  Mib
  <input type="file" name="mibfile" id="mibfile" />
  <input name="action" type="hidden" value="upload" />
  <input name="mibname" type="hidden" value="<?php echo $mibname ?>" />  <input name="" type="submit" />
</form>
</p>
<table>
<?php while($row = mysql_fetch_assoc($result)){ ?>
<tr><td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?update=true&id=<?php echo $row['asmf_id'] ?>"><?php echo $row['asmf_name'] ?></a></td><td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?del_id=<?php echo $row['asmf_id'] ?>" onClick="return confirm('Are you sure you want to delete MIB <?php echo $row['asmf_name'] ?>?');"> <img src="images/delete.png" border="0" width="16" height="16" /></a></td></tr>
<?php } ?>
</table>
<?php
}
bottomOpg();
?>
</body>
</html>
