<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

// Get all payments
$query = "SELECT sp.*, u.name as user_name, m.name as mechanic_name, mr.description 
          FROM service_payments sp
          JOIN users u ON sp.user_id = u.id
          JOIN users m ON sp.mechanic_id = m.id
          JOIN maintenance_requests mr ON sp.request_id = mr.id
          ORDER BY sp.created_at DESC";
$payments = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Records - Admin</title>
    <link rel="stylesheet" href="../toast_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .main-content {
            margin-left: 0;
            padding: 30px;
            transition: margin-left 0.3s;
        }
        .sidebar.active ~ .main-content {
            margin-left: 250px;
        }
        header {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        #toggleBtn, #darkModeBtn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        h1 {
            margin: 0;
            font-size: 24px;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-pending { color: var(--warning); }
        .stat-paid { color: var(--success); }
        .stat-failed { color: var(--danger); }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending { background: var(--warning); color: white; }
        .badge-paid { background: var(--success); color: white; }
        .badge-failed { background: var(--danger); color: white; }
        .amount-cell {
            font-weight: 600;
            color: var(--success);
        }
        .no-payments {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        .no-payments i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
    </style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<div class="main-content">
    <header>
        <div style="display: flex; align-items: center;">
            <button id="toggleBtn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1>Payment Records</h1>
        </div>
        <button id="darkModeBtn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
    </header>

    <div class="dashboard-container">
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-value stat-paid">₹<?= 
                    number_format(array_reduce($payments, function($carry, $item) {
                        return $carry + ($item['status'] === 'paid' ? $item['amount'] : 0);
                    }, 0), 2) 
                ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
            <div class="stat-card">
                <div class="stat-value stat-pending"><?= 
                    count(array_filter($payments, fn($p) => $p['status'] === 'pending'))
                ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($payments) ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
        </div>

        <?php if (empty($payments)): ?>
            <div class="no-payments">
                <i class="far fa-money-bill-alt"></i>
                <h3>No payment records found</h3>
                <p>Payment records will appear here when available</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service</th>
                        <th>User</th>
                        <th>Mechanic</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= $payment['id'] ?></td>
                            <td><?= htmlspecialchars($payment['description']) ?></td>
                            <td><?= htmlspecialchars($payment['user_name']) ?></td>
                            <td><?= htmlspecialchars($payment['mechanic_name']) ?></td>
                            <td class="amount-cell">₹<?= number_format($payment['amount'], 2) ?></td>
                            <td>
                                <span class="status-badge badge-<?= $payment['status'] ?>">
                                    <?= ucfirst($payment['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($payment['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
// [Same JavaScript functions as in payment_request.php]
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    
    const darkModeBtn = document.getElementById('darkModeBtn');
    const icon = darkModeBtn.querySelector('i');
    if (document.body.classList.contains('dark-mode')) {
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    } else {
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
    }
}

// Check for saved dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    const darkModeBtn = document.getElementById('darkModeBtn');
    const icon = darkModeBtn.querySelector('i');
    icon.classList.remove('fa-moon');
    icon.classList.add('fa-sun');
}
</script>
</body>
</html>