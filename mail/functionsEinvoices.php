<?php
function einvoiceProcess($structure) {
	global $basePath,$debug;
	//logmsg(print_r($structure,TRUE));
	//Save attachments to flat files
	if($structure->ctype_primary == "multipart") {
		foreach($structure->parts as $part) {
			if(isset($part->disposition) && $part->disposition == "attachment") {
				unset($file);
				if (isset($part->d_parameters['filename'])){
					if (preg_match("/.pdf$/i",$part->d_parameters['filename'])) {
						do { $file = $basePath."/data/out/BW_".substr(md5(rand()), 0, 36)."_".date('YmdHis').".pdf.BWPDF"; while (file_exists($file));
					} elseif (preg_match("/.csv$/i",$part->d_parameters['filename']) && (preg_match("/^A\&P_FLATFILE/i",$part->d_parameters['filename']))) {
						$file = $basePath."/data/out/".$part->d_parameters['filename'];
					}
				}

				if (isset($file)) {
					$contents = $part->body;
				
					if($debug) logmsg("Saving attachment ".$file);
					file_put_contents($file,$contents);
					chmod($file,0660);
				}
			}
		}
	}
}
?>
