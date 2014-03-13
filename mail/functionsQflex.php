<?php
function qflexProcess($body) {
	foreach (explode("\n",$body) as $line) {
		if (strstr($line,"=")) {
			list($k,$v) = explode("=",$line);
			$pairs[$k]=$v;
		}
	}

	$fieldMap['OR'] = "origin";
	$fieldMap['ET'] = "eventType";
	$fieldMap['DC'] = "q_manager_name";
	$fieldMap['D']  = "q_name";
	$fieldMap['OC'] = "q_name";
	$fieldMap['O']  = "trig_name";
	$fieldMap['PV'] = "q_depth";
	$fieldMap['PN'] = "trig_name";
	$fieldMap['S']  = "severity";
	$fieldMap['FT'] = "monitor_name";

	$defaults['OR'] = "QFLEX";
	$defaults['ET'] = "MQ Alert";
	$defaults['S']  = "70";

	foreach	($fieldMap as $k => $v) {
		if(isset($pairs[$k])) {
			$values[$k] = $pairs[$k];
			continue;
		}
		if(isset($pairs[$v])) {
			$values[$k] = $pairs[$v];
			continue;
		}
		if(isset($defaults[$k])) {
			$values[$k] = $defaults[$k];
			continue;
		}
		$values[$k] = "";
	}
	switch ($values['S']) {
		case "Failure": 
			$values['S'] = "100";
			break;
		case "Problem": 
			$values['S'] = "70";
			break;
		case "Warning": 
			$values['S'] = "70";
			break;
		case "Info": 
			$values['S'] = "10";
			break;
	}

	foreach($values as $k => $v) {
		$newpairs[] = $k.'='.$v;
	}
	$newbody = implode(",",$newpairs);
	logmsg($newbody);
	return $newbody;
}
?>
