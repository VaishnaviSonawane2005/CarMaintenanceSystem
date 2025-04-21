<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

// Get user details
$user_name = $_SESSION['name'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? 'Admin';

// Accept logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'];
    $update = mysqli_query($conn, "UPDATE maintenance_requests SET status='accepted' WHERE id=$request_id");
    if ($update) {
        header("Location: accept_request.php?success=1");
        exit();
    }
}

// 🟢 Updated query with JOIN to get name and contact
$result = mysqli_query($conn, "
    SELECT mr.id, mr.description, u.name AS user_name, u.contact AS user_contact 
    FROM maintenance_requests mr
    JOIN users u ON mr.user_id = u.id
    WHERE mr.status='pending'
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accept Requests - Admin Panel</title>
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
            animation: fadeIn 0.4s ease-in-out;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        table {
            width: 100%;
            background: #fff;
            border-collapse: collapse;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        th, td {
            padding: 14px;
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

        .accept-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .accept-btn:hover {
            background-color: #218838;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }

        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
    <script>
        function confirmAccept() {
            return confirm("Are you sure you want to accept this request?");
        }
    </script>
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
        <li><a href="dashboard.php">🏠 Home</a></li>
        <li><a href="accept_request.php">✔️ Accept Requests</a></li>
        <li><a href="assign_mechanic.php">👨‍🔧 Assign Mechanic</a></li>
        <li><a href="notifications.php">🔔 Notifications</a></li>
        <li><a href="billing.php">📄 Billing</a></li>
        <li><a href="payment.php">💰 View Payments</a></li>
        <li><a href="../logout.php">🚪 Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Pending Maintenance Requests</h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">✅ Request accepted successfully!</div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>User Name</th>
            <th>Contact No.</th>
            <th>Description</th>
            <th>Action</th>
        </tr>
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['user_contact']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td>
                        <form method="post" action="accept_request.php" onsubmit="return confirmAccept();">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="accept-btn">Accept</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5">No pending requests found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
