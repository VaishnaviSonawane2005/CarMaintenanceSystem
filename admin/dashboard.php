<?php
// Start session with same configuration
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/', '', false, true);
session_start();

// Strict role checking for admin dashboard
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

// Update last activity time
$_SESSION['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Car Maintenance System</title>
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
