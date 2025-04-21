<?php
// File: assign.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $mechanic_id = $_POST['mechanic_id'];
    mysqli_query($conn, "UPDATE maintenance_requests SET mechanic_id='$mechanic_id', status='assigned' WHERE id='$request_id'");
    header("Location: assign_mechanic.php");
}
?>