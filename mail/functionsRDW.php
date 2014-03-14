<?php
function narrowProcess() {
	global $aembase;
	$file = $aembase."/rdw/RDWreport.rec";

	if (!$fp = fopen($file,'a')) {
		logmsg("Unable to open file ".$file);
		return;
	}
	if(fwrite($fp,date('r')) === FALSE) {
		logmsg("Unable to write to file ".$file);
		return;
	}
	logmsg("Wrote timestamp to file ".$file);
	fclose($fp);
}
?>
