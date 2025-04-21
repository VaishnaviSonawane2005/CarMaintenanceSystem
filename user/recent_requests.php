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

// Fetching recent notifications for the user
$query = "SELECT * FROM maintenance_requests WHERE user_id = '$user_id' ORDER BY request_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications | Car Maintenance System</title>
    <link rel="stylesheet" href="../dashboard.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f6f9;
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            height: 100vh;
            background-color: #1e1e2f;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            overflow-y: auto;
            z-index: 100;
            animation: fadeIn 0.6s ease-in-out;
        }
        .sidebar h2 {
            color: #ffffff;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .sidebar .profile img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            padding: 10px;
            font-size: 18px;
        }
        .sidebar ul li:hover {
            background-color: #17a2b8;
            cursor: pointer;
        }
        .sidebar ul li a {
            color: #ffffff;
            text-decoration: none;
            display: block;
        }
        .sidebar ul li a:hover {
            color: #ffffff;
        }

        /* Main Content */
        .main {
            margin-left: 240px;
            padding: 20px;
            background-color: #fff;
            min-height: 100vh;
            animation: fadeIn 0.5s ease-in-out;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #2c3e50;
            color: #ffffff;
        }
        header button {
            font-size: 24px;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }
        header h1 {
            font-size: 28px;
        }

        .content {
            padding: 20px;
        }

        .notification-section {
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #3498db;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #e8f4fa;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .card {
            background-color: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .card:hover {
            background-color: #2980b9;
            color: white;
            cursor: pointer;
        }

        /* Success/Failure messages */
        .message {
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-top: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Keyframe Animation */
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }

    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Welcome, <?php echo ucfirst($user_role); ?></h2>
    <div class="profile">
        <img src="../images/profile.png" alt="Profile">
        <p><?php echo htmlspecialchars($user_name); ?></p>
    </div>
    <ul>
        <li><a href="user_dashboard.php">🏠 Home</a></li>
        <li><a href="request_service.php">🛠️ Request Service</a></li>
        <li><a href="schedule_service.php">📅 Schedule Service</a></li>
        <li><a href="recent_requests.php">📨 My Notifications</a></li>
        <li><a href="payment.php">💳 View Bills / Make Payment</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main">
    <header>
        <button onclick="toggleSidebar()">☰</button>
        <h1>My Notifications</h1>
    </header>

    <section class="content">
        <marquee behavior="scroll" direction="left">Welcome to Car Maintenance System - Notifications Panel</marquee>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success">✅ Request submitted successfully!</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="message error">❌ Something went wrong, please try again!</div>
        <?php endif; ?>

        <div class="card-grid">
            <div class="card">🔔 Assigned Mechanic</div>
            <div class="card">📋 Request Status</div>
            <div class="card">📝 Request Details</div>
            <div class="card">🔄 Service Progress</div>
        </div>

        <div class="notification-section">
            <h3>Recent Service Request Updates</h3>
            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Status</th>
                    <th>Assigned Mechanic</th>
                    <th>Mechanic Contact</th>
                    <th>Mechanic Availability</th>
                </tr>
                <?php
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td>" . ($row['status'] ?? 'Pending') . "</td>";
                    echo "<td>" . ($row['mechanic_name'] ?? 'Not Assigned') . "</td>";
                    echo "<td>" . ($row['mechanic_contact'] ?? 'Not Available') . "</td>";
                    echo "<td>" . ($row['mechanic_availability'] ?? 'N/A') . "</td>";
                    echo "</tr>";
                }
                ?>
            </table>
        </div>
    </section>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
