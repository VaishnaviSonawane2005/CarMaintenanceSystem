<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "car_maintenance_system");

$alert = "";
$alert_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // LOGIN
    if (isset($_POST['login'])) {
        $email = trim($_POST['login_email']);
        $password = $_POST['login_password'];

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

                $role_name = ucfirst($user['role']);
                $alert = "🎉 Welcome back, {$user['name']}! Redirecting to {$role_name} Dashboard...";
                $alert_type = "success";
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '{$user['role']}/dashboard.php';
                    }, 1500);
                </script>";
            } else {
                $alert = "❌ Invalid password! Please try again.";
                $alert_type = "error";
            }
        } else {
            $alert = "❌ No account found with this email!";
            $alert_type = "error";
        }
    }

    // REGISTER (Only for users)
    elseif (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $contact = trim($_POST['contact']);
        $role = 'user'; // Force role to 'user' for registration

        if ($password !== $confirm) {
            $alert = "⚠️ Passwords do not match!";
            $alert_type = "warning";
        } elseif (strlen($password) < 6) {
            $alert = "⚠️ Password must be at least 6 characters!";
            $alert_type = "warning";
        } else {
            $check = $mysqli->prepare("SELECT * FROM users WHERE email = ?");
            $check->bind_param("s", $email);
            $check->execute();
            $result = $check->get_result();

            if ($result->num_rows > 0) {
                $alert = "❌ Email already exists!";
                $alert_type = "error";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("INSERT INTO users (name, email, password, contact, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $name, $email, $hashed, $contact, $role);

                if ($stmt->execute()) {
                    $alert = "✅ Registration successful! Please login with your credentials.";
                    $alert_type = "success";
                } else {
                    $alert = "❌ Registration failed! Please try again.";
                    $alert_type = "error";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Maintenance System | Authentication</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="style_auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container animate__animated animate__fadeIn">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Car Maintenance</h1>
                <p>Keep your vehicle in perfect condition</p>
            </div>

            <?php if (!empty($alert)): ?>
                <div class="alert alert-<?php echo $alert_type; ?> animate__animated animate__fadeIn">
                    <?php echo $alert; ?>
                </div>
            <?php endif; ?>

            <div class="tabs">
                <button id="login-btn" class="tab-btn active" onclick="showForm('login')">Login</button>
                <button id="register-btn" class="tab-btn" onclick="showForm('register')">Register</button>
                <div class="tab-indicator"></div>
            </div>

            <div class="form-container">
                <!-- Login Form -->
                <div class="form-section active" id="login">
                    <form method="POST">
                        <div class="input-group">
                            <input type="email" name="login_email" placeholder="Email Address" required>
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        </div>
                        <div class="input-group">
                            <input type="password" name="login_password" placeholder="Password" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary">
                            <span>Login</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                    <div class="form-footer">
                        <p>Don't have an account? <a href="#" onclick="showForm('register')">Register here</a></p>
                    </div>
                </div>

                <!-- Registration Form -->
                <div class="form-section" id="register">
                    <form method="POST">
                        <div class="input-group">
                            <input type="text" name="name" placeholder="Full Name" required>
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                        </div>
                        <div class="input-group">
                            <input type="email" name="email" placeholder="Email Address" required>
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        </div>
                        <div class="input-group">
                            <input type="text" name="contact" placeholder="Contact Number" required>
                            <span class="input-icon"><i class="fas fa-phone"></i></span>
                        </div>
                        <div class="input-group">
                            <input type="password" name="password" placeholder="Password (min 6 characters)" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                        <div class="input-group">
                            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                        <button type="submit" name="register" class="btn btn-primary">
                            <span>Register</span>
                            <i class="fas fa-user-plus"></i>
                        </button>
                    </form>
                    <div class="form-footer">
                        <p>Already have an account? <a href="#" onclick="showForm('login')">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function showForm(form) {
            // Update tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(form + '-btn').classList.add('active');
            
            // Update forms
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(form).classList.add('active');
            
            // Move indicator
            const indicator = document.querySelector('.tab-indicator');
            const activeBtn = document.querySelector('.tab-btn.active');
            indicator.style.width = `${activeBtn.offsetWidth}px`;
            indicator.style.left = `${activeBtn.offsetLeft}px`;
        }

        // Initialize tab indicator position
        window.onload = function() {
            const activeBtn = document.querySelector('.tab-btn.active');
            const indicator = document.querySelector('.tab-indicator');
            indicator.style.width = `${activeBtn.offsetWidth}px`;
            indicator.style.left = `${activeBtn.offsetLeft}px`;
        };
    </script>
</body>
</html>