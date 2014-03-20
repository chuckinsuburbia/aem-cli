<?php
function emsAlarmProcess($body,$subject) {
	//ini_set('mbstring.substitute_character', "none");
  	//$conv_body= mb_convert_encoding($body, 'UTF-8', 'UTF-8'); 
	$conv_body = iconv("ISO-8859-1", "UTF=8//TRANSLIT", $body);

	$lines = explode("\n",$conv_body);
	foreach($lines as $k => $v) {
		if(strstr($v,"Subject: ")) { $i = $k; }
	}

	$severity	= "70";
	$domain		= "DanFoss";
	$domainClass	= trim(substr($lines[$i],14,20));
	$origin		= trim(substr($lines[$i+3],0,strpos($lines[$i+2]," ")));
	$objectClass	= trim($lines[$i+4]);
	$object		= trim($lines[$i+5]);
	$paramName	= trim(substr($lines[$i+6],4));
	$paramValue	= trim($lines[$i+8]);
	$freeText	= trim($lines[$i+9]);

	$newbody = "OR=".$origin.",DC=".$domainClass.",D=".$domain.",OC=".$objectClass.",O=".$object.",PN=".$paramName.",PV=".$paramValue.",S=".$severity.",FT=".$freeText;

	logmsg($newbody);
	return $newbody;
}
?>
