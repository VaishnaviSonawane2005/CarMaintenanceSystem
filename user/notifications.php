<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';

// Fetch all notifications for the current user
$user_id = $_SESSION['id'];
$query = "
    SELECT 
        n.id, 
        n.request_id, 
        n.type, 
        n.message, 
        n.created_at, 
        n.is_read,
        mr.status as request_status,
        mr.mechanic_id,
        u.name as mechanic_name,
        u.contact as mechanic_contact,
        ms.suggestion,
        mr.preferred_date,
        mr.preferred_time
    FROM notifications n
    LEFT JOIN maintenance_requests mr ON n.request_id = mr.id
    LEFT JOIN users u ON mr.mechanic_id = u.id
    LEFT JOIN mechanic_suggestions ms ON n.request_id = ms.request_id AND ms.mechanic_id = mr.mechanic_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark all as read when page loads
if (!empty($notifications)) {
    $unread_ids = array_filter($notifications, fn($n) => !$n['is_read']);
    if (!empty($unread_ids)) {
        $update = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();
        $update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Notifications</title>
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
        .notification-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .notification-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid var(--primary);
        }
        .notification-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .notification-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        .notification-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
        }
        .notification-title i {
            margin-right: 10px;
        }
        .notification-time {
            font-size: 13px;
            color: #7f8c8d;
        }
        .notification-body {
            padding: 20px;
        }
        .notification-message {
            margin: 0 0 15px 0;
            line-height: 1.6;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        .badge-pending { background: #f39c12; color: white; }
        .badge-accepted { background: #2ecc71; color: white; }
        .badge-rejected { background: #e74c3c; color: white; }
        .badge-inprogress { background: #3498db; color: white; }
        .badge-testing { background: #9b59b6; color: white; }
        .badge-completed { background: #27ae60; color: white; }
        .mechanic-details, .suggestion-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: 600;
            min-width: 120px;
            color: #7f8c8d;
        }
        .detail-value {
            flex: 1;
        }
        .suggestion-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        .suggestion-text {
            font-style: italic;
            line-height: 1.6;
        }
        .no-notifications {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        .no-notifications i {
            font-size: 50px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        .unread {
            background: #f0f8ff;
            border-left: 4px solid var(--primary);
        }
        .progress-tracker {
            margin: 20px 0;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 20px;
        }
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 3px;
            background: #e0e0e0;
            z-index: 0;
        }
        .progress-bar {
            position: absolute;
            top: 50%;
            left: 0;
            height: 3px;
            background: var(--primary);
            z-index: 1;
            transition: width 0.5s ease;
        }
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            width: 24%;
        }
        .step-icon {
            width: 30px;
            height: 30px;
            background: #e0e0e0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .step.active .step-icon {
            background: var(--primary);
        }
        .step.completed .step-icon {
            background: var(--success);
        }
        .step-label {
            font-size: 12px;
            color: #95a5a6;
        }
        .step.active .step-label,
        .step.completed .step-label {
            color: var(--dark);
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
            }
            .sidebar.active ~ .main-content {
                margin-left: 0;
            }
            .notification-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .notification-time {
                margin-top: 5px;
            }
        }
        </style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<div class="main-content">
    <header>
        <div style="display: flex; align-items: center;">
            <button id="toggleBtn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h1>Service Notifications</h1>
        </div>
        <button id="darkModeBtn" onclick="toggleDarkMode()"><i class="fas fa-moon"></i></button>
    </header>

    <div class="notification-container">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class="far fa-bell-slash"></i>
                <h3>No notifications yet</h3>
                <p>You'll see updates about your service requests here</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?>">
                    <div class="notification-header">
                        <h3 class="notification-title">
                            <?php 
                            $icon = 'fa-info-circle';
                            $color = 'var(--primary)';
                            if ($notification['type'] === 'status_update') {
                                $icon = 'fa-sync-alt';
                                $color = 'var(--primary)';
                            } elseif ($notification['type'] === 'assignment') {
                                $icon = 'fa-user-cog';
                                $color = 'var(--success)';
                            } elseif ($notification['type'] === 'rejection') {
                                $icon = 'fa-times-circle';
                                $color = 'var(--danger)';
                            } elseif ($notification['type'] === 'completion') {
                                $icon = 'fa-check-circle';
                                $color = 'var(--success)';
                            }
                            ?>
                            <i class="fas <?= $icon ?>" style="color: <?= $color ?>"></i>
                            <?= htmlspecialchars($notification['message']) ?>
                        </h3>
                        <span class="notification-time">
                            <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                        </span>
                    </div>
                    <div class="notification-body">
                        <p class="notification-message">
                            <?php if ($notification['request_status']): ?>
                                <span class="status-badge badge-<?= strtolower(str_replace(' ', '', $notification['request_status'])) ?>">
                                    <?= $notification['request_status'] ?>
                                </span>
                            <?php endif; ?>
                            Request ID: <?= $notification['request_id'] ?>
                        </p>
                        
                        <div class="detail-row">
                            <span class="detail-label">Scheduled Date:</span>
                            <span class="detail-value"><?= $notification['preferred_date'] ?> at <?= date('h:i A', strtotime($notification['preferred_time'])) ?></span>
                        </div>
                        
                        <?php if ($notification['request_status'] && in_array($notification['request_status'], ['Accepted', 'In Progress', 'Testing', 'Completed'])): ?>
                            <div class="progress-tracker">
                                <div class="progress-steps">
                                    <div class="progress-bar" style="width: <?= 
                                        $notification['request_status'] === 'Accepted' ? '25%' : 
                                        ($notification['request_status'] === 'In Progress' ? '50%' : 
                                        ($notification['request_status'] === 'Testing' ? '75%' : '100%')) 
                                    ?>"></div>
                                    <div class="step <?= $notification['request_status'] === 'Accepted' ? 'active' : ($notification['request_status'] === 'In Progress' || $notification['request_status'] === 'Testing' || $notification['request_status'] === 'Completed' ? 'completed' : '') ?>">
                                        <div class="step-icon">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="step-label">Accepted</div>
                                    </div>
                                    <div class="step <?= $notification['request_status'] === 'In Progress' ? 'active' : ($notification['request_status'] === 'Testing' || $notification['request_status'] === 'Completed' ? 'completed' : '') ?>">
                                        <div class="step-icon">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <div class="step-label">In Progress</div>
                                    </div>
                                    <div class="step <?= $notification['request_status'] === 'Testing' ? 'active' : ($notification['request_status'] === 'Completed' ? 'completed' : '') ?>">
                                        <div class="step-icon">
                                            <i class="fas fa-vial"></i>
                                        </div>
                                        <div class="step-label">Testing</div>
                                    </div>
                                    <div class="step <?= $notification['request_status'] === 'Completed' ? 'active completed' : '' ?>">
                                        <div class="step-icon">
                                            <i class="fas fa-flag-checkered"></i>
                                        </div>
                                        <div class="step-label">Completed</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['mechanic_id']): ?>
                            <div class="mechanic-details">
                                <div class="detail-row">
                                    <span class="detail-label">Assigned Mechanic:</span>
                                    <span class="detail-value"><?= htmlspecialchars($notification['mechanic_name']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Contact:</span>
                                    <span class="detail-value"><?= htmlspecialchars($notification['mechanic_contact']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($notification['suggestion']): ?>
                            <div class="suggestion-box">
                                <div class="suggestion-title">Mechanic's Suggestions:</div>
                                <div class="suggestion-text"><?= htmlspecialchars($notification['suggestion']) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to toggle sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Function to toggle dark mode
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    
    // Update button icon
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

// Real-time updates using EventSource
if (typeof(EventSource) !== "undefined") {
    const eventSource = new EventSource("notifications_stream.php?user_id=<?= $user_id ?>");
    
    eventSource.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        if (data.update) {
            // Show toast notification
            createToast(data.type === 'new' ? 'info' : 'success', data.message, 5000);
            
            // Reload the page after a short delay to show new notifications
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    };
    
    eventSource.onerror = function() {
        console.log("EventSource failed.");
        // Attempt to reconnect after 5 seconds
        setTimeout(() => {
            if (eventSource.readyState === EventSource.CLOSED) {
                eventSource = new EventSource("notifications_stream.php?user_id=<?= $user_id ?>");
            }
        }, 5000);
    };
} else {
    console.log("Your browser doesn't support server-sent events.");
}

// Periodically check for updates (fallback)
setInterval(() => {
    fetch('check_notifications.php?user_id=<?= $user_id ?>')
        .then(response => response.json())
        .then(data => {
            if (data.new_notifications) {
                createToast('info', 'You have new updates about your service requests', 3000);
                window.location.reload();
            }
        });
}, 30000); // Check every 30 seconds
</script>
</body>
</html>