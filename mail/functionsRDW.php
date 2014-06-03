<?php
function narrowProcess($structure,$body) {
	global $aembase;
	switch(true) {
		case(strstr($structure->headers['subject'],"Success, Cache update notification: Report - C&S Warehouse Shipment Margins Detail")):
			$file = $aembase."/rdw/RDWmarginsDetail.rec";
			break;
		case(strstr($structure->headers['subject'],"Success, Cache update notification: Report - C&S Warehouse Shipment Margins")):
			$file = $aembase."/rdw/RDWmargins.rec";
			break;
		case(strstr($structure->headers['subject'],"Daily Sales Market Area Total Report")):
			$file = $aembase."/rdw/RDWreport.rec";
			break;
		default:
			return;
	}

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
