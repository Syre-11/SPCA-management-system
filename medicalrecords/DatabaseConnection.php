<?php
$serverName="";
$user="";
$password="";
$database="";

$conn=new mysqli($serverName,$user,$password,$database);

if ($conn->connect_error){
    die("Connection to server and database failed".$conn->connect_error);
} else {

    echo "Connection to database is succefully established";
}
?>