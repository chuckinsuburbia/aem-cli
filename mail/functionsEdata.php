<?php
function edataProcess($structure) {
	global $basePath,$debug;
	$outdir = $basePath."/data/out/edata";
	$arcdir = $outdir."/archive";
	//logmsg(print_r($structure,TRUE));
	//Save attachments to flat files
	if($structure->ctype_primary == "multipart") {
		foreach($structure->parts as $part) {
			if(isset($part->disposition) && $part->disposition == "attachment") {
				if (isset($part->d_parameters['filename'])){
					$file = $outdir."/".preg_replace('/\s+/','_',$part->d_parameters['filename']);
				}
				if (isset($file)) {
					$contents = $part->body;
					if($debug) logmsg("Saving attachment ".$file);
					file_put_contents($file,$contents);
					chmod($file,0660);
					exec("gzip -c \"".$file."\" >\"".$file.".gz\" ; mv \"".$file.".gz\" ".$arcdir,$output,$rc);
					unset($file);
				}
			}
		}
	}
}
?>
