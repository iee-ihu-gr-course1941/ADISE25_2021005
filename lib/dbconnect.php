<?php
$host='localhost';
$db='webgame';

require_once "lib/db_upass.php";

$user = $DB_USER;
$pass = $DB_PASS;

if(gethostname()=='users.iee.ihu.gr'){
    $mysqli = new mysqli($host,$user,$pass,$db,null,'');
}
else{
    $mysqli = new mysqli($host,$user,$pass,$db);
}

if($mysqli->connect_errno){
    echo 'Failed to connect to MySQL';
}

?>