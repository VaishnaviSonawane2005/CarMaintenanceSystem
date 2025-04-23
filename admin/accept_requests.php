<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$user_name = $_SESSION['name'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? 'Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];

    $stmt = $conn->prepare("UPDATE maintenance_requests SET status='Accepted' WHERE id = ?");
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute()) {
        header("Location: assign_mechanic.php?id=$request_id");
        exit();
    } else {
        $error = "Failed to update request.";
    }
    $stmt->close();
}

// Fetch all pending requests with user info
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Accept Requests - Admin Panel</title>
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

        .accept-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
        }

        .accept-btn:hover {
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
    <script>
        function confirmAccept() {
            return confirm("Are you sure you want to accept this request?");
        }
    </script>
</head>
<body>

<!-- Sidebar -->
<?php include '../sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <h2>Pending Maintenance Requests</h2>

    <?php if (isset($error)): ?>
        <div class="error-message"><?= $error ?></div>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>User Name</th>
            <th>Contact</th>
            <th>Description</th>
            <th>Preferred Date</th>
            <th>Preferred Time</th>
            <th>Action</th>
        </tr>

        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['user_name']) ?></td>
                    <td><?= htmlspecialchars($row['user_contact']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= $row['preferred_date'] ?></td>
                    <td><?= $row['preferred_time'] ?></td>
                    <td>
                        <form method="post" onsubmit="return confirmAccept();">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="accept-btn">Accept</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No pending requests found.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
