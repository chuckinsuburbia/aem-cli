<?php
function nexusProcess($structure) {
	global $basePath,$debug;
	$arcdir = $basePath."/data/out/archive";
	//logmsg(print_r($structure,TRUE));
	//Save attachments to flat files
	if($structure->ctype_primary == "multipart") {
		foreach($structure->parts as $part) {
			if(isset($part->disposition) && $part->disposition == "attachment") {
				$file = isset($part->d_parameters['filename']) ? $basePath."/data/out/".$part->d_parameters['filename'] : $basePath."/data/out/noname".rand();
				$contents = $part->body;
				if($debug) logmsg("Saving attachment ".$file);
				file_put_contents($file,$contents);
				chmod($file,0660);
				exec("gzip -c ".$file." >".$file.".gz ; mv ".$file.".gz ".$arcdir,$output,$rc);
			}
		}
	}
}
?>
