<?php
$mysqli = new mysqli("localhost", "root", "", "car_maintenance_system");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "<h2>Hashing mechanics' passwords...</h2>";

$query = "SELECT id, email, password FROM mechanics";
$result = $mysqli->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];
        $email = $row['email'];
        $password = $row['password'];

        // If password is already hashed, skip
        if (password_get_info($password)['algo']) {
            echo "✅ Already hashed: $email <br>";
            continue;
        }

        // Hash and update password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $mysqli->prepare("UPDATE mechanics SET password = ? WHERE id = ?");
        $update->bind_param("si", $hashed, $id);
        if ($update->execute()) {
            echo "🔐 Hashed and updated: $email <br>";
            
        } else {
            echo "❌ Failed to update: $email <br>";
        }
        $update->close();
    }
} else {
    echo "No mechanics found.";
}

$mysqli->close();
?>
