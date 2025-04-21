<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Dashboard | Car Maintenance System</title>
    <link rel="stylesheet" href="../dashboard.css">
</head>
<body>
<div class="sidebar" id="sidebar">
    <h2>Welcome, <?php echo ucfirst($user_role); ?></h2>
    <div class="profile">
        <img src="../images/profile.png" alt="Profile">
        <p><?php echo htmlspecialchars($user_name); ?></p>
    </div>
    <ul>
        <li><a href="#">🏠 Home</a></li>
        <li><a href="request_service.php">🛠️ Request Service</a></li>
        <li><a href="schedule_service.php">📅 Schedule Service</a></li>
        <li><a href="recent_requests.php">📨 My Notifications</a></li>
        <li><a href="payment.php">💳 View Bills / Make Payment</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<div class="main">
    <header>
        <button onclick="toggleSidebar()">☰</button>
        <h1>Dashboard</h1>
    </header>
    <section class="content">
        <marquee behavior="scroll" direction="left">Welcome to Car Maintenance System - User Panel</marquee>
        <div class="card-grid">
            <div class="card">🗓️ Upcoming Services</div>
            <div class="card">📋 Recent Requests</div>
            <div class="card">🔔 Status Notifications</div>
            <div class="card">💳 Payments</div>
        </div>
    </section>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>
</body>
</html>
