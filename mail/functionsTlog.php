<?php
function tlogProcess($body) {
	global $emePost,$debug;
	foreach(explode("\n",$body) as $row) {
		if($debug) logmsg($row);
		$s = false;
		$row = preg_replace("/\s+/","",$row);
		if($s && strstr($row,",")) {
			list($store,$time) = explode(",",$row);
			$url = $emePost."?store=".$store."&timestamp=".$time;
			if($debug) logmsg($url);
			$post = file_get_contents($url);
		}
		if(strstr($row,"---START---DATA---")) { 
			if($debug) logmsg("Start of data found");
			$s = true; 
		}
	}	
}
?>
