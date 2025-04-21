<?php
session_start();
$mysqli = new mysqli("localhost", "root", "", "car_maintenance_system");

$alert = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['login'])) {
        // LOGIN
        $email = $_POST['login_email'];
        $password = $_POST['login_password'];

        $result = $mysqli->query("SELECT * FROM users WHERE email='$email'");
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['id'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];

                // Redirect based on role
                if ($row['role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: user/dashboard.php");
                }
                exit();
            } else {
                $alert = "❌ Invalid password!";
            }
        } else {
            $alert = "❌ No user found!";
        }
    } elseif (isset($_POST['register'])) {
        // REGISTER
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm = $_POST['confirm_password'];
        $contact = $_POST['contact'];
        $user_role = $_POST['user_role']; // Fixed name here

        if ($password != $confirm) {
            $alert = "⚠️ Passwords do not match!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (name, email, password, contact, role) 
                      VALUES ('$name', '$email', '$hashed', '$contact', '$user_role')";
            if ($mysqli->query($query)) {
                $alert = "✅ Registration successful. Please login.";
            } else {
                $alert = "❌ Email already exists or registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auth | Car Maintenance System</title>
    <link rel="stylesheet" href="/CarMaintenanceSystem/style_auth.css">
    </head>
<body>
<div class="auth-container">
    <div class="form-box login">
        <h2>Login</h2>
        <form method="POST">
            <input type="email" name="login_email" placeholder="Email" required>
            <input type="password" name="login_password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>

    <div class="form-box register">
        <h2>Register</h2>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <input type="text" name="contact" placeholder="Contact Number" required>

            <select name="user_role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit" name="register">Register</button>
        </form>
    </div>
</div>

<?php if ($alert): ?>
<div class="popup"><?php echo $alert; ?></div>
<?php endif; ?>

</body>
</html>
