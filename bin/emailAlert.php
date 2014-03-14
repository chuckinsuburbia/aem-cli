#!/usr/bin/php
<?php
$MAP[1]="AEM Incident ID";
$MAP[2]="AP Incident ID";
$MAP[3]="SC Incident ID";
$MAP[4]="Event Type";
$MAP[5]="Source";
$MAP[6]="Service";
$MAP[7]="Origin Severity";
$MAP[8]="AEM Severity";
$MAP[9]="Object Class";
$MAP[10]="Object";
$MAP[11]="Object Location";
$MAP[12]="Origin Class";
$MAP[13]="Origin";
$MAP[14]="Origin Key";
$MAP[15]="Origin Event Class";
$MAP[16]="Origin Event Key";
$MAP[17]="Domain";
$MAP[18]="Domain Class";
$MAP[19]="Parameter Name";
$MAP[20]="Parameter Value";
$MAP[21]="Text";
$MAP[22]="IT Mgmt Layer";
$MAP[23]="Comment";

$to = 'collishc@aptea.com';
$subject = "AEM Alert";
$subject .= isset($argv[1]) ? " - ".$MAP[1].": ".$argv[1] : "";
$subject .= isset($argv[21]) ? " - ".$argv[21] : "";
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: AEM User <aemuser@'.php_uname("n").'>' . "\r\n";
$message = "<html><body><table>\r\n";

foreach ($MAP as $k => $v)
 {
  $message .= "<tr><th>".$v."</th><td>";
  if(isset($argv[$k]))
   {
    $message.="<pre>".addslashes($argv[$k])."</pre>";
   }
  $message.="</td></tr>\r\n";
 }

$message .= "</table></body></html>";

mail($to, $subject, $message, $headers);

?>
