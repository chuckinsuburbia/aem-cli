<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_aem = "mv00lp22";
$database_aem = "aem";
$username_aem = "aemuser";
$password_aem = "hnAM36MW2sQeKjLU";
$aem = mysql_pconnect($hostname_aem, $username_aem, $password_aem) or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db($database_aem);
?>
