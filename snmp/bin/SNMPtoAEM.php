#!/usr/bin/php
<?php

//Load environment
$basePath="/in/AEM";
require_once($basePath."/snmp/conf/aem_snmp_conf.php");
require_once($basePath."/lib/aemdb.php");

//Output file name
do {
	$outfile = $basePath."/spool/snmp_".date('YmdHis')."_".rand().".xml";
} while (file_exists($outfile));

//Get input from STDIN
$input = file_get_contents("php://stdin");
$lines = explode("\n",$input);

//Source Host is the first line of SNMP trap
$SNMPsourceHost = array_shift($lines);
file_put_contents($snmplog,"source host = ".$SNMPsourceHost."\n",FILE_APPEND);
$tokens['eventType'] = $SNMPsourceHost;

//take off next 3 lines
array_shift($lines);array_shift($lines);array_shift($lines);

//Loop through remaining lines and fetch valid messages into array 
foreach($lines as $line){
        $firstSpace = strpos($line," ");
        $oidStart = strpos($line,"enterprises.")+12;
        if($oidStart>12 && $firstSpace > $oidStart){
                $oid = substr($line,$oidStart,$firstSpace-$oidStart);
                $value = substr($line,$firstSpace+1);
                $oidValues[$oid] = $value;
        }
}

//Loop through array of messages and look up SNMP field mapping in database
foreach($oidValues as $key=>$val){
        if($debug) file_put_contents($snmplog,$key." = ".$val."\n",FILE_APPEND);
        $sql = "select at_name from aem_snmp_mapping, aem_tokens, aem_snmp_objects where at_id = asm_token and asm_object = aso_id and aso_oid = '".$key."'";
        $result = mysql_query($sql,$aem) or file_put_contents($snmplog,"SNMPtoAEM - getTokenMapping: ".mysql_error(),FILE_APPEND);
        $tokens[mysql_result($result,0,0)] = str_replace('"',"",$val);
}

$tokens['source'] = "SNMP";
$tokens['enterprise'] = substr($key,0,strpos($key, "."));

//if($debug) file_put_contents($snmplog,"TOKENS = ".$tokens."\n",FILE_APPEND);

//exec($aemopen." SNMP ".$tokens." 2>>$snmplog >>$snmplog");
//exec($aemopen." source=SNMP ".$tokens." 2>>$snmplog >>$snmplog");

//Create XML output from tokens
$xml = array('<?xml version="1.0" encoding="UTF-8"?>');
$xml[] = '<alerts>';
$xml[] = '<alert>';
foreach($tokens as $k => $v) {
	$xml[] = "\t<".$k.'>'.$v.'</'.$k.'>';
}
$xml[] = '</alert>';
$xml[] = '</alerts>';
foreach($xml as $l) file_put_contents($outfile,$l."\n",FILE_APPEND);

?>
