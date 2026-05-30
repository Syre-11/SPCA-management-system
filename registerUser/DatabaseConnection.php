<?php
// Local XAMPP: uses MySQL database "mockdb".
// GitHub Pages: run `npm run build:static` — see README-STATIC.md.
$serverName = "localhost";
$user = "root";
$password = "";
$database = "mockdb";

$conn=new mysqli($serverName,$user,$password,$database);

if ($conn->connect_error){
    die("Connection to server and database failed".$conn->connect_error);
}
?>


     