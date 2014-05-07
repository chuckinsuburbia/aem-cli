<?php
function SCProcess() {
	global $aembase;
	$file = $aembase."/anp/scMonitor/email.rec";
	logmsg("Touch ".$file);
	touch($file);
}
?>
