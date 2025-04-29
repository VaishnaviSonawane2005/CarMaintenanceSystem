<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';  // Modular toast system

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_name = $_SESSION['name'] ?? 'Vaishnavi Sonawane';
$user_role = $_SESSION['role'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    if ($action === 'accept') {
        header("Location: assign_mechanic.php?id={$request_id}");
        exit();
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE maintenance_requests SET status='Rejected' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        if ($stmt->execute()) {
            header("Location: accept_requests.php?toast=reject_success");
            exit();
        } else {
            $error = "Failed to reject request.";
        }
        $stmt->close();
    }
}

$query = "
    SELECT mr.id, mr.description, mr.preferred_date, mr.preferred_time, 
           u.name AS user_name, u.contact AS user_contact
    FROM maintenance_requests mr
    JOIN users u ON mr.user_id = u.id
    WHERE mr.status = 'Pending'
";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Requests - Admin Panel</title>

    <!-- Toast CSS/JS -->
    <link rel="stylesheet" href="../toast_styles.css">
    <script src="../toast_script.js"></script>

    <!-- YOUR ORIGINAL CSS & ANIMATIONS -->
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f0f2f5, #ffffff);
            overflow-x: hidden;
        }
        .main-content { padding: 30px; transition: margin-left 0.3s ease; }
        .sidebar.active ~ .main-content { margin-left: 250px; }
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
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
            transition: background 0.3s ease;
        }
        #toggleBtn:hover { background-color: #17a2b8; }
        header h1 { margin: 0; flex-grow: 1; }

        /* Card Container */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Responsive grid with min card size */
            gap: 20px;
            margin-top: 30px;
        }
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .card:hover {
            transform: translateY(-10px);
        }
        .card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .card p {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
        }
        .card .actions {
            display: flex;
            justify-content: space-between;
        }
        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        .accept-btn {
            background-color: #28a745;
            color: white;
        }
        .accept-btn:hover {
            background-color: #218838;
        }
        .reject-btn {
            background-color: #dc3545;
            color: white;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
        }
    </style>

    <script>
        function confirmReject() {
            return confirm("Are you sure you want to reject this request?");
        }

        window.onload = function() {
            const params = new URLSearchParams(window.location.search);
            if (params.has('toast')) {
                switch (params.get('toast')) {
                    case 'assign_success':
                        createToast('success', 'Mechanic assigned successfully! Redirecting...', 3000);
                        setTimeout(() => window.location.href = 'accept_requests.php', 3100);
                        break;
                    case 'reject_success':
                        createToast('success', 'Request rejected successfully!', 3000);
                        break;
                    case 'assign_error':
                        createToast('error', 'Failed to assign mechanic.', 4000);
                        break;
                }
            }
        }
    </script>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div id="toast-container"></div>
<audio id="toast-success" src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" preload="auto"></audio>
<audio id="toast-error" src="https://assets.mixkit.co/sfx/preview/mixkit-wrong-answer-fail-notification-946.mp3" preload="auto"></audio>

<div class="main-content">
    <header>
        <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
        <h1>Pending Maintenance Requests</h1>
    </header>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card-container">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($r = mysqli_fetch_assoc($result)): ?>
                <div class="card">
                    <h3>Request ID: <?= $r['id'] ?></h3>
                    <p><strong>User:</strong> <?= htmlspecialchars($r['user_name']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($r['user_contact']) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($r['description']) ?></p>
                    <p><strong>Date:</strong> <?= htmlspecialchars($r['preferred_date']) ?></p>
                    <p><strong>Time:</strong> <?= htmlspecialchars($r['preferred_time']) ?></p>
                    <div class="actions">
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action" value="accept" class="action-btn accept-btn">Accept</button>
                        </form>
                        <form method="post" onsubmit="return confirmReject();" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <button type="submit" name="action" value="reject" class="action-btn reject-btn">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No pending requests found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
