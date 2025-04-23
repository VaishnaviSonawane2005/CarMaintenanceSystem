<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../auth.php");
    exit();
}
$mechanic_name = $_SESSION['name'];
$mechanic_role = $_SESSION['role'];

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mechanic Dashboard</title>
    <link rel="stylesheet" href="../dashboard.css">
</head>
<body>

<?php include '../sidebar.php'; ?>


<div class="main">
    <header>
    <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
        <h1>Dashboard</h1>
    </header>
    <section class="content">
        <marquee behavior="scroll" direction="left">Welcome to Car Maintenance System - Mechanic Panel</marquee>   
        <div class="card">
        <p>You can view and manage your assigned tasks and upcoming maintenance schedules here.</p>
    </div>
</div>
<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
