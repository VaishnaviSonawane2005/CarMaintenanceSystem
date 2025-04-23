<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

$user_name = $_SESSION['name'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? 'Admin';

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    // Fetch request details based on request_id
    $request_query = "SELECT * FROM maintenance_requests WHERE id = ?";
    $stmt = $conn->prepare($request_query);
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $request_result = $stmt->get_result();
    $request = $request_result->fetch_assoc();
    $stmt->close();

    if ($request) {
        // Fetch available mechanics for the preferred date and time
        $mechanic_query = "
            SELECT m.id, m.name, m.contact, ms.id AS slot_id, ms.available_date, ms.available_time
            FROM mechanics m
            JOIN mechanic_slots ms ON m.id = ms.mechanic_id
            WHERE ms.available_date = ? AND ms.available_time = ? AND ms.status = 'available'
        ";
        $mechanic_stmt = $conn->prepare($mechanic_query);
        $mechanic_stmt->bind_param("ss", $request['preferred_date'], $request['preferred_time']);
        $mechanic_stmt->execute();
        $mechanics_result = $mechanic_stmt->get_result();
        $mechanic_stmt->close();
    }
}

// Assign mechanic if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mechanic_id']) && isset($_POST['slot_id'])) {
    $mechanic_id = $_POST['mechanic_id'];
    $slot_id = $_POST['slot_id'];

    // Update mechanic slot status to 'booked'
    $update_slot_query = "UPDATE mechanic_slots SET status = 'booked' WHERE id = ?";
    $update_stmt = $conn->prepare($update_slot_query);
    $update_stmt->bind_param("i", $slot_id);
    if ($update_stmt->execute()) {
        // Assign mechanic to the maintenance request
        $assign_mechanic_query = "UPDATE maintenance_requests SET mechanic_id = ? WHERE id = ?";
        $assign_stmt = $conn->prepare($assign_mechanic_query);
        $assign_stmt->bind_param("ii", $mechanic_id, $request_id);
        $assign_stmt->execute();

        // Redirect to the dashboard or a confirmation page
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Failed to assign mechanic.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Mechanic - Admin Panel</title>
    <link rel="stylesheet" href="../dashboard.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0; padding: 0;
            background-color: #f4f6f9;
        }

        .sidebar {
            width: 240px; height: 100vh;
            background-color: #1e1e2f;
            color: #fff; position: fixed;
            padding: 20px;
        }

        .sidebar h2 {
            font-size: 22px;
        }

        .sidebar .profile {
            text-align: center;
            margin: 20px 0;
        }

        .sidebar .profile img {
            width: 80px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li {
            padding: 10px;
            margin: 8px 0;
            transition: background 0.3s ease;
        }

        .sidebar ul li:hover {
            background: #17a2b8;
            border-radius: 5px;
        }

        .sidebar ul li a {
            color: #fff; text-decoration: none;
            display: block;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #17a2b8;
            color: white;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .assign-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        .assign-btn:hover {
            background-color: #218838;
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
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <div class="profile">
        <img src="../img/car-service.png" alt="Profile">
        <p><?= htmlspecialchars($user_name); ?></p>
    </div>
    <ul>
        <li><a href="dashboard.php">🏠 Dashboard</a></li>
        <li><a href="accept_request.php">✔️ Accept Requests</a></li>
        <li><a href="assign_mechanic.php">👨‍🔧 Assign Mechanic</a></li>
        <li><a href="notifications.php">🔔 Notifications</a></li>
        <li><a href="billing.php">📄 Billing</a></li>
        <li><a href="payment.php">💰 Payments</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Assign Mechanic for Request #<?= $request_id ?></h2>

    <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <p><strong>User Name:</strong> <?= htmlspecialchars($request['user_name']) ?></p>
    <p><strong>Contact:</strong> <?= htmlspecialchars($request['user_contact']) ?></p>
    <p><strong>Description:</strong> <?= htmlspecialchars($request['description']) ?></p>
    <p><strong>Preferred Date:</strong> <?= $request['preferred_date'] ?></p>
    <p><strong>Preferred Time:</strong> <?= $request['preferred_time'] ?></p>

    <h3>Select Mechanic</h3>
    <?php if (mysqli_num_rows($mechanics_result) > 0): ?>
        <form method="POST">
            <table>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Availability</th>
                    <th>Action</th>
                </tr>
                <?php while ($mechanic = mysqli_fetch_assoc($mechanics_result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($mechanic['name']) ?></td>
                        <td><?= htmlspecialchars($mechanic['contact']) ?></td>
                        <td><?= $mechanic['available_date'] . ' ' . $mechanic['available_time'] ?></td>
                        <td>
                            <input type="radio" name="mechanic_id" value="<?= $mechanic['id'] ?>" required>
                            <input type="hidden" name="slot_id" value="<?= $mechanic['slot_id'] ?>">
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <button type="submit" class="assign-btn">Assign Mechanic</button>
        </form>
    <?php else: ?>
        <p>No available mechanics at the selected time.</p>
    <?php endif; ?>
</div>

</body>
</html>
