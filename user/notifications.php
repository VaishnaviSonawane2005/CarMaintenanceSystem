<?php
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

if (!isset($_GET['no_mark_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
}

$query = "SELECT n.*, mr.status as request_status, mr.mechanic_id, 
          u.name as mechanic_name, u.contact as mechanic_contact,
          ms.suggestion, mr.preferred_date, mr.preferred_time, mr.description,
          mr.requested_on as request_created, mr.updated_at as last_updated
          FROM notifications n
          LEFT JOIN maintenance_requests mr ON n.request_id = mr.id
          LEFT JOIN users u ON mr.mechanic_id = u.id
          LEFT JOIN mechanic_suggestions ms ON n.request_id = ms.request_id AND ms.mechanic_id = mr.mechanic_id
          WHERE n.user_id = ?
          ORDER BY n.is_read ASC, n.created_at DESC";

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
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #333;
            transition: background 0.3s;
        }
        
        body.dark-mode {
            background: #1a1a1a;
            color: #f0f0f0;
        }
        
        .main-content {
            margin-left: 0;
            padding: 30px;
            transition: margin-left 0.3s;
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
        
        body.dark-mode header {
            background: #1e2a38;
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
            transition: all 0.3s;
            border-left: 4px solid var(--primary);
        }
        
        body.dark-mode .notification-card {
            background: #2d2d2d;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        
        .notification-card.unread {
            background: var(--unread-bg);
            border-left: 4px solid var(--primary);
        }
        
        body.dark-mode .notification-card.unread {
            background: #2a3a4a;
        }
        
        .notification-header {
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        body.dark-mode .notification-header {
            background: #252525;
            border-color: #444;
        }
        
        .notification-title {
            font-weight: 600;
            font-size: 16px;
            color: var(--dark);
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        body.dark-mode .notification-title {
            color: #f0f0f0;
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
        
        .badge-accepted { background: #2ecc71; color: white; }
        .badge-inprogress { background: #3498db; color: white; }
        .badge-testing { background: #9b59b6; color: white; }
        .badge-completed { background: #27ae60; color: white; }
        
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
        
        @keyframes celebration {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .completed-celebration {
            animation: celebration 1s ease;
            color: var(--success);
            display: inline-block;
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
        
        .unread-section, .read-section {
            margin-bottom: 30px;
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
    ?>
    <div class="notification-card <?= $notification['is_read'] ? '' : 'unread' ?>" data-request-id="<?= $notification['request_id'] ?>">
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
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    const icon = document.querySelector('#darkModeBtn i');
    icon.classList.toggle('fa-moon');
    icon.classList.toggle('fa-sun');
}

if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
    const icon = document.querySelector('#darkModeBtn i');
    icon.classList.replace('fa-moon', 'fa-sun');
}

function setupNotificationStream() {
    if (typeof(EventSource) !== "undefined") {
        const eventSource = new EventSource(`notifications_stream.php?user_id=<?= $user_id ?>`);
        
        eventSource.addEventListener('notification', function(e) {
            const data = JSON.parse(e.data);
            showNewNotification(data);
            playNotificationSound();
            showStatusToast('info', 'New update: ' + data.message);
        });
        
        eventSource.addEventListener('status_update', function(e) {
            const data = JSON.parse(e.data);
            updateRequestStatus(data.request_id, data.status, data.message);
            
            // Show different toast based on status
            if (data.status === 'Completed') {
                showStatusToast('success', data.message);
                showCompletionCelebration(data.message);
            } else {
                showStatusToast('info', 'Status updated: ' + data.message);
            }
        });
        
        eventSource.onerror = function() {
            setTimeout(setupNotificationStream, 5000);
        };
    } else {
        setInterval(checkForUpdates, 10000);
    }
}

function showNewNotification(notification) {
    const container = document.querySelector('.unread-section') || 
                     document.querySelector('.notification-container');
    const html = `
        <div class="notification-card unread" data-request-id="${notification.request_id}">
            <div class="notification-header">
                <h3 class="notification-title">
                    <i class="fas fa-${notification.type === 'status_update' ? 'sync-alt' : 'wrench'}"></i>
                    ${notification.message}
                </h3>
                <span class="notification-time">${new Date().toLocaleString()}</span>
            </div>
            <div class="notification-body">
                <p class="notification-message">
                    <span class="status-badge badge-${notification.status.toLowerCase().replace(' ', '')}">
                        ${notification.status}
                    </span>
                </p>
            </div>
        </div>`;
    
    if (document.querySelector('.unread-section')) {
        container.insertAdjacentHTML('afterbegin', html);
    } else {
        container.innerHTML = `
            <div class="unread-section">
                <h2 class="section-title">New Notifications</h2>
                ${html}
            </div>` + container.innerHTML;
    }
}

function updateRequestStatus(requestId, status, message) {
    document.querySelectorAll(`[data-request-id="${requestId}"]`).forEach(card => {
        // Update status badge
        const badge = card.querySelector('.status-badge');
        if (badge) {
            badge.className = `status-badge badge-${status.toLowerCase().replace(' ', '')}`;
            badge.textContent = status;
        }
        
        // Add celebration for completed status
        if (status === 'Completed') {
            const msgContainer = card.querySelector('.notification-message') || 
                                card.querySelector('.notification-body');
            if (msgContainer) {
                const celebration = document.createElement('span');
                celebration.className = 'completed-celebration';
                celebration.innerHTML = '<i class="fas fa-check-double"></i> Service Completed';
                msgContainer.appendChild(celebration);
            }
        }
    });
}

function showStatusToast(status, message) {
    const type = status === 'Completed' ? 'success' : 
                status === 'Rejected' ? 'error' : 'info';
    createToast(type, message, 3000);
}

function showCompletionCelebration(message) {
    // Create celebration overlay
    const celebration = document.createElement('div');
    celebration.className = 'celebration-container';
    celebration.innerHTML = `
        <div class="celebration-content">
            <div class="celebration-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Service Completed!</h2>
            <p>${message}</p>
            <button onclick="this.closest('.celebration-container').remove()" 
                    style="margin-top: 20px; padding: 8px 20px; background: var(--success); color: white; border: none; border-radius: 4px; cursor: pointer;">
                Great!
            </button>
        </div>
        <div class="floating-icons" id="floating-icons"></div>
    `;
    
    document.body.appendChild(celebration);
    
    // Add floating icons
    const iconsContainer = document.getElementById('floating-icons');
    const icons = ['fa-wrench', 'fa-car', 'fa-cog', 'fa-check', 'fa-tools', 'fa-bolt'];
    
    for (let i = 0; i < 20; i++) {
        const icon = document.createElement('i');
        icon.className = 'fas floating-icon ' + icons[Math.floor(Math.random() * icons.length)];
        icon.style.left = Math.random() * 100 + '%';
        icon.style.top = Math.random() * 100 + '%';
        icon.style.color = `hsl(${Math.random() * 360}, 100%, 50%)`;
        icon.style.animationDelay = Math.random() * 3 + 's';
        iconsContainer.appendChild(icon);
    }
    
    // Trigger confetti
    confetti({
        particleCount: 150,
        spread: 70,
        origin: { y: 0.6 }
    });
    
    // Play celebration sound
    playCelebration();
    
    // Auto-close after 8 seconds
    setTimeout(() => {
        celebration.remove();
    }, 8000);
}

function playNotificationSound() {
    const audio = new Audio('../sounds/notification.mp3');
    audio.play().catch(e => console.log("Audio play failed:", e));
}

function playCelebration() {
    const audio = new Audio('../sounds/celebration.mp3');
    audio.play().catch(e => console.log("Celebration audio failed:", e));
}

function checkForUpdates() {
    fetch('check_notifications.php?user_id=<?= $user_id ?>')
        .then(response => response.json())
        .then(data => {
            if (data.new_notifications) {
                window.location.reload();
            }
        });
}

document.addEventListener('DOMContentLoaded', function() {
    setupNotificationStream();
    
    // Check for any completed services to celebrate
    document.querySelectorAll('.badge-completed').forEach(badge => {
        const card = badge.closest('.notification-card');
        if (card && !card.querySelector('.completed-celebration')) {
            const msgContainer = card.querySelector('.notification-message') || 
                                card.querySelector('.notification-body');
            if (msgContainer) {
                const celebration = document.createElement('span');
                celebration.className = 'completed-celebration';
                celebration.innerHTML = '<i class="fas fa-check-double"></i> Service Completed';
                msgContainer.appendChild(celebration);
            }
        }
    });
});
</script>
</body>
</html>