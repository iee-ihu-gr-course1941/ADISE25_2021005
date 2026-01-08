<?php
$host='';
$db='';

require_once "lib/db_upass.php";

$user = $DB_USER;
$pass = $DB_PASS;

$mysqli = new mysqli($host,$user,$pass,$db);

if($mysqli->connect_errno){
    echo 'Failed to connect to MySQL';
}

?>