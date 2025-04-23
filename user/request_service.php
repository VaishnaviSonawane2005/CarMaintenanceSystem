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
        /* Ensure no global body changes here, as it's already handled in dashboard.css */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #f0f2f5, #ffffff);
            overflow-x: hidden;
        }

        .main {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }

        .sidebar.active ~ .main-content {
            margin-left: 250px;
        }

        /* Header Styling */
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
        }

        #toggleBtn:hover {
            background-color: #17a2b8;
        }

        header h1 {
            margin: 0;
            flex-grow: 1;
        }

        /* Form Styling */
        form {
            background: #fff;
            max-width: 600px;
            margin: 0 auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        label, input, select, textarea {
            display: block;
            width: 100%;
            margin: 10px 0;
        }

        input, select, textarea {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            background: #28a745;
            color: white;
            font-weight: bold;
            padding: 10px;
            transition: background 0.3s;
        }

        button:hover {
            background: #218838;
        }

        .message {
            max-width: 600px;
            margin: 20px auto;
            padding: 12px;
            text-align: center;
            border-radius: 5px;
        }

        .success-message { background: #d4edda; color: #155724; }
        .error-message { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?> <!-- Sidebar included here as per your setup -->

<div class="main shifted" id="main-content">
    <header>
    <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
    <h1>Request Maintenance</h1>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="message success-message">✅ Request submitted successfully!</div>
    <?php elseif (!empty($error)): ?>
        <div class="message error-message"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="issue_type">Maintenance Issue</label>
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

        <label for="additional_note">Additional Notes</label>
        <textarea name="additional_note" id="additional_note" rows="4" placeholder="Add any extra details..."></textarea>

        <label for="preferred_date">Preferred Date</label>
        <input type="date" name="preferred_date" min="<?= $minDate ?>" required>

        <label for="preferred_time">Preferred Time</label>
        <input type="time" name="preferred_time" min="<?= $minTime ?>" required>

        <button type="submit">Submit Request</button>
    </form>
</div>

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('active');
    }
</script>

</body>
</html>
