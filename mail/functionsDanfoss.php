<?php
function emsAlarmProcess($body,$subject) {
	//ini_set('mbstring.substitute_character', "none");
  	//$conv_body= mb_convert_encoding($body, 'UTF-8', 'UTF-8'); 
	$conv_body = iconv("ISO-8859-1", "UTF=8//TRANSLIT", $body);
	$body=str_replace("Auto forwarded by a Rule","",$body);
	$body=preg_replace('/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n", $body);

	$lines = explode("\n",$conv_body);
	foreach($lines as $k => $v) {
		if(strstr($v,"Subject: ")) { $i = $k; }
	}

	$severity	= "70";
	$domain		= "DanFoss";
	$domainClass	= trim(substr($lines[$i],13,20));
	$origin		= trim(substr($lines[$i+2],0,strpos($lines[$i+2]," ")));
	$objectClass	= trim($lines[$i+3]);
	$object		= trim($lines[$i+4]);
	$paramName	= trim(substr($lines[$i+5],strpos($lines[$i+5],"SI: ")+4));
	$paramValue	= trim($lines[$i+7]);
	$freeText	= trim($lines[$i+8]);

	$newbody = "OR=".$origin.",DC=".$domainClass.",D=".$domain.",OC=".$objectClass.",O=".$object.",PN=".$paramName.",PV=".$paramValue.",S=".$severity.",FT=".$freeText;

	logmsg($newbody);
	return $newbody;
}
?>
