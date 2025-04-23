<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
$user_name = $_SESSION['name'] ?? 'Admin';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id']);
    $name = trim($_POST['name']);
    $contact = trim($_POST['contact']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'mechanic';

    if ($_POST['role'] !== 'mechanic') {
        $error = "❌ Invalid role selected.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "❌ Invalid email format!";
    } elseif (strlen($password) < 6) {
        $error = "❌ Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $error = "❌ A user with this email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (id, name, email, password, contact, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id, $name, $email, $hashed_password, $contact, $role);

            if ($stmt->execute()) {
                $success = "✅ Mechanic added successfully!";
            } else {
                $error = "❌ Failed to add mechanic. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Mechanic</title>
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
        }

        #toggleBtn:hover {
            background-color: #17a2b8;
        }

        header h1 {
            margin: 0;
            flex-grow: 1;
        }

        .main-content {
            padding: 30px;
            transition: margin-left 0.3s ease;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 30px;
        }

        .form-container {
            background: #fff;
            max-width: 600px;
            margin: auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .form-container label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }

        .form-container input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .form-container input:focus {
            border-color: #17a2b8;
            outline: none;
        }

        .form-container .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 38px;
            cursor: pointer;
        }

        .form-container button {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            margin-top: 20px;
            transition: background 0.3s ease;
        }

        .form-container button:hover {
            background-color: #218838;
        }

        .message {
            max-width: 600px;
            margin: 20px auto;
            padding: 12px;
            text-align: center;
            border-radius: 5px;
        }

        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main shifted" id="main-content">
    <header>
        <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
        <h1>Add Mechanic</h1>
    </header>

    <?php if ($success): ?><div class="message success"><?= $success ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= $error ?></div><?php endif; ?>

    <div class="form-container">
        <form method="POST">
            <label for="id">User ID</label>
            <input type="number" id="id" name="id" required>

            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required>

            <label for="contact">Contact Number</label>
            <input type="text" id="contact" name="contact" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <div class="password-container">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="password-toggle" id="password-toggle">👁️</span>
            </div>

            <div class="password-container">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="password-toggle" id="confirm-password-toggle">👁️</span>
            </div>

            <input type="hidden" name="role" value="mechanic">
            <button type="submit">Add Mechanic</button>
        </form>
    </div>
</div>

<script>
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);

        toggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? '👁️❌' : '👁️';
        });
    }

    setupPasswordToggle('password-toggle', 'password');
    setupPasswordToggle('confirm-password-toggle', 'confirm_password');
</script>


</body>
</html>




👁