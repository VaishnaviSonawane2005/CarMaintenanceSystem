<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

include '../db_connect.php';

$user_id = $_SESSION['id'];
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Check for new notifications
$query = "SELECT COUNT(*) as new_count FROM notifications 
          WHERE user_id = ? AND created_at > ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $user_id, $last_check);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'new_notifications' => $row['new_count'] > 0,
    'count' => $row['new_count'],
    'last_check' => date('Y-m-d H:i:s')
]);
?>