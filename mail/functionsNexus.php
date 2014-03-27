<?php
function nexusProcess($structure) {
	global $basePath;

	//Save attachments to flat files
	if($structure->ctype_primary == "multipart") {
		foreach($structure->parts as $part) {
			if(isset($part->disposition) && $part->disposition == "attachment") {
				$file = isset($part->d_parameters['filename']) ? $basePath."/data/out/".$part->d_parameters['filename'] : $basePath."/data/out/noname".rand();
				$contents = $part->body;
				if($debug) logmsg("Saving attachment ".$file);
				file_put_contents($file,$contents);
			}
		}
	}
}
?>
