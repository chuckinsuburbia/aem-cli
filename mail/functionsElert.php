<?php
function elertProcess($body) {
	$pattern="/\bIM[0-9]+\b/";
	preg_match($pattern,$body,$matches);
	$ticket = $matches[0];

	global $aembase;
	$file = $aembase."/elerts/emails/".$ticket;
	logmsg("Touch ".$file);
	touch($file);

	$fields['severity'] = "100";
	$lines = explode("\n",$body);
	logmsg(print_r($lines,TRUE));
	for($i = 0; $i < count($lines); ++$i) {
		switch(true) {
			case (trim($lines[$i]) == "Alert Closed:"):
				$fields['severity'] = "10";
				break;
			case (trim($lines[$i]) == "Store:"):
				$fields['store'] = trim(substr($lines[$i],strpos($lines[$i],"Store:")+6));
				break;
			case (trim($lines[$i]) == "Type Of Emergency:"):
				$fields['type'] = strstr($lines[$i+2]," ",TRUE);
				break;
			case (strstr($lines[$i],"Location: ")):
				$fields['location'] = trim(substr($lines[$i],strpos($lines[$i],"Location: ")+10));
				break;
			case (trim($lines[$i]) == "Issue Description:"):
				$fields['description'] = $lines[$i+2];
				break;
		}
	}
	$xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
	$xml .= "<alerts><alert>\n";
	$xml .= "\t<origin>".$fields['store']."</origin>\n";
	$xml .= "\t<domain>".$fields['store']."</domain>\n";
	$xml .= "\t<originSeverity>".$fields['severity']."</originSeverity>\n";
	$xml .= "\t<source>emergency_alert</source>\n";
	$xml .= "\t<eventType>emergency_alert</eventType>\n";
	$xml .= "\t<objectClass>".$fields['type']."</objectClass>\n";
	$xml .= "\t<domainClass>".$fields['type']."</domainClass>\n";
	$xml .= "\t<object>".$fields['location']."</object>\n";
	$xml .= "\t<parameterValue>".$fields['description']."</parameterValue>\n";
	$xml .= "\t<parameterName>".$ticket."</parameterName>\n";
	$xml .= "</alert></alerts>";

	logmsg("Creating XML file.");
	//Output file - in a loop to ensure unique file name
	global $basePath;
	do {
		$outfile = $basePath."/spool/elert_".date('YmdHis')."_".rand().".xml";
	} while (file_exists($outfile));
	file_put_contents($outfile,$xml."\n",FILE_APPEND);

}
?>
