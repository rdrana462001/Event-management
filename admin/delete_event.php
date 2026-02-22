<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("DB error"); }

$id = $_GET['id'];

mysqli_query($conn,"DELETE FROM events WHERE event_id='$id'");
header("location:events.php");
