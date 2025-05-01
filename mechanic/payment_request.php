<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';

$mechanic_id = $_SESSION['id'];

// Service price list (in INR with tax included)
$service_prices = [
    'Oil Change' => 1200,
    'Brake Inspection' => 800,
    'Engine Tune-Up' => 2500,
    'Tire Rotation' => 600,
    'Battery Check' => 500,
    'AC Repair' => 3500,
    'Suspension Issue' => 4200,
    'Clutch Problem' => 3800,
    'Lights Not Working' => 700,
    'Other' => 1500 // Base price for other services
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    
    // Get service details
    $stmt = $conn->prepare("SELECT id, user_id, description FROM maintenance_requests WHERE id = ? AND mechanic_id = ? AND status = 'Completed'");
    $stmt->bind_param("ii", $request_id, $mechanic_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $request = $result->fetch_assoc();
        $user_id = $request['user_id'];
        $description = $request['description'];
        
        // Determine service type and calculate amount
        $service_type = 'Other';
        $amount = $service_prices['Other'];
        
        foreach ($service_prices as $service => $price) {
            if (stripos($description, $service) !== false) {
                $service_type = $service;
                $amount = $price;
                break;
            }
        }
        
        // Create payment request
        $stmt = $conn->prepare("INSERT INTO service_payments (request_id, user_id, mechanic_id, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $request_id, $user_id, $mechanic_id, $amount);
        
        if ($stmt->execute()) {
            // Mark payment as requested
            $conn->query("UPDATE maintenance_requests SET payment_requested = 1 WHERE id = $request_id");
            
            // Create notification for user
            $message = "Payment requested for service #$request_id (₹$amount)";
            $conn->query("INSERT INTO notifications (user_id, request_id, type, message) VALUES ($user_id, $request_id, 'payment', '$message')");
            
            header("Location: mechanic_dashboard.php?toast=payment_requested");
            exit();
        } else {
            header("Location: payment_request.php?request_id=$request_id&toast=payment_error");
            exit();
        }
    } else {
        header("Location: mechanic_dashboard.php?toast=invalid_request");
        exit();
    }
}

// Get completed services assigned to this mechanic
$query = "SELECT id, description FROM maintenance_requests WHERE mechanic_id = ? AND status = 'Completed' AND payment_requested = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $mechanic_id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Payment</title>
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
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .service-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
        }
        .service-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        .service-desc {
            color: #555;
            margin-bottom: 15px;
        }
        .payment-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .amount-display {
            font-size: 20px;
            font-weight: bold;
            color: var(--success);
        }
        .request-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .request-btn:hover {
            transform: translateY(-2px);
        }
        .price-list {
            margin-top: 40px;
        }
        .price-list h2 {
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: var(--primary);
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .no-services {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        .no-services i {
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
            <h1>Request Payment</h1>
        </div>
        <button id="darkModeBtn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
    </header>

    <div class="container">
        <?php if (empty($services)): ?>
            <div class="no-services">
                <i class="fas fa-check-circle"></i>
                <h3>No pending payment requests</h3>
                <p>All your completed services have payment requests initiated</p>
            </div>
        <?php else: ?>
            <?php foreach ($services as $service): 
                $amount = $service_prices['Other'];
                foreach ($service_prices as $service_name => $price) {
                    if (stripos($service['description'], $service_name) !== false) {
                        $amount = $price;
                        break;
                    }
                }
            ?>
                <div class="service-card">
                    <div class="service-header">Service #<?= $service['id'] ?></div>
                    <div class="service-desc"><?= htmlspecialchars($service['description']) ?></div>
                    <form class="payment-form" method="POST">
                        <input type="hidden" name="request_id" value="<?= $service['id'] ?>">
                        <div class="amount-display">₹<?= number_format($amount, 2) ?></div>
                        <button type="submit" class="request-btn">
                            <i class="fas fa-rupee-sign"></i> Request Payment
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="price-list">
            <h2><i class="fas fa-tags"></i> Service Price List</h2>
            <table>
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Price (INR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_prices as $service => $price): ?>
                        <tr>
                            <td><?= $service ?></td>
                            <td>₹<?= number_format($price, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: right; margin-top: 10px; font-style: italic;">
                * All prices include 18% GST
            </p>
        </div>
    </div>
</div>

<script>
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