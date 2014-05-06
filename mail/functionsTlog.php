<?php
function tlogProcess($body) {
	global $emePost;
	foreach(explode("\n",$body) as $row) {
		$s = false;
		$row = preg_replace("/\s+/","",$row);
		if($s && strstr($row,",")) {
			list($store,$time) = explode(",",$row);
			$url = $emePost."?store=".$store."&timestamp=".$time;
			$post = file_get_contents($url);
			logmsg($url);
		}
		if(strstr($row,"---START---DATA---")) { $s = true; }
	}	
}
?>
