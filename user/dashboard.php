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
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f0f2f5, #ffffff);
            overflow-x: hidden;
        }

        .main {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .main.shifted {
            margin-left: 250px;
        }

        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
        }

        header h1 {
            margin: 0;
            flex-grow: 1;
        }

        #toggleBtn {
            background-color: #34495e;
            color: white;
            border: none;
            font-size: 20px;
            padding: 8px 14px;
            cursor: pointer;
            border-radius: 4px;
            margin-right: 15px;
        }

        #toggleBtn:hover {
            background-color: #17a2b8;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            font-size: 18px;
        }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main" id="mainContent">
    <header>
        <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
        <h1>User Dashboard</h1>
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
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        sidebar.classList.toggle('active');
        mainContent.classList.toggle('shifted');
    }
</script>

</body>
</html>
