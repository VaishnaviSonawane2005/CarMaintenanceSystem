<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

session_start();
if (!isset($_SESSION['id'])) {
    exit();
}

include '../db_connect.php';

$user_id = $_GET['user_id'] ?? $_SESSION['id'];
$last_id = 0;

if (isset($_SERVER['HTTP_LAST_EVENT_ID'])) {
    $last_id = (int)$_SERVER['HTTP_LAST_EVENT_ID'];
}

while (true) {
    // Check for new notifications with mechanic info
    $query = "SELECT n.*, mr.status, u.name as mechanic_name 
              FROM notifications n
              LEFT JOIN maintenance_requests mr ON n.request_id = mr.id
              LEFT JOIN users u ON mr.mechanic_id = u.id
              WHERE n.user_id = ? AND n.id > ? AND n.is_read = 0
              ORDER BY n.id DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $notification = $result->fetch_assoc();
        
        $event = [
            'update' => true,
            'type' => 'new',
            'message' => $notification['message'],
            'status' => $notification['status'] ?? null,
            'mechanic' => $notification['mechanic_name'] ?? null,
            'last_id' => $notification['id']
        ];
        
        echo "data: " . json_encode($event) . "\n\n";
        ob_flush();
        flush();
        
        $last_id = $notification['id'];
    }
    
    // Check for status updates with slot info
    $query = "SELECT mr.id, mr.status, n.message, ms.date, ms.start_time, ms.end_time
              FROM maintenance_requests mr
              JOIN notifications n ON mr.id = n.request_id
              LEFT JOIN mechanic_slots ms ON mr.id = ms.request_id
              WHERE mr.user_id = ? AND mr.status != 'Pending' 
              AND mr.updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $event = [
            'update' => true,
            'type' => 'status_update',
            'message' => "Status updated for request #{$row['id']}: {$row['status']}",
            'status' => $row['status'],
            'request_id' => $row['id'],
            'slot_info' => $row['date'] ? [
                'date' => $row['date'],
                'time' => date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time']))
            ] : null
        ];
        
        echo "data: " . json_encode($event) . "\n\n";
        ob_flush();
        flush();
    }
    
    sleep(5);
}
?>