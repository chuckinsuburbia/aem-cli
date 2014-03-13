<?php
function tlogProcess($body) {
	foreach(explode("\n",$body) as $row) {
		$row = preg_replace("/\s+/","",$row);
		if(!strstr($row,"--START DATA--") && strstr($row,",")) {
			list($store,$time) = explode(",",$row);
			$url = $emePost."?store=".$store."&timestamp=".$time;
			//$post = file_get_contents($url);
			logmsg($url);
		}
	}	
}
?>
