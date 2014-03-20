<?php
function plumProcess($subject) {
	$store = array_pop(explode(' ',$subject));
	global $plumErrorPost;
	$url = $plumErrorPost."?store=".$store;
	logmsg($url);
	$post = file_get_contents($url);
	logmsg("PLUM Error post result = ".$post);
	
	$mailto= $store."feadmin@aptea.com";
	//$mailto= "collishc@aptea.com";
	$subj = "Expired PLUM Batches found in store ".$store;
	$body = "<p>Attention: Scanning administrator or price integrity co-ordinator (PIC). There are expired batches in PLUM. For detailed instructions click on the following link. Any problems, please contact the Help Desk @ 1-800-877-7717.</p><p><a href='http://taprightweb1:80/portal/app/portlets/results/viewsolution.jsp?solutionid=041125710505580&isguest=true'>RightAnswers Solution</a></p>";
	$headers  = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	mail($mailto,$subj,$body,$headers);
}

function backupFailureProcess($body) {
	foreach(explode("\n",$body) as $line) {
		if(preg_match("/=/",$line)) $lines[] = $line;
	}
	$newbody = implode(",",$lines);
	return($newbody);
}
?>
