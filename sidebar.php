<?php
if (!isset($_SESSION)) session_start();
$name = $_SESSION['name'] ?? 'Guest';
$role = $_SESSION['role'] ?? 'user'; // fallback to user
?>

<!-- Sidebar Component -->
<div class="sidebar" id="sidebar">
    <h2><?= ucfirst($role); ?> Panel</h2>
    <div class="profile" style="text-align:center; margin:20px 0;">
        <img src="images/profile.png" alt="Profile" style="width:70px; border-radius: 50%;">
        <p><?= htmlspecialchars($name); ?></p>
    </div>
    <ul>
        <?php if ($role === 'user'): ?>
            <li><a href="user_dashboard.php">🏠 User Dashboard</a></li>
            <li><a href="request_service.php">🛠️ Request Service</a></li>
            <li><a href="schedule_service.php">📅 Schedule Service</a></li>
            <li><a href="recent_requests.php">📨 My Notifications</a></li>
            <li><a href="payment.php">💳 View Bills / Make Payment</a></li>
        <?php elseif ($role === 'admin'): ?>
            <li><a href="accept_request.php">✔️ Accept Requests</a></li>
            <li><a href="add_mechanic.php">➕ Add Mechanic</a></li>
            <li><a href="view_requests.php">📋 View Requests</a></li>
        <?php elseif ($role === 'mechanic'): ?>
            <li><a href="mechanic_dashboard.php">🏠 Mechanic Dashboard</a></li>
            <li><a href="assigned_tasks.php">📋 Assigned Tasks</a></li>
            <li><a href="schedule.php">🗓 My Schedule</a></li>
        <?php endif; ?>
        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Sidebar CSS Styling -->
<style>
    .sidebar {
        position: fixed;
        top: 0;
        left: -250px;
        width: 250px;
        height: 100%;
        background-color: #2c3e50;
        color: white;
        padding-top: 20px;
        transition: 0.3s;
        z-index: 999;
    }

    .sidebar.active {
        left: 0;
    }

    .sidebar h2 {
        text-align: center;
        margin-bottom: 20px;
        font-size: 22px;
    }

    .sidebar .profile {
        margin-bottom: 30px;
    }

    .sidebar .profile img {
        width: 70px;
        border-radius: 50%;
        margin-bottom: 10px;
    }

    .sidebar .profile p {
        margin: 0;
        font-weight: bold;
        color: #ffffff;
    }

    .sidebar ul {
        list-style-type: none;
        padding: 0;
    }

    .sidebar ul li {
        padding: 15px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    }

    .sidebar ul li a {
        color: white;
        text-decoration: none;
        display: block;
        transition: 0.3s;
    }

    .sidebar ul li:hover {
        background-color: #17a2b8;
    }

    .sidebar ul li a:hover {
        color: #fff;
        text-decoration: underline;
    }
</style>

<!-- JavaScript for Sidebar Toggle -->
<script>
    // Toggle sidebar visibility
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
    }
</script>
