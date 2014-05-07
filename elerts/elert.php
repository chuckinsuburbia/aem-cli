#!/usr/bin/php
<?php
date_default_timezone_set('America/New_York');

if(!empty($_REQUEST['scserver'])){
        $scserver=$_REQUEST['scserver'];
}else{
        $scserver="scclientprod";
}
if(!empty($_REQUEST['debug'])){
        $debug=true;
}else{
        #$debug=false;
        $debug=true;
}
if(!isset($scusername)){
        $scusername='pem';
        $scpassword='Pemspassword';
}
$scport="12671";
$log="elert.log";

$aembase = getenv('AEMBASE');
require_once($aembase.'/lib/nusoap/lib/nusoap.php');
function logit($label,$message){
        global $log;
        if(is_array($message)){
                foreach($message as $lbl=>$msg){
                        logit($label.":".$lbl,$msg);
                }
        }else{
                $msg = date("Y-m-d H:i:s")." - ";
		$msg .= isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
		$msg .= " - ".$label." - ".$message."\n";
                file_put_contents($log, $msg, FILE_APPEND | LOCK_EX);
        }
}

function scSoapClient($wsdl)
{
        global $scserver,$scport, $debug, $scusername, $scpassword,$log;
        // Start SOAP Client/ Connect to SC
        $client = new nusoap_client('http://'.$scserver.':'.$scport.'/'.$wsdl.'?wsdl', 'wsdl','','','','');
        $err = $client->getError();
        if ($err) {
                die( '<h2>Constructor error</h2><pre>' . $err . '</pre>');
        }
        $client->setCredentials($scusername, $scpassword);
        $client->loadWSDL();
        //print_r($client->operations);
        return $client;
}
function scSoapRequest(&$client,$request,$query,$data="",$ignoreFailure=false){
        global $debug, $log;
//      $query = "Location=\"01001\"";
        // Setup SOAP REQUEST

        $keys = scSoapVal('keys',"",'peregrinetoxoKeysType',array("query" => $query));
        $instance = scSoapVal("instance",$data,"peregrinetoxoInstanceType");
        $model = scSoapVal("model",array($keys,$instance),"peregrinetoxoModelType");

        // CALL SOAP REQUEST to get IMs
        $_request = scSoapVal($request."Request",$model,$request."RequestType");
        $result = $client->call($request,$_request->serialize('literal'),"http://servicecenter.peregrine.com/PWS");
        //print $_request->serialize('literal');
        if ($client->fault) {
                logit("SC_SOAP_REQUEST FAULT - $request",$result);
                return false;
        } else {
                // Check for errors
                $err = $client->getError();
                if ($err) {
                        // Display the error
                        logit("SC_SOAP_REQUEST FAULT - $request",'<h2>Retrieve Error</h2><pre>' . $err . '</pre>');
                        return false;
                } else {
                        if($result['!status'] == "FAILURE" && !$ignoreFailure){
                                 logit("SC_SOAP_REQUEST FAULT - $request",$result['!message']);
                                 logit("SC_SOAP_REQUEST FAULT - $request",$result['model']['!query']);
                                 return false;
                        }
                        return $result;
                }
        }
}

function scSoapVal($name, $val, $type = "String", $attributes = false, $element_ns = "http://servicecenter.peregrine.com/PWS", $type_ns = false){
        return new soapval($name,$type,$val,$element_ns,$type_ns,$attributes);
}

$scSC=scSoapClient("IncidentManagement");
//$scSC->setDebugLevel($soapDebugLevel);
$result=scSoapRequest($scSC,"RetrieveIncidentKeysList",'IMTicketStatus~="Closed"& HelpDesk="Emergency"',"");
if(!$result){
        logit("SC Retrieve Incident Keys List Failed",$result);
        echo $scSC->getDebug();
        die("Could not get list");
}
if($debug) logit("SC Retrieve Keys List ",$result);

#print_r( $result);
if(!empty($result['keys']['IncidentID'])){
        if($debug) logit("IncidentID",$result['keys']['IncidentID']);
        $ims[]=$result['keys']['IncidentID'];
}else{
        foreach($result['keys'] as $ticket){
                if($debug) logit("IncidentID",$ticket['IncidentID']);
                $ims[]=$ticket['IncidentID'];
        }
}
exec("ls -1 ".$aembase."/elerts/pending",$pending);
exec("ls -1 ".$aembase."/elerts/emails",$list);
print "IN SC:\n";
print_r($ims);
print "RECEIVED:\n";
print_r($list);
print "PENDING:\n";
print_r($pending);

$new = array_diff($ims,$list);
$old = array_diff($list,$ims);
$alert = array_diff($pending,$list);

print "NEW:\n";
print_r($new);
print "OLD:\n";
print_r($old);
print "ALERT:\n";
print_r($alert);

foreach($old as $im){
        unlink($aembase."/elerts/emails/$im");
}

exec("rm -f ".$aembase."/elerts/pending/*");
if(sizeof($new) >0 ){
        foreach($new as $im){
                exec("touch ".$aembase."/elerts/pending/$im");
        }
}
if(sizeof($alert) >0 ){
	$to = "collishc@aptea.com";
	$subj = "missing elert emails";
	$body = "missing the following emails:\n\n".implode("\n",$alert);
	mail($to,$subj,$body);
        exit(1);
}
?>
