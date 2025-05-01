<?php
if (!isset($_SESSION)) session_start();
$name = $_SESSION['name'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'user';

// Get unread notification count for badge
$notification_count = 0;
if (isset($_SESSION['id'])) {
    include '../db_connect.php';
    $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $notification_count = $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}
?>

<!-- Sidebar Component -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2><?= ucfirst($role); ?> Panel</h2>
        <div class="close-btn" onclick="toggleSidebar()">✕</div>
    </div>
    <div class="profile">
        <div class="avatar-container">
            <img src="images/profile.png" alt="Profile" class="avatar">
            <div class="avatar-pulse"></div>
        </div>
        <p class="username"><?= htmlspecialchars($name); ?></p>
        <div class="role-badge"><?= ucfirst($role); ?></div>
    </div>
    <ul class="nav-links">
        <?php if ($role === 'user'): ?>
            <li><a href="user_dashboard.php"><i class="fas fa-home"></i><span class="link-text">Dashboard</span></a></li>
            <li><a href="request_service.php"><i class="fas fa-tools"></i><span class="link-text">Request Service</span></a></li>
            <li><a href="schedule_service.php"><i class="fas fa-calendar-alt"></i><span class="link-text">Schedule Service</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i><span class="link-text">Notifications</span>
                <?php if ($notification_count > 0): ?>
                    <span class="notification-badge"><?= min($notification_count, 9) . ($notification_count > 9 ? '+' : '') ?></span>
                <?php endif; ?>
            </a></li>
            <li><a href="payment.php"><i class="fas fa-credit-card"></i><span class="link-text">Payments</span></a></li>
            <li><a href="payment_history.php"><i class="fas fa-history"></i><span class="link-text">Payment History</span></a></li>
            
        <?php elseif ($role === 'admin'): ?>
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span class="link-text">Dashboard</span></a></li>
            <li><a href="accept_requests.php"><i class="fas fa-check-circle"></i><span class="link-text">Accept Requests</span></a></li>
            <li><a href="add_mechanic.php"><i class="fas fa-user-plus"></i><span class="link-text">Add Mechanic</span></a></li>
            <li><a href="view_requests.php"><i class="fas fa-clipboard-list"></i><span class="link-text">Service Requests</span></a></li>
            <li><a href="admin_payments.php"><i class="fas fa-money-bill-wave"></i><span class="link-text">Payment Records</span></a></li>
            <li><a href="revenue_report.php"><i class="fas fa-chart-line"></i><span class="link-text">Revenue Report</span></a></li>
            
        <?php elseif ($role === 'mechanic'): ?>
            <li><a href="mechanic_dashboard.php"><i class="fas fa-home"></i><span class="link-text">Dashboard</span></a></li>
            <li><a href="assigned_tasks.php"><i class="fas fa-tasks"></i><span class="link-text">Assigned Tasks</span></a></li>
            <li><a href="my_schedule.php"><i class="fas fa-calendar"></i><span class="link-text">My Schedule</span></a></li>
            <li><a href="payment_request.php"><i class="fas fa-hand-holding-usd"></i><span class="link-text">Request Payments</span></a></li>
            <li><a href="mechanic_earnings.php"><i class="fas fa-wallet"></i><span class="link-text">My Earnings</span></a></li>
            <li><a href="notifications.php"><i class="fas fa-bell"></i><span class="link-text">Notifications</span>
                <?php if ($notification_count > 0): ?>
                    <span class="notification-badge"><?= min($notification_count, 9) . ($notification_count > 9 ? '+' : '') ?></span>
                <?php endif; ?>
            </a></li>
        <?php endif; ?>
        <li class="logout-link"><a href="logout.php"><i class="fas fa-sign-out-alt"></i><span class="link-text">Logout</span></a></li>
    </ul>
</div>

<style>
    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
    
    .sidebar {
        position: fixed;
        top: 0;
        left: -300px;
        width: 300px;
        height: 100%;
        background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
        color: white;
        padding: 20px 0;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        z-index: 999;
        box-shadow: 5px 0 25px rgba(0,0,0,0.2);
        overflow-y: auto;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .sidebar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 20px 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    
    .sidebar h2 {
        margin: 0;
        font-size: 1.5rem;
        background: linear-gradient(to right, #17a2b8, #28a745);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .close-btn {
        font-size: 1.5rem;
        cursor: pointer;
        transition: transform 0.3s;
    }
    
    .close-btn:hover {
        transform: rotate(90deg);
        color: #ff5c5c;
    }
    
    .profile {
        text-align: center;
        padding: 20px;
        margin: 20px 0;
        position: relative;
    }
    
    .avatar-container {
        position: relative;
        width: 100px;
        height: 100px;
        margin: 0 auto 15px;
    }
    
    .avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #17a2b8;
        position: relative;
        z-index: 2;
        transition: all 0.3s;
    }
    
    .avatar-pulse {
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        background: rgba(23, 162, 184, 0.5);
        border-radius: 50%;
        z-index: 1;
        animation: pulse 2s infinite;
    }
    
    .username {
        font-size: 1.2rem;
        margin: 10px 0 5px;
        font-weight: bold;
    }
    
    .role-badge {
        display: inline-block;
        padding: 3px 10px;
        background: #17a2b8;
        border-radius: 20px;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
    
    .nav-links {
        list-style: none;
        padding: 0 20px;
    }
    
    .nav-links li {
        margin-bottom: 5px;
        position: relative;
        overflow: hidden;
        border-radius: 5px;
        transition: all 0.3s;
    }
    
    .nav-links li:before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transition: all 0.5s;
    }
    
    .nav-links li:hover {
        transform: translateX(10px);
    }
    
    .nav-links li:hover:before {
        left: 0;
    }
    
    .nav-links a {
        color: white;
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 15px;
        position: relative;
        z-index: 1;
    }
    
    .nav-links i {
        font-size: 1.2rem;
        width: 30px;
        transition: all 0.3s;
    }
    
    .nav-links li:hover i {
        transform: scale(1.2);
        color: #17a2b8;
    }
    
    .link-text {
        margin-left: 10px;
        transition: all 0.3s;
    }
    
    .notification-badge {
        background: #ff5c5c;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 0.7rem;
        margin-left: auto;
    }
    
    .logout-link {
        margin-top: 30px;
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 10px;
    }
    
    .logout-link i {
        color: #ff5c5c;
    }
    
    @keyframes pulse {
        0% { transform: scale(0.95); opacity: 0.7; }
        70% { transform: scale(1.1); opacity: 0.3; }
        100% { transform: scale(0.95); opacity: 0.7; }
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('active');
        
        // Toggle main content shift
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.toggle('shifted');
        }
    }
</script>