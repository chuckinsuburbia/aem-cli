#!/usr/bin/php
<?php
$local=true;
$basepath=getenv("AEMBASE");
require_once($basepath."/lib/functions.php");
require_once($basepath."/conf/config.php");

$type=$argv[1];
$file=$argv[2];
$lines = file($file);
$count=1;
switch($type){
	case "text":	
		$sql = "delete from aem_translation where atran_step = 3";
		mysql_query($sql,$aem) or die(mysql_error());
		foreach($lines as $line){
			if(substr($line,0,1) != "#"){
				$fields = split('\|',$line);
				if(sizeof($fields) == 7){
					$objectClass = trim($fields[0]);
					$object = trim($fields[1]);
					$parameterName = trim($fields[2]);
					$parameterValue = trim($fields[3]);
					$domain = trim($fields[4]);
					$enterprise = array_pop(split('\.',trim($fields[5])));
					$domainClass = "*";
					$itMgmtLayer = "*";
					$eventType = "*";
					$text = str_replace(array("[T:","]"),"%%",trim($fields[6]));
					$text = str_replace("%%parameter%%","%%parameterName%%",$text);
					$translation = "(".$domainClass.")\|(".$domain.")\|(".$objectClass.")\|(".$object.")\|(".$eventType.")\|(".$parameterName.")\|(".$itMgmtLayer.")\|(".$parameterValue.")\|(".$enterprise.")";
					$translation = str_replace("*",".*",$translation);
					$sql = "insert into aem_translation values ('',3,$count,".GetSQLValueString($translation,'text').",".GetSQLValueString($text,'text').")";
					mysql_query($sql,$aem) or die(mysql_error());
					print $sql."\n";
					$count++;
				}
			}
		}
		break;
	case "service":
		$sql = "delete from aem_translation where atran_step = 4";
		mysql_query($sql,$aem) or die(mysql_error());
		foreach($lines as $line){
			if(substr($line,0,1) != "#"){
				$fields = split('\|',$line);
				if(sizeof($fields) == 10){
					$domainClass = trim($fields[0]);
					$domain = trim($fields[1]);
					$objectClass = trim($fields[2]);
					$object = trim($fields[3]);
					$eventType = trim($fields[4]);
					$parameterName = trim($fields[5]);
					$itMgmtLayer = trim($fields[6]);
					$service = trim($fields[7]);
					$svc = file_get_contents("http://controlm/bip/scexport.php?type=link&group=$service");
					if(empty($svc)) $svc=$service;
					$translation = "(".$domainClass.")\|(".$domain.")\|(".$objectClass.")\|(".$object.")\|(".$eventType.")\|(".$parameterName.")\|(".$itMgmtLayer.")";
					$translation = str_replace("*",".*",$translation);
					$sql = "insert into aem_translation values ('',4,$count,".GetSQLValueString($translation,'text').",".GetSQLValueString($svc,'text').")";
					mysql_query($sql,$aem) or die(mysql_error());
					print $sql."\n";
					$count++;
				}
			}
		}
		break;
	case "severity":
		$sql = "delete from aem_translation where atran_step = 30";
		mysql_query($sql,$aem) or die(mysql_error());
		foreach($lines as $line){
			if(substr($line,0,1) != "#"){
				$fields = split('\|',$line);
				if(sizeof($fields) == 10){
					$domainClass = trim($fields[0]);
					$domain = trim($fields[1]);
					$objectClass = trim($fields[2]);
					$object = trim($fields[3]);
					$eventType = trim($fields[4]);
					$parameterName = trim($fields[5]);
					$itMgmtLayer = trim($fields[6]);
					$service = trim($fields[7]);
					$severity = trim($fields[8]);
					switch($severity){
						case "Y":
							$aem_severity = "Critical";
							break;
						case "N":
							$aem_severity = "Warning";
							break;
						default:
							$aem_severity=false;
					}
					if($aem_severity){
						$translation = "(".$domainClass.")\|(".$domain.")\|(".$objectClass.")\|(".$object.")\|(".$eventType.")\|(".$parameterName.")\|(".$itMgmtLayer.")";
						$translation = str_replace("*",".*",$translation);
						$sql = "insert into aem_translation values ('',30,$count,".GetSQLValueString($translation,'text').",".GetSQLValueString($aem_severity,'text').")";
						mysql_query($sql,$aem) or die(mysql_error());
						print $sql."\n";
						$count++;
					}
				}
			}
		}
		break;			
}

?>
