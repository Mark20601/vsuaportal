<?php 
session_start();
$server = 'localhost';
$username = 'u762538048_DITProjects25';
$password = 'dit@projects2025byTeamNetNaViscan';
$database = 'u762538048_VSUASGP';

$conn = new mysqli($server, $username, $password, $database);

if(!$conn){
    echo "Database Error!";
}