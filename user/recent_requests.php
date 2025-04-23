<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];
$user_id = $_SESSION['id'];

$query = "SELECT * FROM maintenance_requests WHERE user_id = '$user_id' ORDER BY request_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link rel="stylesheet" href="../dashboard.css">
    <style>
        .main-content {
            margin-left: 240px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f4f6f9;
            animation: fadeIn 0.6s ease-in-out;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
        }
        header button {
            font-size: 24px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }
        .content {
            margin-top: 20px;
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .card {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s;
        }
        .card:hover {
            background: #2980b9;
            color: white;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 14px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #eef6fc; }
        marquee {
            background: #dfe6e9;
            padding: 10px;
            font-weight: bold;
            color: #2d3436;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main-content">
    <header>
        <button onclick="toggleSidebar()">☰</button>
        <h2>My Notifications</h2>
    </header>

    <div class="content">
        <marquee>Welcome to Car Maintenance System - Notifications Panel</marquee>

        <div class="card-grid">
            <div class="card">🔔 Assigned Mechanic</div>
            <div class="card">📋 Request Status</div>
            <div class="card">📝 Request Details</div>
            <div class="card">🔄 Service Progress</div>
        </div>

        <h3>Recent Requests</h3>
        <table>
            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Status</th>
                    <th>Mechanic</th>
                    <th>Contact</th>
                    <th>Availability</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['status'] ?? 'Pending' ?></td>
                        <td><?= $row['mechanic_name'] ?? 'Not Assigned' ?></td>
                        <td><?= $row['mechanic_contact'] ?? 'N/A' ?></td>
                        <td><?= $row['mechanic_availability'] ?? 'N/A' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
