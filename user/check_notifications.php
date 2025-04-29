<?php
session_start();
if (!isset($_SESSION['id'])) {
    exit(json_encode(['new_notifications' => false]));
}

include '../db_connect.php';

$user_id = $_GET['user_id'] ?? $_SESSION['id'];
$last_check = $_GET['last_check'] ?? 0;

// Check for new notifications with their types
$query = "SELECT COUNT(*) as count, n.type 
          FROM notifications n
          WHERE n.user_id = ? AND n.created_at > FROM_UNIXTIME(?)
          GROUP BY n.type";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $last_check);
$stmt->execute();
$result = $stmt->get_result();

$updates = [];
while ($row = $result->fetch_assoc()) {
    $updates[$row['type']] = (int)$row['count'];
}

echo json_encode([
    'new_notifications' => array_sum($updates) > 0,
    'updates' => $updates,
    'timestamp' => time()
]);
?>