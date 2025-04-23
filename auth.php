<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "car_maintenance_system");

$alert = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // LOGIN
    if (isset($_POST['login'])) {
        $email = trim($_POST['login_email']);
        $password = $_POST['login_password'];

        // Try USERS table for admin, user, and mechanic
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user_result = $stmt->get_result();

        if ($user_result->num_rows === 1) {
            $user = $user_result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($_SESSION['role'] === 'mechanic') {
                    header("Location: mechanic/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();
            } else {
                $alert = "❌ Invalid password!";
            }
        } else {
            $alert = "❌ No account found with this email!";
        }
    }

    // REGISTER
    elseif (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $contact = trim($_POST['contact']);
        $role = $_POST['role']; // Role is dynamic for admin or mechanic

        if ($password !== $confirm) {
            $alert = "⚠️ Passwords do not match!";
        } elseif (strlen($password) < 6) {
            $alert = "⚠️ Password must be at least 6 characters!";
        } else {
            // Check if email already exists in the users table
            $check = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $alert = "❌ Email already exists!";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                // Insert user data into users table
                $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, contact, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hashed, $contact, $role);

                if ($stmt->execute()) {
                    $alert = "✅ Registration successful. Please login.";
                } else {
                    $alert = "❌ Registration failed!";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login | Car Maintenance</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 500px;
            background: #fff;
            margin: 80px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form label {
            display: block;
            margin: 12px 0 5px;
            font-weight: bold;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        .btn {
            background: #17a2b8;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background: #138496;
        }
        .alert {
            background-color: #ffeeba;
            color: #856404;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .tabs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .tabs button {
            flex: 1;
            background-color: #e9ecef;
            border: none;
            padding: 10px;
            cursor: pointer;
        }
        .tabs button.active {
            background-color: #17a2b8;
            color: #fff;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
    </style>
    <script>
        function showForm(form) {
            document.querySelectorAll('.form-section').forEach(f => f.classList.remove('active'));
            document.querySelectorAll('.tabs button').forEach(b => b.classList.remove('active'));
            document.getElementById(form).classList.add('active');
            document.getElementById(form + "-btn").classList.add('active');
        }

        window.onload = () => {
            showForm('login'); // Default view
        };
    </script>
</head>
<body>

<div class="container">
    <h2>Car Maintenance System</h2>

    <?php if (!empty($alert)) echo "<div class='alert'>$alert</div>"; ?>

    <div class="tabs">
        <button id="login-btn" onclick="showForm('login')">🔐 Login</button>
        <button id="register-btn" onclick="showForm('register')">📝 Register</button>
    </div>

    <!-- Login Form -->
    <div class="form-section" id="login">
        <form method="POST">
            <label for="login_email">Email</label>
            <input type="email" name="login_email" required>

            <label for="login_password">Password</label>
            <input type="password" name="login_password" required>

            <button type="submit" name="login" class="btn">Login</button>
        </form>
    </div>

    <!-- Registration Form -->
    <div class="form-section" id="register">
        <form method="POST">
            <label for="name">Full Name</label>
            <input type="text" name="name" required>

            <label for="email">Email</label>
            <input type="email" name="email" required>

            <label for="contact">Contact Number</label>
            <input type="text" name="contact" required>

            <label for="password">Password</label>
            <input type="password" name="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" required>

            <label for="role">Role</label>
            <select name="role" required>
                <option value="user">User</option>
                <option value="mechanic">Mechanic</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" name="register" class="btn">Register</button>
        </form>
    </div>
</div>

</body>
</html>
