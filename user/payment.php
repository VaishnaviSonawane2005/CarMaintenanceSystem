<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';

$user_id = $_SESSION['id'];

// Get pending payments with additional details
$query = "SELECT sp.*, mr.description, mr.status as request_status,
          u.name as mechanic_name, u.contact as mechanic_contact,
          mr.preferred_date, mr.preferred_time
          FROM service_payments sp
          JOIN maintenance_requests mr ON sp.request_id = mr.id
          JOIN users u ON sp.mechanic_id = u.id
          WHERE sp.user_id = ? AND sp.status = 'pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check for successful payment toast
$payment_success = false;
if (isset($_GET['toast'])) {
    if ($_GET['toast'] === 'payment_success' && isset($_GET['payment_id'])) {
        $payment_success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Payment</title>
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
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .payment-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            overflow: hidden;
            border-left: 4px solid var(--primary);
        }
        .payment-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .payment-title {
            font-weight: 600;
            font-size: 18px;
            color: var(--dark);
            margin: 0;
        }
        .payment-body {
            padding: 20px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 12px;
        }
        .detail-label {
            font-weight: 600;
            min-width: 150px;
            color: #7f8c8d;
        }
        .detail-value {
            flex: 1;
        }
        .amount-display {
            font-size: 28px;
            font-weight: bold;
            color: var(--success);
            text-align: center;
            margin: 20px 0;
        }
        .payment-btn {
            display: block;
            width: 100%;
            background: var(--success);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            text-align: center;
        }
        .payment-btn:hover {
            background: #218838;
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

        .receipt-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .receipt-title {
            font-size: 22px;
            font-weight: bold;
            color: var(--dark);
        }
        .receipt-subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }
        .receipt-service {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #eee;
        }
        .receipt-total {
            font-weight: bold;
            font-size: 18px;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid var(--primary);
        }
        .receipt-details {
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<div class="main-content">
    <header>
        <div style="display: flex; align-items: center;">
            <button id="toggleBtn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1>Make Payment</h1>
        </div>
        <button id="darkModeBtn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
    </header>

    <div class="payment-container">
        <?php if ($payment_success): ?>
            <div class="receipt-container">
                <div class="receipt-header">
                    <div class="receipt-title">Payment Successful</div>
                    <div class="receipt-subtitle">Thank you for your payment</div>
                </div>
                
                <?php 
                // Get the successful payment details
                $payment_id = (int)$_GET['payment_id'];
                $query = "SELECT sp.*, mr.description, u.name as mechanic_name 
                          FROM service_payments sp
                          JOIN maintenance_requests mr ON sp.request_id = mr.id
                          JOIN users u ON sp.mechanic_id = u.id
                          WHERE sp.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $payment_id);
                $stmt->execute();
                $payment = $stmt->get_result()->fetch_assoc();
                $services = json_decode($payment['services_json'], true);
                ?>
                
                <div class="detail-row">
                    <span class="detail-label">Service ID:</span>
                    <span class="detail-value">#<?= $payment['request_id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['description']) ?></span>
                </div>
                
                <div style="margin: 20px 0;">
                    <h4>Service Breakdown:</h4>
                    <?php foreach ($services as $service): ?>
                        <div class="receipt-service">
                            <span><?= $service['name'] ?></span>
                            <span>₹<?= number_format($service['base_price'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="receipt-service">
                        <span>Subtotal:</span>
                        <span>₹<?= number_format($payment['base_amount'], 2) ?></span>
                    </div>
                    <div class="receipt-service">
                        <span>GST (18%):</span>
                        <span>₹<?= number_format($payment['tax_amount'], 2) ?></span>
                    </div>
                    <div class="receipt-service receipt-total">
                        <span>Total Paid:</span>
                        <span>₹<?= number_format($payment['amount'], 2) ?></span>
                    </div>
                </div>
                
                <div class="receipt-details">
                    <div class="detail-row">
                        <span class="detail-label">Payment Date:</span>
                        <span class="detail-value"><?= date('d M Y h:i A', strtotime($payment['payment_date'])) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Mechanic:</span>
                        <span class="detail-value"><?= htmlspecialchars($payment['mechanic_name']) ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($payments)): ?>
            <div class="no-payments">
                <i class="far fa-check-circle"></i>
                <h3>No pending payments</h3>
                <p>You don't have any outstanding payments at this time</p>
            </div>
        <?php else: ?>
            <?php foreach ($payments as $payment): 
                $services = json_decode($payment['services_json'], true);
            ?>
                <div class="payment-card">
                    <div class="payment-header">
                        <h3 class="payment-title">Payment for Service #<?= $payment['request_id'] ?></h3>
                    </div>
                    <div class="payment-body">
                        <div class="detail-row">
                            <span class="detail-label">Service:</span>
                            <span class="detail-value"><?= htmlspecialchars($payment['description']) ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Mechanic:</span>
                            <span class="detail-value"><?= htmlspecialchars($payment['mechanic_name']) ?> (<?= htmlspecialchars($payment['mechanic_contact']) ?>)</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Scheduled:</span>
                            <span class="detail-value"><?= $payment['preferred_date'] ?> at <?= date('h:i A', strtotime($payment['preferred_time'])) ?></span>
                        </div>
                        
                        <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                            <h4 style="margin-top: 0; margin-bottom: 10px;">Bill Details:</h4>
                            <?php foreach ($services as $service): ?>
                                <div class="detail-row" style="margin-bottom: 5px;">
                                    <span class="detail-label"><?= $service['name'] ?>:</span>
                                    <span class="detail-value">₹<?= number_format($service['base_price'], 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                            <div class="detail-row" style="margin-top: 10px;">
                                <span class="detail-label">Subtotal:</span>
                                <span class="detail-value">₹<?= number_format($payment['base_amount'], 2) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">GST (18%):</span>
                                <span class="detail-value">₹<?= number_format($payment['tax_amount'], 2) ?></span>
                            </div>
                            <div class="detail-row" style="font-weight: bold; margin-top: 10px;">
                                <span class="detail-label">Total Amount:</span>
                                <span class="detail-value">₹<?= number_format($payment['amount'], 2) ?></span>
                            </div>
                        </div>
                        
                        <form action="process_payment.php" method="POST" onsubmit="return confirmPayment()">
                            <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>">
                            <button type="submit" class="payment-btn">
                                <i class="fas fa-rupee-sign"></i> Pay Now
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmPayment() {
    return confirm("Are you sure you want to proceed with this payment?");
}

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

// Handle toast messages from URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const toast = urlParams.get('toast');
    const sound = urlParams.get('sound');
    
    if (toast) {
        let message = '';
        let type = 'success';
        
        switch(toast) {
            case 'payment_success':
                message = 'Payment processed successfully!';
                type = 'success';
                break;
            case 'payment_error':
                message = urlParams.get('message') || 'Payment failed. Please try again.';
                type = 'error';
                break;
            case 'invalid_request':
                message = 'Invalid payment request';
                type = 'error';
                break;
        }
        
        if (message) {
            createToast(type, message);
            
            // Play sound if specified
            if (sound) {
                const audio = new Audio(sound === 'success' ? 
                    'https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3' : 
                    'https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3');
                audio.play();
            }
            
            // Clean URL
            const cleanUrl = window.location.pathname;
            window.history.replaceState({}, document.title, cleanUrl);
        }
    }
});
</script>
</body>
</html>
