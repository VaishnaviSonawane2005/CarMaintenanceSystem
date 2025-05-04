<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

include '../db_connect.php';

$user_id = $_SESSION['id'];
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Check for new notifications and suggestions
$query = "SELECT COUNT(*) as new_count FROM notifications n
          LEFT JOIN mechanic_suggestions ms ON n.request_id = ms.request_id
          WHERE n.user_id = ? AND (n.created_at > ? OR ms.created_at > ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $last_check, $last_check);
$stmt->execute();
$result = $stmt->fetch_assoc();

header('Content-Type: application/json');
echo json_encode([
    'new_notifications' => $result['new_count'] > 0,
    'count' => $result['new_count'],
    'last_check' => date('Y-m-d H:i:s')
]);
?>