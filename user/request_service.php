<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
date_default_timezone_set('Asia/Kolkata');

$minDate = date('Y-m-d');
$minTime = date('H:i', strtotime('+1 hour'));

$user_id = $_SESSION['id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_type = mysqli_real_escape_string($conn, $_POST['issue_type']);
    $additional_note = mysqli_real_escape_string($conn, $_POST['additional_note']);
    $date = $_POST['preferred_date'];
    $time = $_POST['preferred_time'];

    // Combine select + textarea
    $description = $issue_type;
    if (!empty($additional_note)) {
        $description .= " | Note: " . $additional_note;
    }

    $insert = mysqli_query($conn, "INSERT INTO maintenance_requests (user_id, description, preferred_date, preferred_time) 
                                   VALUES ('$user_id', '$description', '$date', '$time')");

    if ($insert) {
        header("Location: request_service.php?success=1");
        exit();
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Maintenance</title>
    <link rel="stylesheet" href="../dashboard.css">
    <style>
        .sidebar {
            width: 240px;
            height: 100vh;
            background-color: #1e1e2f;
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            overflow-y: auto;
            z-index: 100;
        }
        .sidebar ul li:hover {
            background-color: #17a2b8;
        }
        .main-content {
            margin-left: 240px;
            padding: 20px;
            background-color: #f4f6f9;
            min-height: 100vh;
            animation: fadeIn 0.5s ease-in-out;
        }
        h2 {
            text-align: center;
            color: #2c3e50;
        }
        form {
            max-width: 600px;
            margin: 0 auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: fadeIn 0.6s ease-in-out;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="date"],
        input[type="time"],
        select, textarea, button {
            width: 100%;
            margin: 12px 0;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #28a745;
            color: white;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        button:hover {
            background: #218838;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <h2>Welcome, <?php echo ucfirst($user_role); ?></h2>
    <div class="profile">
        <img src="../img/car-service.png" alt="Profile" style="width:100px; height:auto;">
        <p><?php echo htmlspecialchars($user_name); ?></p>
    </div>
    <ul>
        <li><a href="user_dashboard.php">🏠 Dashboard</a></li>
        <li><a href="request_service.php">🛠️ Request Maintenance</a></li>
        <li><a href="my_requests.php">📋 My Requests</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Schedule Car Maintenance</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">✅ Request submitted successfully!</div>
    <?php elseif (!empty($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="issue_type">Select Maintenance Issue</label>
        <select name="issue_type" id="issue_type" required>
            <option value="">-- Select Issue --</option>
            <option value="Oil Change">Oil Change</option>
            <option value="Brake Inspection">Brake Inspection</option>
            <option value="Engine Tune-Up">Engine Tune-Up</option>
            <option value="Tire Rotation">Tire Rotation</option>
            <option value="Battery Check">Battery Check</option>
            <option value="AC Repair">AC Repair</option>
            <option value="Suspension Issue">Suspension Issue</option>
            <option value="Clutch Problem">Clutch Problem</option>
            <option value="Lights Not Working">Lights Not Working</option>
            <option value="Other">Other (Specify below)</option>
        </select>

        <label for="additional_note">Or Describe Issue (optional)</label>
        <textarea name="additional_note" id="additional_note" placeholder="Describe your issue if not listed above..." rows="4"></textarea>

        <label for="preferred_date">Preferred Date</label>
        <input type="date" name="preferred_date" min="<?= $minDate ?>" required>

        <label for="preferred_time">Preferred Time</label>
        <input type="time" name="preferred_time" min="<?= $minTime ?>" required>

        <button type="submit">Submit Request</button>
    </form>
</div>

</body>
</html>
