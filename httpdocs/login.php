<?php 
if(!isset($_SESSION)) session_start();
if(isset($_REQUEST['username'])){
	$ldapconfig['host'] = 'moncorpdc1';
	$ldapconfig['port'] = NULL;
	$ldapconfig['basedn'] = 'dc=corp,dc=gaptea,dc=com';
	$ldapconfig['authrealm'] = 'aptea';
	
	function ldap_authenticate($user,$pass) {
		global $ldapconfig;
	//	echo $user." ".$pass;    
		if ($user != "" && $pass != "") {
			$ds=@ldap_connect($ldapconfig['host'],$ldapconfig['port']);
			@ldap_bind($ds, "aptea\pem", "2hard4U");
		    $r = @ldap_search( $ds, $ldapconfig['basedn'], 'sAMAccountname=' . $user);
		    if ($r) {
				//foreach($r as $key => $value){
				//	print $key."=".$value;
				//}
			   $result = @ldap_get_entries( $ds, $r);
			   //print_r($result);
				if ($result[0]) {
				   if (@ldap_bind( $ds, $result[0]['dn'], $pass) ) {
					    return $result[0];
				    }
				}
		   }
		}
		return NULL;
	}	
	if (($result = ldap_authenticate($_REQUEST['username'],$_REQUEST['password'])) == NULL) {
	    die('Authorization Failed');
	}
	$_SESSION['adminUser'] = true;
	header("Location: ".$_REQUEST['return']);	
	//print_r($result);
}
if(isset($_REQUEST['action']) && $_REQUEST['action'] == "logout"){
	$_SESSION['adminUser'] = false;
	header("Location: ".$_REQUEST['return']);
}?>