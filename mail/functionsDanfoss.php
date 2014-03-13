<?php
function emsAlarmProcess($body,$subject) {
	//ini_set('mbstring.substitute_character', "none");
  	//$conv_body= mb_convert_encoding($body, 'UTF-8', 'UTF-8'); 
	$conv_body = iconv("ISO-8859-1", "UTF=8//TRANSLIT", $body);

	$lines = explode("\n",$conv_body);

	$severity	= "70";
	$domain		= "DanFoss";
	$domainClass	= $subject;
	$origin		= trim(substr($lines[0],0,strpos($lines[0]," ")));
	$objectClass	= trim($lines[1]);
	$object		= trim($lines[2]);
	$paramName	= trim(substr($lines[3],4));
	$paramValue	= trim($lines[5]);
	$freeText	= trim($lines[6]);

	$newbody = "OR=".$origin.",DC=".$domainClass.",D=".$domain.",OC=".$objectClass.",O=".$object.",PN=".$paramName.",PV=".$paramValue.",S=".$severity.",FT=".$freeText;

	logmsg($newbody);
	return $newbody;
}
?>
