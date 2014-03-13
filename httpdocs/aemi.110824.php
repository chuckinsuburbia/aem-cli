<?php include('../lib/aemdb.php'); ?>
<?php include('../lib/functions.php'); ?>
<?php if(isset($_POST['submit'])){
	if(isset($_REQUEST['pg']) && $_REQUEST['pg'] == 'token'){
		$sql="INSERT INTO aem_tokens (`at_id`, `at_name`) VALUES (NULL, '".$_POST['token']."')";
		$qry=mysql_query($sql, $aem);
		header("Location:".$_SERVER['PHP_SELF']."?pg=tokens");
	}
} // end of if isset submit

if(isset($_GET['pg']) && $_GET['pg'] == 'tokens' && isset($_GET['del_id'])){
	$sql="delete from aem_tokens where at_id=".$_GET['del_id']."";
	$qry=mysql_query($sql, $aem);	
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>AEMI - A&amp;P Event Manager Interface</title>
<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/ico" href="/favicon.ico">
<style type="text/css">
body {
	font-family: Tahoma, Geneva, sans-serif;
}
.a_noline {
}
a {
	text-decoration: none;
}
</style>

<?php htmlHead(); ?>
<script language="javascript">
actionUrl="<?php echo $_SERVER['PHP_SELF'] ?>";

function enableScript(){
	if(document.getElementById('step_type').value == "script"){
		document.getElementById("script").style.display='block';		
//		document.getElementById("script").style.visibility='visible';
	}else{
		document.getElementById("script").style.display='none';		
//		document.getElementById("script").style.visibility='hidden';
	}
}
</script>
</head>

<body>
    <?php // load top of page and links from function
		topOpg();
	?>
      <?php if(!isset($_REQUEST['pg'])){ print '<img src="images/4fun.gif" width="696" height="297" />'; }else{ 
	  if($_REQUEST['pg'] == 'tokens'){
		  		print '
				<form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=tokens">
				<table width="30%" cellspacing="0"><tr>
						<td align="right" colspan="2" nowrap="nowrap">
						<label for="token">Add token</label>
						  <input type="hidden" name="pg" value="token" />
						  <input type="text" name="token" id="token" />
						  <input type="submit" name="submit" id="submit" value="Submit" /><br>
						  <i>Renaming or deleting a token may result in alerts not filtering properly.</i>
					    <BR><br></td></tr>';
	  			$sql="SELECT * FROM `aem_tokens` ORDER BY `aem_tokens`.`at_name` ASC";
				$qry=mysql_query($sql, $aem);
				$count=0;
				while($row=mysql_fetch_assoc($qry)){
					if($count % 3 == 2){
						$bgcolor="style=\"border-bottom: 1px solid black;\"";//bgcolor=\"#CCCCCC\"";
					}else{
						$bgcolor="";
					}
					print '<tr><td align="left" '.$bgcolor.'>'.$row['at_name'].' </td><td '.$bgcolor.'><a href="aemi.php?pg=tokens&del_id='.$row['at_id'].'" onClick="return confirm(\'Are you sure you want to delete token '.$row['at_name'].'?\');"> <img src="images/delete.png" border="0" width="16" height="16" /></a></td></tr>'."\n";
					$count++;
				}
				print '</table>
				</form>';
			}
			if($_REQUEST['pg'] == 'steps'){
				if($_POST['TOKstepID']){
					$sql="UPDATE aem_step SET as_return_token = '".$_POST['return_token']."' WHERE as_id =".$_POST['TOKstepID']."";
					$qry=mysql_query($sql, $aem);	
				}
				if($_POST['renaming']){
					$sql="UPDATE aem_step SET as_name = '".$_POST['renaming']."' WHERE as_id =".$_POST['namingID']."";
					//print $sql.'<br>';
					$qry=mysql_query($sql, $aem);
				}
				if($_GET['copying']){
					$sql="INSERT INTO aem_step (`as_name` ,`as_action` ,`as_return_token` ) select concat(`as_name`, '_copy') ,`as_action` ,`as_return_token` from aem_step where as_id=".$_GET['copying']."";	
					$qry=mysql_query($sql, $aem);
					$insertID=mysql_insert_id();
					$sql="INSERT INTO aem_step_config (asc_step, asc_sequence, asc_token) select ".$insertID.", asc_sequence, asc_token from aem_step_config where asc_step=".$_GET['copying']."";
					$qry=mysql_query($sql, $aem);
					$sql="insert into aem_translation (atran_step, atran_sequence, atran_match, atran_value) select ".$insertID.", atran_sequence, atran_match, atran_value from aem_translation where atran_step=".$_GET['copying']."";
					$qry=mysql_query($sql, $aem);
				}
				$message='Script path';
				if(isset($_POST['addStep'])){
					$stepName=$_POST['step_name'];
					$stepPath=$_POST['step_type'];
					$ret_token=$_POST['return_token'];
					if($stepPath == "script"){
						$stepPath=$_POST['script'];
					}
					$sql="INSERT INTO `aem`.`aem_step` (`as_id` ,`as_name` ,`as_action` ,`as_return_token` )VALUES (NULL , '".$_POST['step_name']."', '".$stepPath."', '".$ret_token."')";	
					$qry=mysql_query($sql, $aem);
					$stepID=mysql_insert_id();
				}// end of if addStep
				
				// DELETE THE STEP ///////////////
				if($_GET['delete']){
					$sql="delete from aem_step where as_id='".$_GET['delete']."'";
					$qry=mysql_query($sql, $aem);
					$sql="delete from aem_step_config where asc_step='".$_GET['delete']."'";
					$qry=mysql_query($sql, $aem);
					$sql="delete from aem_translation where atran_step='".$_GET['delete']."'";
					$qry=mysql_query($sql, $aem);	
				} // end of delete step
				
				// DISPLAY STEPS 
				$sql="SELECT * FROM `aem_tokens` order by at_name asc";
				$qry=mysql_query($sql, $aem);
	  			print "<p>&nbsp;</p>";
				print '<table width="50%">';
				// changing the end token : text, service, contact, etc. 
				if(!$_GET['chg_token']){
				  // rename the step	
				  if(!$_GET['rename']){
					print '<tr><td align="center" colspan="4">
						<form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=steps">
							<table width="100%" border="0" cellspacing="0" cellpadding="0">
							  <tr>
								<td><input type="text" name="step_name" value="Name" size="15"/></td>
								<td><select name="step_type" id="step_type" onChange="enableScript();"><option value="">Select Type</option><option value="function blackout">Blackout</option><option value="function translate">Translate</option><option value="script">Script</option></select></td>
								<td><input type="text" name="script" id="script" value="'.$message.'" size="30" style="display:none" /></td>
								<td align="center"><select name="return_token"><option value="">Return Token</option>';
								while($row=mysql_fetch_assoc($qry)){
									print '<option value="'.$row['at_id'].'" >'.$row['at_name'].'</option>';
								}
								print '</select></td>
								<td align="right">&nbsp;
								<input type="submit" name="addStep" id="addStep" value="Add Step" /></td>
							  </tr><tr><td colspan="5"><hr><td><tr>
							</table>
						</form>
						</td><tr>';
				  }else{ // else of NOT rename
					$sql="select as_name from aem_step where as_id=".$_GET['rename'];
					$qry=mysql_query($sql, $aem);
					$res=mysql_result($qry,0,0);
					print '<tr><td align="center" colspan="4"><form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=steps">
						<input type="text" name="renaming" value="'.$res.'" size="15"/> 
						<input type="hidden" name="namingID" value="'.$_GET['rename'].'" />
						<input type="submit" name="RENAMING" value="Rename" />
					</form></td></tr>';
				  } //end of NOT rename "else"
				}else{ //else of if NOT chg_token
					$sql="select * from aem_tokens order by at_name asc";
					$qry=mysql_query($sql, $aem);
					print '<tr><td align="center" colspan="4"><form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=steps">Change return token for...  '.$_GET['stepName'].'&nbsp;&nbsp;&nbsp; <select name="return_token"><option value="">Return Token</option>';
						while($row=mysql_fetch_assoc($qry)){
							print '<option value="'.$row['at_id'].'" >'.$row['at_name'].'</option>';
						}
						print '</select>
						<input type="hidden" name="TOKstepID" value="'.$_GET['chg_token'].'" />
						<input type="submit" name="token_chging" value="Change" />
					</form></td></tr>';
				} //end of if NOT chg_token
				$sql="select * from aem_step order by as_name asc";
				$qry=mysql_query($sql, $aem);
				$tokens = getTokens();
				while($row=mysql_fetch_assoc($qry)){
					print '<tr><td align="left"><a href="stepcfg.php?step='.$row['as_id'].'" class=linktxt>'.$row['as_name'].'</a></td>';
					if($row['as_action'] == 'function translate'){
						print '<td align="left"><a href="translationcfg.php?step='.$row['as_id'].'" class=linktxt>Translate</a></td>';
						print '<td align="left"><a href="aemi.php?pg=steps&chg_token='.$row['as_id'].'&stepName='.$row['as_name'].'">'.$tokens[$row['as_return_token']].'</a></td>';
					}elseif($row['as_action'] == 'function blackout'){
						print '<td align="left"><a href="blackoutcfg.php?step='.$row['as_id'].'" class=linktxt>Blackout</a></td>';
						print '<td align="left"><a href="aemi.php?pg=steps&chg_token='.$row['as_id'].'&stepName='.$row['as_name'].'">'.$tokens[$row['as_return_token']].'</a></td>';
					}else{
						print '<td align="left">'.$row['as_action'].'</td>';
						print '<td align="left"><a href="aemi.php?pg=steps&chg_token='.$row['as_id'].'&stepName='.$row['as_name'].'">'.$tokens[$row['as_return_token']].'</a></td>';
					}// end of if
					print '<td nowrap="nowrap">&nbsp; <a href="aemi.php?pg=steps&rename='.$row['as_id'].'"><img src="images/rename.gif" width="16" height="16" title="Rename Step" border="0" /></a>&nbsp;<a href="aemi.php?pg=steps&copying='.$row['as_id'].'"><img src="images/copy2.gif" width="16" height="16" title="Copy Step" border="0" /></a> &nbsp;<a href="aemi.php?pg=steps&delete='.$row['as_id'].'" onclick="return confirm(\'Deleting '.$row['as_name'].'\');" ><img src="images/delete.png" width="16" height="16" border="0" title="Delete Step" /></a></td>';
					print '</tr>';	
				}// end of while
				print '</table>';
			}// end of if pg steps
			
			// DISPLAY SOURCE PAGE ////////////////////////////
			if($_REQUEST['pg'] == 'source'){
				
				if($_GET['delete']){
					$sql="delete from aem_source where asrc_id=".$_GET['delete'];
					$qry=mysql_query($sql, $aem);
					$sql="delete from aem_source_path where asp_source=".$_GET['delete'];
					$qry=mysql_query($sql, $aem);
				} // end of DELETE 
				
				if($_GET['copying']){
					$sql="INSERT INTO aem_source (`asrc_name`) select concat(`asrc_name`, '_copy') from aem_source where asrc_id=".$_GET['copying']."";	
					$qry=mysql_query($sql, $aem);
					$insertID=mysql_insert_id();
					$sql="INSERT INTO aem_source_path (asp_source, asp_sequence, asp_step) select ".$insertID.", asp_sequence, asp_step from aem_source_path where asp_source=".$_GET['copying']."";
					$qry=mysql_query($sql, $aem);
				}// END OF COPY
				
			  if(!$_GET['rename']){
				if(isset($_POST['RENAMING'])){
					$sql="UPDATE `aem`.`aem_source` SET `asrc_name` = '".$_POST['renaming']."' WHERE `aem_source`.`asrc_id` =".$_POST['namingID']."";
					$qry=mysql_query($sql, $aem);
				}
				if(isset($_POST['scr_submit'])){
					$sql="insert into aem_source (asrc_id, asrc_name) values (NULL, '".$_POST['srcPath']."')";	
					print $sql.'<br>';
					$qry=mysql_query($sql, $aem);
					$srcID=mysql_insert_id();
					$sql="insert into aem_source_path (asp_source, asp_sequence, asp_step) values ('".$srcID."', '1', (SELECT as_id FROM `aem_step` order by as_id limit 1))"; 
					$qry=mysql_query($sql, $aem);
				}
	  			print "<p>&nbsp;</p>";
				print '<table style="border-bottom: 1px solid black;padding-bottom:5px;"><tr><td align="center" colspan="3">';
				print '<form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=source">
						New Source: &nbsp;<input type="text" name="srcPath" />&nbsp;&nbsp;
						<input type="submit" name="scr_submit" value="Submit" />
						</form></td></tr>';
				$sql="SELECT distinct asrc_id, asrc_name FROM aem_source as asx, aem_source_path as asp where asx.asrc_id=asp.asp_source order by asx.asrc_name asc";
				$qry=mysql_query($sql, $aem);
				while($row=mysql_fetch_assoc($qry)){
					$spsql="SELECT * FROM aem_source_path as asp, aem_step as asx, aem_source as asrc where asp.asp_source=".$row['asrc_id']." and asrc.asrc_id=".$row['asrc_id']." and asp.asp_step=asx.as_id order by asp.asp_sequence ";
					$spqry=mysql_query($spsql, $aem);
					$stepnames='';
					while($asrow=mysql_fetch_assoc($spqry)){
						$stepnames.=' '.$asrow['as_name'].' > ';
					}
					$seq_name=$row['asrc_name'];
//					$stepnames.=' [ DestPath ] ';
					$sdsql="SELECT * FROM aem_dest_path as adp, aem_step as asx, aem_source as asrc where adp.adp_source=".$row['asrc_id']." and asrc.asrc_id=".$row['asrc_id']." and adp.adp_step=asx.as_id order by adp_type, adp_sequence";
					$sdqry=mysql_query($sdsql, $aem);
					$deststeps=array();
					while($adrow=mysql_fetch_assoc($sdqry)){
						$deststeps[$adrow['adp_type']].=' '.$adrow['as_name'].' > ';
					}

					print '<tr><td colspan="3" style="border-top: 1px solid black;padding-top:5px;"><span style="display:inline; float:left;">'.$seq_name.'</span><span style="display:inline; float:right;"><a href="aemi.php?pg=source&rename='.$row['asrc_id'].'&name='.$seq_name.'" onclick="return confirm(\'Renaming '.$seq_name.'\');" ><img src="images/rename.gif" width="16" height="16" title="Rename Source" border="0" /></a>&nbsp;<a href="aemi.php?pg=source&copying='.$row['asrc_id'].'" onclick="return confirm(\'Copying '.$seq_name.'\');" ><img src="images/copy2.gif" width="16" height="16" title="Copy Source" border="0" /></a> &nbsp;<a href="aemi.php?pg=source&delete='.$row['asrc_id'].'" onclick="return confirm(\'Deleting '.$seq_name.'\');" ><img src="images/delete.png" width="16" height="16" border="0" title="Delete Source" /></a></span></td></tr>
					<tr><td align="left"><a href="sourcecfg.php?source='.$row['asrc_id'].'">Inbound</a></td><td colspan="2" align="left"> &nbsp;&nbsp;= '.$stepnames.'</td></tr>'."\n";	
					print '<tr><td align="left"><a href="destcfg.php?source='.$row['asrc_id'].'&type=open">Open</a></td><td colspan="2" align="left"> &nbsp;&nbsp;= '.$deststeps['open'].'</td></tr>'."\n";
					print '<tr><td align="left"><a href="destcfg.php?source='.$row['asrc_id'].'&type=update">Update</a></td><td colspan="2" align="left"> &nbsp;&nbsp;= '.$deststeps['update'].'</td></tr>'."\n";
					print '<tr><td align="left"><a href="destcfg.php?source='.$row['asrc_id'].'&type=close">Close</a></td><td colspan="2" align="left"> &nbsp;&nbsp;= '.$deststeps['close'].'</td></tr>'."\n";
				}
				print '</table>';
			  }else{ // else rename source 
				  print '<form id="form1" name="form1" method="post" action="'.$_SERVER['PHP_SELF'].'?pg=source">
						<input type="text" name="renaming" value="'.$_GET['name'].'" size="15"/> 
						<input type="hidden" name="namingID" value="'.$_GET['rename'].'" />
						<input type="submit" name="RENAMING" value="Rename" />
					</form>';
			  } // end of the rename else 
			} // END OF Source ///////////////
			if($_REQUEST['pg'] == 'search'){
	  			print '<p>&nbsp;</p>Search with a magnafier?';
			}
			if($_REQUEST['pg'] == 'lio'){
	  			print "<p>&nbsp;</p>In or out?";
			}
	  }
	bottomOpg();	  
	  ?>
</body>
</html>