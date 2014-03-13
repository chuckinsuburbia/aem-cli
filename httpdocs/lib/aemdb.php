<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_aem = "localhost";
$database_aem = "aem";
$username_aem = "root";
$password_aem = "";
$aem = mysql_pconnect($hostname_aem, $username_aem, $password_aem) or trigger_error(mysql_error(),E_USER_ERROR); 
mysql_select_db($database_aem);
?>
