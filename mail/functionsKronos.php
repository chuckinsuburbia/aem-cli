<?php
function kronosClockProcess($body) {
	global $aembase;
	if (file_exists($aembase."/conf/KRONOS_REBOOT.txt")) return;

	$newbody = "";

	$pattern = "Device has not communicated since the last server restart.";
	//$pattern = "Device communication delay.";
	if(substr_count($body,$pattern) > 10) {
		$newbody="OR=KRONOS,S=70,DC=TIMECLOCK,D=MASSIVE,ET=KRONOS,O=CLOCKDOWN,OC=TIMECLOCK,PN=COMMUNICATION,PV=DOWN";
	} else {
		$conditions = explode("\r\n\r\n",$body);
		foreach ($conditions as $condition) {
			if(strstr($condition,$pattern)) {
				$device = substr($condition,strpos($condition,"Device: ")+9,5);
				$newbody.="OR=KRONOS,S=70,DC=TIMECLOCK,D=".$device.",ET=KRONOS,O=CLOCKDOWN,OC=TIMECLOCK,PN=COMMUNICATION,PV=DOWN\n";
			}
		}
	}

	logmsg($newbody);
	return $newbody;
}
?>
