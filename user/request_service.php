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
    $issue_types = $_POST['issue_type'] ?? [];

    if (empty($issue_types)) {
        $error = "Please select at least one maintenance issue.";
    } else {
        $issue_type = mysqli_real_escape_string($conn, implode(", ", $issue_types));
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Request Maintenance</title>
<link rel="stylesheet" href="../dashboard.css">
<style>
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
        width: 50px;
    }

    #toggleBtn:hover {
        background-color: #17a2b8;
    }

    header h1 {
        margin: 0;
        flex-grow: 1;
    }

    form {
        background: #fff;
        max-width: 600px;
        margin: 20px auto;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    label {
        display: block;
        margin: 10px 0 5px;
    }

    input[type="text"],
    input[type="date"],
    input[type="time"],
    textarea {
        width: 100%;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }

    button {
        background: #28a745;
        color: white;
        font-weight: bold;
        padding: 10px;
        border-radius: 8px;
        border: none;
        width: 100%;
        margin-top: 15px;
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

    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .checkbox-group label {
        background: #f0f2f5;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.3s;
        flex: 1 1 45%;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
    }

    .checkbox-group label:hover {
        background: #d9e2ec;
    }

    .checkbox-group input[type="checkbox"] {
        transform: scale(1.2);
    }
</style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main shifted" id="main-content">
    <header>
        <button id="toggleBtn" onclick="toggleSidebar()">&#9776;</button>
        <h1>Request Maintenance</h1>
    </header>

    <?php if (isset($_GET['success'])): ?>
        <div class="message success-message">✅ Request submitted successfully!</div>
    <?php elseif (!empty($error)): ?>
        <div class="message error-message"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Maintenance Issue</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="issue_type[]" value="Oil Change"> Oil Change</label>
            <label><input type="checkbox" name="issue_type[]" value="Brake Inspection"> Brake Inspection</label>
            <label><input type="checkbox" name="issue_type[]" value="Engine Tune-Up"> Engine Tune-Up</label>
            <label><input type="checkbox" name="issue_type[]" value="Tire Rotation"> Tire Rotation</label>
            <label><input type="checkbox" name="issue_type[]" value="Battery Check"> Battery Check</label>
            <label><input type="checkbox" name="issue_type[]" value="AC Repair"> AC Repair</label>
            <label><input type="checkbox" name="issue_type[]" value="Suspension Issue"> Suspension Issue</label>
            <label><input type="checkbox" name="issue_type[]" value="Clutch Problem"> Clutch Problem</label>
            <label><input type="checkbox" name="issue_type[]" value="Lights Not Working"> Lights Not Working</label>
            <label><input type="checkbox" name="issue_type[]" value="Other"> Other (Specify below)</label>
        </div>

        <label for="additional_note">Additional Notes</label>
        <textarea name="additional_note" id="additional_note" rows="4" placeholder="Add any extra details..."></textarea>

        <label for="preferred_date">Preferred Date</label>
        <input type="date" name="preferred_date" id="preferred_date" min="<?= $minDate ?>" required>

        <label for="preferred_time">Preferred Time</label>
        <input type="time" name="preferred_time" id="preferred_time" required>

        <button type="submit">Submit Request</button>
    </form>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Time restrictions (Client-side also)
const preferredDate = document.getElementById('preferred_date');
const preferredTime = document.getElementById('preferred_time');

preferredDate.addEventListener('change', function() {
    const today = new Date();
    const selectedDate = new Date(this.value);
    if (today.toDateString() === selectedDate.toDateString()) {
        // Today: Minimum 1 hour from now
        let minTime = new Date(today.getTime() + 60*60*1000);
        preferredTime.min = minTime.toTimeString().substring(0,5);
    } else {
        // Future dates: allow normal business hours (8:00 AM to 9:00 PM)
        preferredTime.min = '08:00';
        preferredTime.max = '21:00';
    }
});

// Trigger once on load too
preferredDate.dispatchEvent(new Event('change'));
</script>

</body>
</html>
