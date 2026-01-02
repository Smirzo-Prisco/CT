<?php

$_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
$IP = $_SERVER['REMOTE_ADDR'];
$Host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

echo "your IP is $IP and your host is $Host";

?>