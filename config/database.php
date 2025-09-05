<?php
// config/config.php


// Database configuration for Supabase
$host = "cdrdtizfmehslttsbjtc";
$db_name = "Elms_db";  // Supabase default
$username = "postgres";
$password = "0274742039&Joe"; // from Supabase settings

try {
    $conn = new PDO("pgsql:host=$host;port=5432;dbname=$db_name;user=$username;password=$password");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception){
    echo "Connection error: " . $exception->getMessage();
}
?>