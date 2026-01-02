<?php
$host = 'localhost';
$user = 'crystaltokyo';
$pass = 'Tenetevela89';
$db = 'my_crystaltokyo';
$con = mysql_connect($host,$user,$pass) or die (mysql_error());
$sel = mysql_select_db($db) or die (mysql_error());
?>