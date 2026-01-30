<?php
$serverName="is3-dev.ict.ru.ac.za";
$user="CodeX";
$password="C0d3x!2025";
$database="codex";

$conn=new mysqli($serverName,$user,$password,$database);

if ($conn->connect_error){
    die("Connection to server and database failed".$conn->connect_error);
} else {

    echo "Connection to database is succefully established";
}
?>


     