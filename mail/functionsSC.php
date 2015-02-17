<?php
function SCProcess() {
	global $aembase;
	$file = $aembase."/anp/scMonitor/email.rec";
	logmsg("Touch ".$file);
	touch($file);
}

function SCUpdateIM($body,$subject) {
	global $aembase;
	preg_match('/IM[0-9]{6,7}/',strtoupper($subject),$matches);
	$im = $matches[0];
	$pieces = explode('From:',$body);
	$updtxt = "From:".addslashes(preg_replace('/\s\s+/', "\n",str_replace("Auto forwarded by a Rule","",$pieces[1])));
	logmsg("Updating ".$im.":\n".$updtxt);

	require_once($aembase.'/lib/nusoap/lib/nusoap.php');
	require($aembase.'/conf/config.php');

	$err = $sc_client->getError();
	if ($err) {
		logmsg('Constructor error: '.$err);
		return;
	}
	$sc_client->setCredentials('pem', 'Pemspassword');

	$IncidentID = new soapval("IncidentID","StringType",$im,"http://servicecenter.peregrine.com/PWS/Common");
	$keys = new soapval('keys','IncidentKeysType',array($IncidentID),"http://servicecenter.peregrine.com/PWS");
	$JournalUpdates = new soapval("JournalUpdates","ArrayType",$updtxt,"http://servicecenter.peregrine.com/PWS/Common");
	$instance = new soapval("instance","IncidentInstanceType",array($JournalUpdates),"http://servicecenter.peregrine.com/PWS");
	$model = new soapval("model", "IncidentModelType",array($keys,$instance),null,"http://servicecenter.peregrine.com/PWS");
	$UpdateIncidentRequest = new soapval("UpdateIncidentRequest","UpdateIncidentRequestType",$model,"http://servicecenter.peregrine.com/PWS");
	#print_r($UpdateIncidentRequest);
	$result = $sc_client->call('UpdateIncident',$UpdateIncidentRequest->serialize('literal'),"http://servicecenter.peregrine.com/PWS");//,

	// Check for a fault
	if ($sc_client->fault) {
		logmsg('Update Fault: '.print_r($result));
	} else {
		// Check for errors
		$err = $sc_client->getError();
		if ($err) {
			// Display the error
			logmsg('Update Error: '.$err);
		} else {
			if($result['!status'] == 'SUCCESS') {
				logmsg($im." successfully updated.");
			} else {
				logmsg(print_r($result,TRUE));
			}
		}
	}

}
?>
