<?php
$servername = "localhost";
$username = "root"; // Default for XAMPP
$password = "";     // Default for XAMPP
$database = "car_maintenance_system"; // Change this to your DB name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("<div style='
        background: linear-gradient(to right, #ff4e50, #f9d423);
        color: white;
        font-family: Arial, sans-serif;
        padding: 20px;
        border-radius: 10px;
        text-align: center;
        margin: 40px;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
        animation: pulse 1s infinite;
    '>
        <h2>Database Connection Failed!</h2>
        <p>Error: " . $conn->connect_error . "</p>
    </div>

    <style>
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.03); }
        100% { transform: scale(1); }
    }
    </style>");
}
?>
