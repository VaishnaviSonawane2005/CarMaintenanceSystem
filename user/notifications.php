<?php
// Start session with proper configuration
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/', '', false, true);
session_start();

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['id'];

// Mark notifications as read when page loads
if (!isset($_GET['no_mark_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
}

// Fetch all notifications with detailed service information
$query = "SELECT n.*, mr.status as request_status, mr.mechanic_id, 
          u.name as mechanic_name, u.contact as mechanic_contact,
          ms.suggestion, mr.preferred_date, mr.preferred_time, mr.description,
          mr.requested_on as request_created, mr.updated_at as last_updated
          FROM notifications n
          LEFT JOIN maintenance_requests mr ON n.request_id = mr.id
          LEFT JOIN users u ON mr.mechanic_id = u.id
          LEFT JOIN mechanic_suggestions ms ON n.request_id = ms.request_id AND ms.mechanic_id = mr.mechanic_id
          WHERE n.user_id = ?
          ORDER BY 
            CASE WHEN mr.status = 'Completed' AND n.created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 0 ELSE 1 END,
            n.is_read ASC, 
            n.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) die("Error preparing query: " . $conn->error);
if (!$stmt->bind_param("i", $user_id)) die("Error binding parameters: " . $stmt->error);
if (!$stmt->execute()) die("Error executing query: " . $stmt->error);

$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$service_stages = ['Accepted', 'In Progress', 'Testing', 'Completed'];
$stage_icons = [
    'Accepted' => 'fa-check-circle',
    'In Progress' => 'fa-tools',
    'Testing' => 'fa-vial',
    'Completed' => 'fa-check-double'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Notifications</title>
    <link rel="stylesheet" href="../toast_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <style>
        :root {
            --primary: #3498db;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --light: #ecf0f1;
            --unread-bg: #f0f8ff;
            --highlight-bg: #fff9e6;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        body.dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }
        
        .main-content {
            margin-left: 0;
            padding: 30px;
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
            border-left: 4px solid var(--primary);
        }
        
        body.dark-mode .notification-card {
            background: #2d2d2d;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        
        .notification-card.unread {
            background: var(--unread-bg);
        }
        
        .notification-card.highlight {
            background: var(--highlight-bg);
            border-left: 4px solid var(--warning);
            animation: highlight-pulse 2s ease-in-out;
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
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .notification-time {
            font-size: 13px;
            color: #7f8c8d;
        }
        
        .notification-body {
            padding: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
            color: white;
        }
        
        .badge-accepted { background: #2ecc71; }
        .badge-inprogress { background: #3498db; }
        .badge-testing { background: #9b59b6; }
        .badge-completed { background: #27ae60; }
        
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
        
        body.dark-mode .progress-steps::before {
            background: #444;
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
            width: 40px;
            height: 40px;
            background: #e0e0e0;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #555;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        body.dark-mode .step-icon {
            background-color: #444;
            color: #ddd;
        }
        
        .step.active .step-icon {
            background: var(--primary);
            color: white;
            animation: pulse 2s infinite;
        }
        
        .step.completed .step-icon {
            background: var(--success);
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #95a5a6;
        }
        
        body.dark-mode .step-label {
            color: #aaa;
        }
        
        .step.active .step-label,
        .step.completed .step-label {
            color: var(--dark);
            font-weight: 600;
        }
        
        body.dark-mode .step.active .step-label,
        body.dark-mode .step.completed .step-label {
            color: #f0f0f0;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }
        
        @keyframes highlight-pulse {
            0% { box-shadow: 0 0 10px rgba(243, 156, 18, 0.5); }
            50% { box-shadow: 0 0 20px rgba(243, 156, 18, 0.8); }
            100% { box-shadow: 0 0 10px rgba(243, 156, 18, 0.5); }
        }
        
        .completed-celebration {
            animation: celebration 1s ease;
            color: var(--success);
            display: inline-block;
        }
        
        .no-notifications {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        
        .service-description {
            margin: 15px 0;
            padding: 10px;
            background: #f0f8ff;
            border-left: 3px solid var(--primary);
            border-radius: 0 4px 4px 0;
        }
        
        body.dark-mode .service-description {
            background: #2a3a4a;
        }
        
        .new-completion-banner {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .mechanic-suggestion {
            margin: 15px 0;
            padding: 10px;
            background: #fff9e6;
            border-left: 3px solid var(--warning);
            border-radius: 0 4px 4px 0;
        }
        
        body.dark-mode .mechanic-suggestion {
            background: #3a3a2a;
        }
        
        .mechanic-info {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .mechanic-info i {
            font-size: 20px;
            margin-right: 10px;
            color: var(--primary);
        }
        
        .section-title {
            font-size: 18px;
            margin: 20px 0 10px;
            color: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 5px;
        }
        
        body.dark-mode .section-title {
            color: #f0f0f0;
        }
        
        @keyframes celebration {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
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
        <?php 
        $recent_completions = array_filter($notifications, function($n) {
            return $n['request_status'] === 'Completed' && 
                   strtotime($n['created_at']) >= (time() - 3600);
        });
        
        if (!empty($recent_completions)): ?>
            <div class="new-completion-banner">
                <i class="fas fa-check-circle"></i>
                <div>
                    <h3>Service Completed!</h3>
                    <p>Your recent service request has been completed successfully</p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class="far fa-bell-slash"></i>
                <h3>No notifications yet</h3>
                <p>You'll see updates about your service requests here</p>
            </div>
        <?php else: 
            $unread = array_filter($notifications, fn($n) => !$n['is_read']);
            $read = array_filter($notifications, fn($n) => $n['is_read']);
            
            if (!empty($unread)): ?>
                <div class="unread-section">
                    <h2 class="section-title">New Notifications</h2>
                    <?php foreach ($unread as $notification): 
                        renderNotification($notification, $service_stages, $stage_icons);
                    endforeach; ?>
                </div>
            <?php endif;
            
            if (!empty($read)): ?>
                <div class="read-section">
                    <h2 class="section-title">Earlier Notifications</h2>
                    <?php foreach ($read as $notification): 
                        renderNotification($notification, $service_stages, $stage_icons);
                    endforeach; ?>
                </div>
            <?php endif;
        endif; ?>
    </div>
</div>

<?php
function renderNotification($notification, $service_stages, $stage_icons) {
    $current_stage = array_search($notification['request_status'], $service_stages);
    $progress_percent = $current_stage !== false ? ($current_stage / (count($service_stages) - 1)) * 100 : 0;
    $is_recent_completion = $notification['request_status'] === 'Completed' && 
                          strtotime($notification['created_at']) >= (time() - 3600);
    ?>
    <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?> <?= $is_recent_completion ? 'highlight' : '' ?>" 
         data-request-id="<?= $notification['request_id'] ?>"
         data-status="<?= $notification['request_status'] ?>"
         data-created="<?= strtotime($notification['created_at']) ?>">
        <div class="notification-header">
            <h3 class="notification-title">
                <i class="fas fa-<?= 
                    $notification['type'] === 'status_update' ? 'sync-alt' : 
                    ($notification['type'] === 'new_request' ? 'wrench' : 'bell')
                ?>"></i>
                <?= htmlspecialchars($notification['message']) ?>
            </h3>
            <span class="notification-time">
                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                <?php if ($is_recent_completion): ?>
                    <span style="color: var(--success);">(New!)</span>
                <?php endif; ?>
            </span>
        </div>
        
        <div class="notification-body">
            <?php if ($notification['request_status']): ?>
                <p class="notification-message">
                    <span class="status-badge badge-<?= strtolower(str_replace(' ', '', $notification['request_status'])) ?>">
                        <?= $notification['request_status'] ?>
                    </span>
                    <?php if ($notification['request_status'] === 'Completed'): ?>
                        <span class="completed-celebration">
                            <i class="fas fa-check-double"></i> Service Completed
                        </span>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            
            <?php if ($notification['description']): ?>
                <div class="service-description">
                    <strong>Service Description:</strong> 
                    <?= htmlspecialchars($notification['description']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($notification['mechanic_name'] || $notification['suggestion']): ?>
                <div class="mechanic-suggestion">
                    <?php if ($notification['mechanic_name']): ?>
                        <div class="mechanic-info">
                            <i class="fas fa-user-cog"></i>
                            <div>
                                <strong>Mechanic:</strong> 
                                <?= htmlspecialchars($notification['mechanic_name']) ?>
                                <?php if ($notification['mechanic_contact']): ?>
                                    (<?= htmlspecialchars($notification['mechanic_contact']) ?>)
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($notification['suggestion']): ?>
                        <div>
                            <strong>Mechanic's Suggestion:</strong>
                            <p><?= htmlspecialchars($notification['suggestion']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($current_stage !== false): ?>
                <div class="progress-tracker">
                    <div class="progress-steps">
                        <div class="progress-bar" style="width: <?= $progress_percent ?>%;"></div>
                        <?php foreach ($service_stages as $index => $stage): 
                            $step_class = '';
                            if ($index < $current_stage) {
                                $step_class = 'completed';
                            } elseif ($index == $current_stage) {
                                $step_class = 'active';
                            }
                        ?>
                            <div class="step <?= $step_class ?>">
                                <div class="step-icon">
                                    <i class="fas <?= $stage_icons[$stage] ?>"></i>
                                </div>
                                <div class="step-label"><?= $stage ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<script>
// DOM Elements
const notificationContainer = document.querySelector('.notification-container');
let firstLoad = true;

// Function to toggle sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

// Function to toggle dark mode
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    const icon = document.querySelector('#darkModeBtn i');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
}

// Check for saved dark mode preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    const icon = document.querySelector('#darkModeBtn i');
    icon.classList.replace('fa-moon', 'fa-sun');
}

// Function to highlight new notifications
function highlightNewNotifications() {
    const notifications = document.querySelectorAll('.notification-card');
    const now = Math.floor(Date.now() / 1000);
    
    notifications.forEach(card => {
        const created = parseInt(card.dataset.created);
        const status = card.dataset.status;
        
        if ((status === 'Completed' && (now - created) < 3600) || card.classList.contains('unread')) {
            card.classList.add('highlight');
            setTimeout(() => card.classList.remove('highlight'), 5000);
        }
    });
}

// Function to show notification popup
function showNotificationPopup(notification) {
    const popup = document.createElement('div');
    popup.className = 'notification-popup';
    popup.innerHTML = `
        <div class="popup-content">
            <div class="popup-header">
                <i class="fas fa-bell"></i>
                <h3>New Notification</h3>
                <button class="close-popup">&times;</button>
            </div>
            <div class="popup-body">
                <p>${notification.message}</p>
                <p class="popup-status">
                    <span class="status-badge badge-${notification.status.toLowerCase().replace(' ', '')}">
                        ${notification.status}
                    </span>
                </p>
            </div>
        </div>
    `;
    
    document.body.appendChild(popup);
    
    popup.querySelector('.close-popup').addEventListener('click', () => {
        popup.remove();
    });
    
    setTimeout(() => popup.remove(), 5000);
}

// Function to play notification sound
function playNotificationSound(type = 'default') {
    const audio = new Audio(`../sounds/${type === 'completed' ? 'celebration.mp3' : 'notification.mp3'}`);
    audio.play().catch(e => console.log("Audio play failed:", e));
}

// Function to setup notification stream
function setupNotificationStream() {
    if (typeof(EventSource) !== "undefined") {
        const eventSource = new EventSource(`notifications_stream.php?user_id=<?= $user_id ?>`);
        
        eventSource.addEventListener('notification', function(e) {
            const data = JSON.parse(e.data);
            showNotificationPopup(data);
            playNotificationSound(data.status === 'Completed' ? 'completed' : 'default');
            updateNotificationsUI(data);
            
            if (data.status === 'Completed') {
                showCompletionCelebration(data.message);
            }
        });
        
        eventSource.onerror = function() {
            console.log("SSE connection error. Reconnecting...");
            setTimeout(setupNotificationStream, 5000);
        };
    } else {
        setInterval(checkForUpdates, 10000);
    }
}

// Function to update notifications UI
function updateNotificationsUI(notification) {
    const notificationCard = document.createElement('div');
    notificationCard.className = 'notification-card unread highlight';
    notificationCard.dataset.requestId = notification.request_id;
    notificationCard.dataset.status = notification.status;
    notificationCard.dataset.created = Math.floor(Date.now() / 1000);
    
    notificationCard.innerHTML = `
        <div class="notification-header">
            <h3 class="notification-title">
                <i class="fas fa-${notification.type === 'status_update' ? 'sync-alt' : 'wrench'}"></i>
                ${notification.message}
            </h3>
            <span class="notification-time">Just now</span>
        </div>
        <div class="notification-body">
            <p class="notification-message">
                <span class="status-badge badge-${notification.status.toLowerCase().replace(' ', '')}">
                    ${notification.status}
                </span>
                ${notification.status === 'Completed' ? 
                    '<span class="completed-celebration"><i class="fas fa-check-double"></i> Service Completed</span>' : ''}
            </p>
        </div>
    `;
    
    const unreadSection = document.querySelector('.unread-section') || 
                         document.querySelector('.notification-container');
    
    if (unreadSection) {
        unreadSection.insertBefore(notificationCard, unreadSection.firstChild);
    }
    
    setTimeout(() => notificationCard.classList.remove('highlight'), 5000);
}

// Function to show completion celebration
function showCompletionCelebration(message) {
    const celebration = document.createElement('div');
    celebration.className = 'celebration-container';
    celebration.innerHTML = `
        <div class="celebration-content">
            <div class="celebration-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Service Completed!</h2>
            <p>${message}</p>
            <button class="celebration-btn">Great!</button>
        </div>
        <div class="floating-icons" id="floating-icons"></div>
    `;
    
    document.body.appendChild(celebration);
    
    const icons = ['fa-wrench', 'fa-car', 'fa-cog', 'fa-check', 'fa-tools', 'fa-bolt'];
    const iconsContainer = document.getElementById('floating-icons');
    
    for (let i = 0; i < 20; i++) {
        const icon = document.createElement('i');
        icon.className = `fas floating-icon ${icons[Math.floor(Math.random() * icons.length)]}`;
        icon.style.left = `${Math.random() * 100}%`;
        icon.style.top = `${Math.random() * 100}%`;
        icon.style.color = `hsl(${Math.random() * 360}, 100%, 50%)`;
        icon.style.animationDelay = `${Math.random() * 3}s`;
        iconsContainer.appendChild(icon);
    }
    
    confetti({
        particleCount: 150,
        spread: 70,
        origin: { y: 0.6 }
    });
    
    celebration.querySelector('.celebration-btn').addEventListener('click', () => {
        celebration.remove();
    });
    
    setTimeout(() => celebration.remove(), 8000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    highlightNewNotifications();
    setupNotificationStream();
    
    if (firstLoad && document.querySelector('.notification-card.unread')) {
        playNotificationSound();
        firstLoad = false;
    }
});

// Notification popup styles
const popupStyles = document.createElement('style');
popupStyles.textContent = `
    .notification-popup {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 300px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 1000;
        animation: slideIn 0.5s ease-out;
        overflow: hidden;
    }
    
    body.dark-mode .notification-popup {
        background: #2d2d2d;
        color: white;
    }
    
    .popup-content {
        padding: 15px;
    }
    
    .popup-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .popup-header i {
        font-size: 20px;
        color: #3498db;
        margin-right: 10px;
    }
    
    .popup-header h3 {
        margin: 0;
        flex-grow: 1;
    }
    
    .close-popup {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: #7f8c8d;
    }
    
    .popup-body {
        padding: 10px 0;
    }
    
    .popup-status {
        margin-top: 10px;
    }
    
    .celebration-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: rgba(0,0,0,0.7);
        z-index: 1000;
        animation: fadeIn 0.5s;
    }
    
    .celebration-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    body.dark-mode .celebration-content {
        background: #2d2d2d;
    }
    
    .celebration-icon {
        font-size: 60px;
        color: var(--success);
        margin-bottom: 20px;
        animation: bounce 1s infinite alternate;
    }
    
    .floating-icons {
        position: absolute;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }
    
    .floating-icon {
        position: absolute;
        font-size: 24px;
        animation: float 3s infinite ease-in-out;
        opacity: 0.8;
    }
    
    @keyframes slideIn {
        from { transform: translateX(100%); }
        to { transform: translateX(0); }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes bounce {
        from { transform: translateY(0); }
        to { transform: translateY(-20px); }
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
        100% { transform: translateY(0px); }
    }
`;
document.head.appendChild(popupStyles);
</script>
</body>
</html>