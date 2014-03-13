<?php
function sanErrorProcess($body) {
	foreach (explode("\n",$body) as $line) {
		if (strstr($line,": ")) {
			list($k,$v) = explode(": ",$line);
			$pairs[$k]=trim($v);
		}
	}

	$fieldMap['OR'] = "origin";
	$fieldMap['ET'] = "eventType";
	$fieldMap['DC'] = "Component type";
	$fieldMap['D']  = "Node ID";
	$fieldMap['OC'] = "objectClass";
	$fieldMap['O']  = "Component location";
	$fieldMap['PV'] = "Event Error Code";
	$fieldMap['PN'] = "Event Error Code";
	$fieldMap['S']  = "severity";
	$fieldMap['FT'] = "Event Message";

	$defaults['OR'] = "SAN";
	$defaults['ET'] = "Storage Manager Alert";
	$defaults['DC'] = "SAN Storage";
	$defaults['OC'] = "SAN";
	$defaults['O']  = "Unspecified";
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
