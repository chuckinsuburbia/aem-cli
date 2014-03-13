<?php
function googleVoiceProcess() {
	global $aembase;
	//$cmd = $aembase.'/APAgent/bin/APClient.bin --map-data AEM "IMT" 5 "1234" "IMT" "IMT" "IMT" "IMT" "IMT" "IMT" "A voicemail has been received for the Incident Management Team. Please Accept this alert to stop escalation and check the voicemail."';
	$cmd = $aembase.'/APAgent/bin/APClient.bin --map-data AEM "AEMTest" 5 "1234" "IMT" "IMT" "IMT" "IMT" "IMT" "IMT" "A voicemail has been received for the Incident Management Team. Please Accept this alert to stop escalation and check the voicemail."';
	exec($cmd,$output,$rc);
	if($rc == 0) {
		logmsg("Submitted Alarmpoint alert.");
	} else {
		logmsg("Error submitting Alarmpoint alert: ".$output);
	}
}
?>
