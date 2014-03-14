<?php
function elertProcess($body) {
	$pattern="/\bIM[0-9]+\b/";
	preg_match($pattern,$body,$matches);
	$ticket = $matches[0];

	global $aembase;
	$file = $aembase."/elerts/".$ticket;
	logmsg("Touch ".$file);
	touch($file);
}
?>
