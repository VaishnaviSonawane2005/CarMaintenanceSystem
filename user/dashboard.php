<?php
// Start session with same configuration
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/', '', false, true);
session_start();

// Strict role checking for user dashboard
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}
include '../db_connect.php';

$user_id = $_SESSION['id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

// Get counts for dashboard
$upcoming_count = 0;
$pending_payments = 0;
$active_requests = 0;

// Get upcoming services count
$stmt = $conn->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE user_id = ? AND status IN ('Accepted','In Progress','Testing') AND preferred_date >= CURDATE()");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $upcoming_count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

// Get pending payments count
$stmt = $conn->prepare("SELECT COUNT(*) FROM service_payments WHERE user_id = ? AND status = 'pending'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pending_payments = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

// Get active requests count
$stmt = $conn->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE user_id = ? AND status != 'Completed'");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $active_requests = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

// Get recent requests
$recent_requests = [];
$stmt = $conn->prepare("SELECT id, description, status, preferred_date, created_at FROM maintenance_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_requests = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$_SESSION['last_activity'] = time();
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
            cursor: pointer;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .recent-requests {
            background: #fff;
            padding: 20px;
            margin: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #2c3e50;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            color: white;
        }

        .badge-pending { background-color: #f39c12; }
        .badge-accepted { background-color: #3498db; }
        .badge-inprogress { background-color: #9b59b6; }
        .badge-completed { background-color: #2ecc71; }
        .badge-rejected { background-color: #e74c3c; }
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
            <div class="card" onclick="window.location='upcoming_services.php'">
                <div>🗓️ Upcoming Services</div>
                <div style="font-size:24px;font-weight:bold;margin:10px 0;"><?= $upcoming_count ?></div>
            </div>
            <div class="card" onclick="window.location='active_requests.php'">
                <div>📋 Active Requests</div>
                <div style="font-size:24px;font-weight:bold;margin:10px 0;"><?= $active_requests ?></div>
            </div>
            <div class="card" onclick="window.location='notifications.php'">
                <div>🔔 Notifications</div>
                <div style="font-size:24px;font-weight:bold;margin:10px 0;"><?= $upcoming_count + $pending_payments ?></div>
            </div>
            <div class="card" onclick="window.location='payment.php'">
                <div>💳 Pending Payments</div>
                <div style="font-size:24px;font-weight:bold;margin:10px 0;"><?= $pending_payments ?></div>
            </div>
        </div>

        <div class="recent-requests">
            <h2>Recent Service Requests</h2>
            <?php if (!empty($recent_requests)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Scheduled Date</th>
                            <th>Request Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_requests as $request): ?>
                            <tr onclick="window.location='request_details.php?id=<?= $request['id'] ?>'">
                                <td>#<?= $request['id'] ?></td>
                                <td><?= htmlspecialchars($request['description']) ?></td>
                                <td>
                                    <span class="status-badge badge-<?= strtolower(str_replace(' ', '', $request['status'])) ?>">
                                        <?= $request['status'] ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($request['preferred_date'])) ?></td>
                                <td><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent service requests found.</p>
            <?php endif; ?>
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