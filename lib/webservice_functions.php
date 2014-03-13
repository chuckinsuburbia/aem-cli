<?php

function hello($name) 
 {
  return 'Hello, ' . $name;
 }

function closeIncident($incidentId)
 {
  $cmd="/in/AEM/bin/aemclose.php incident_id=".$incidentId;
  exec($cmd,$output,$rc);
  if($rc == 0)
   {
    return("SUCCESS");
   }
  else
   {
    return("ERROR\n".$output);
   }
 }

?>
