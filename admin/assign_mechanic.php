<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

// Get request ID
$request_id = $_GET['id'] ?? null;
if (!$request_id) {
    die("No request ID provided.");
}

// Fetch preferred date and time for the maintenance request
$stmt = mysqli_prepare($conn, "SELECT preferred_date, preferred_time FROM maintenance_requests WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    die("Maintenance request not found.");
}

$maintenance = mysqli_fetch_assoc($result);
$preferred_date = $maintenance['preferred_date'];
$preferred_time = $maintenance['preferred_time'];

// Fetch available mechanics
$mechanics_query = "
SELECT u.id AS mechanic_id, u.name, u.contact, ms.date, ms.start_time, ms.end_time
FROM users u
JOIN mechanic_slots ms ON u.id = ms.mechanic_id
WHERE u.role = 'mechanic'
  AND ms.status = 'active'
  AND ms.date = ?
  AND ms.start_time <= ?
  AND ms.end_time >= ?
ORDER BY u.name, ms.start_time
";
$stmt2 = mysqli_prepare($conn, $mechanics_query);
mysqli_stmt_bind_param($stmt2, "sss", $preferred_date, $preferred_time, $preferred_time);
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

$mechanics = [];
while ($row = mysqli_fetch_assoc($result2)) {
    $mid = $row['mechanic_id'];
    if (!isset($mechanics[$mid])) {
        $mechanics[$mid] = [
            'id' => $mid,
            'name' => $row['name'],
            'contact' => $row['contact'],
            'slots' => []
        ];
    }
    $mechanics[$mid]['slots'][] = sprintf("%s | %s - %s",
        $row['date'],
        date('h:i A', strtotime($row['start_time'])),
        date('h:i A', strtotime($row['end_time']))
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mechanic_id = $_POST['mechanic_id'] ?? null;
    if ($mechanic_id) {
        // Update both mechanic_id and status in a single query
        $assign_sql = "UPDATE maintenance_requests SET mechanic_id = ?, status = 'Accepted', assigned = 'yes' WHERE id = ?";
        $stmt3 = mysqli_prepare($conn, $assign_sql);
        mysqli_stmt_bind_param($stmt3, "ii", $mechanic_id, $request_id);
        if (mysqli_stmt_execute($stmt3)) {
            header("Location: accept_requests.php?toast=assign_success");
            exit();
        } else {
            header("Location: accept_requests.php?toast=assign_error");
            exit();
        }
    } else {
        echo "<script>alert('⚠️ Please select a mechanic.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Mechanic</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 70px auto;
            padding: 30px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }
        label {
            font-size: 16px;
            font-weight: 600;
        }
        select, button {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        button {
            background: #28a745;
            color: #fff;
            border: none;
            transition: background 0.3s ease;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        .slots-listing, .selected-mechanic-slots {
            margin-top: 30px;
        }
        .mechanic-info {
            background: #fdfdfd;
            border-left: 5px solid #3498db;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            animation: fadeSlide 0.5s ease forwards;
            opacity: 0;
            transform: translateY(10px);
        }
        @keyframes fadeSlide {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .mechanic-name {
            font-weight: bold;
            font-size: 17px;
            color: #2c3e50;
        }
        .slots p {
            margin: 4px 0;
            color: #555;
            font-size: 14px;
        }
        .no-mechanics {
            text-align: center;
            color: #e74c3c;
            font-size: 16px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Assign Mechanic</h2>

    <?php if (empty($mechanics)): ?>
        <p class="no-mechanics">😔 No mechanics are available at the requested time slot.</p>
    <?php else: ?>
        <form action="assign_mechanic.php?id=<?= htmlspecialchars($request_id) ?>" method="POST">
            <label for="mechanic_id">Select Mechanic:</label>
            <select name="mechanic_id" id="mechanic_id" required onchange="showSlots(this.value)">
                <option value="" disabled selected>-- Select Mechanic --</option>
                <?php foreach ($mechanics as $mechanic): ?>
                    <option value="<?= htmlspecialchars($mechanic['id']) ?>">
                        <?= htmlspecialchars($mechanic['name']) ?> (<?= htmlspecialchars($mechanic['contact']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="selected-slots" class="selected-mechanic-slots"></div>
            <button type="submit">Assign Mechanic</button>
        </form>

        <div class="slots-listing">
            <?php foreach ($mechanics as $mechanic): ?>
                <div class="mechanic-info" id="mechanic-<?= htmlspecialchars($mechanic['id']) ?>" style="display:none;">
                    <div class="mechanic-name"><?= htmlspecialchars($mechanic['name']) ?> (<?= htmlspecialchars($mechanic['contact']) ?>)</div>
                    <div class="slots">
                        <?php foreach ($mechanic['slots'] as $slot): ?>
                            <p>📅 <?= htmlspecialchars($slot) ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<script>
function showSlots(id) {
    document.querySelectorAll('.mechanic-info').forEach(div => div.style.display = 'none');
    if (id) {
        const selectedDiv = document.getElementById('mechanic-' + id);
        if (selectedDiv) {
            selectedDiv.style.display = 'block';
        }
    }
}
</script>

</body>
</html>