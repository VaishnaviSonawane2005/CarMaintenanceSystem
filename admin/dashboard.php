<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
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
    <title>Admin Dashboard | Car Maintenance System</title>
    <link rel="stylesheet" href="../dashboard.css">
</head>
<body>
<div class="sidebar" id="sidebar">
    <h2>Welcome, <?php echo ucfirst($user_role); ?></h2>
    <div class="profile">
        <img src="../img/car-service.png" alt="Profile" style="width:100px; height:auto;">
        <p><?php echo htmlspecialchars($user_name); ?></p>
    </div>
    <ul>
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="accept_requests.php">✔️ Accept Requests</a></li>
        <li><a href="assign_mechanic.php">👨‍🔧 Assign Mechanic</a></li>
        <li><a href="notifications.php">🔔 Notifications</a></li>
        <li><a href="billing.php">📄 Billing</a></li>
        <li><a href="payment.php">💰 View Payments</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<div class="main">
    <header>
        <button onclick="toggleSidebar()">☰</button>
        <h1>Dashboard</h1>
    </header>
    <section class="content">
        <marquee behavior="scroll" direction="left">Welcome to Car Maintenance System - Admin Panel</marquee>
        <div class="card-grid">
            <div class="card">📥 Total Requests</div>
            <div class="card">🛠️ Assigned Mechanics</div>
            <div class="card">💳 Recent Payments</div>
            <div class="card">📢 Notifications</div>
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
