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
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #17a2b8;
            --text: #333;
            --light: #f8f9fa;
        }
        
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--text);
        }
        
        header {
            background-color: var(--primary);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        #toggleBtn {
            background-color: var(--secondary);
            color: white;
            border: none;
            font-size: 20px;
            padding: 8px 14px;
            cursor: pointer;
            border-radius: 4px;
            margin-right: 15px;
            transition: all 0.3s ease;
        }
        
        #toggleBtn:hover {
            background-color: var(--accent);
            transform: rotate(90deg);
        }
        
        header h1 {
            margin: 0;
            flex-grow: 1;
            font-size: 1.5rem;
            animation: fadeIn 0.5s ease;
        }
        
        .main-content {
            padding: 30px;
            animation: slideUp 0.5s ease;
        }
        
        .form-container {
            background: white;
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(20px);
            opacity: 0;
            animation: fadeInUp 0.5s ease 0.2s forwards;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(23,162,184,0.2);
            outline: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            cursor: pointer;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .password-toggle:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        button[type="submit"] {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        button[type="submit"]:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes fadeInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(150%);
            transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            border-left: 4px solid #28a745;
        }
        
        .toast.error {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="main shifted" id="main-content">
    <header>
        <button id="toggleBtn" onclick="toggleSidebar()">☰</button>
        <h1>Add Mechanic</h1>
    </header>

    <div class="main-content">
        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label for="id">User ID</label>
                    <input type="number" id="id" name="id" required>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                    <span class="password-toggle" id="password-toggle">👁️</span>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <span class="password-toggle" id="confirm-password-toggle">👁️</span>
                </div>
                
                <input type="hidden" name="role" value="mechanic">
                <button type="submit">Add Mechanic</button>
            </form>
        </div>
    </div>
</div>

<script>
    // Password toggle functionality
    function setupPasswordToggle(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        
        toggle.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            toggle.textContent = isPassword ? '🙈' : '👁️';
        });
    }
    
    setupPasswordToggle('password-toggle', 'password');
    setupPasswordToggle('confirm-password-toggle', 'confirm_password');
    
    // Toast notification
    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <span>${type === 'success' ? '✅' : '❌'}</span>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 10);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }
    
    // Show toast if there are messages
    window.onload = function() {
        <?php if ($success): ?>
            showToast('<?= $success ?>', 'success');
        <?php endif; ?>
        
        <?php if ($error): ?>
            showToast('<?= $error ?>', 'error');
        <?php endif; ?>
    };
</script>

</body>
</html>