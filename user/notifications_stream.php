<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    die("event: error\ndata: {\"error\":\"unauthorized\"}\n\n");
}

// Include database connection with error handling
try {
    include '../db_connect.php';
    
    // Verify connection is established
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("event: error\ndata: " . json_encode(['error' => $e->getMessage()]) . "\n\n");
}

$user_id = $_GET['user_id'] ?? $_SESSION['id'];
$last_event_id = $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0;

// Close session to allow other requests
session_write_close();

// Prevent timeout
set_time_limit(0);
ignore_user_abort(true);

// Immediate flush buffer
if (ob_get_level()) ob_end_flush();
ob_implicit_flush(true);

try {
    while (true) {
        // Check if connection is still alive
        if (!$conn || $conn->ping() === false) {
            throw new Exception("Database connection lost");
        }

        // Check for new notifications
        $query = "SELECT n.id, n.message, n.type, n.created_at, n.request_id,
                 mr.status as request_status, mr.description, 
                 mr.preferred_date, mr.preferred_time, mr.created_at as request_created,
                 mr.updated_at as last_updated,
                 u.name as mechanic_name, u.contact as mechanic_contact,
                 ms.suggestion
                 FROM notifications n
                 LEFT JOIN maintenance_requests mr ON n.request_id = mr.id
                 LEFT JOIN users u ON mr.mechanic_id = u.id
                 LEFT JOIN mechanic_suggestions ms ON n.request_id = ms.request_id AND ms.mechanic_id = mr.mechanic_id
                 WHERE n.user_id = ? AND n.id > ?
                 ORDER BY n.id DESC LIMIT 1";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ii", $user_id, $last_event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $notification = $result->fetch_assoc();
            $last_event_id = $notification['id'];
            
            $eventData = [
                'id' => $notification['id'],
                'type' => 'notification',
                'message' => $notification['message'],
                'status' => $notification['request_status'],
                'description' => $notification['description'],
                'mechanic_name' => $notification['mechanic_name'],
                'mechanic_contact' => $notification['mechanic_contact'],
                'suggestion' => $notification['suggestion'],
                'preferred_date' => $notification['preferred_date'],
                'preferred_time' => $notification['preferred_time'],
                'request_id' => $notification['request_id'],
                'request_created' => $notification['request_created'],
                'last_updated' => $notification['last_updated'],
                'timestamp' => $notification['created_at']
            ];
            
            echo "id: {$notification['id']}\n";
            echo "event: notification\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            
            // Immediately flush output
            if (ob_get_level()) ob_flush();
            flush();
        }
        
        // Check for status updates
        $query = "SELECT mr.id as request_id, mr.status, mr.updated_at, 
                 n.message, n.id as notification_id
                 FROM maintenance_requests mr
                 JOIN notifications n ON mr.id = n.request_id
                 WHERE mr.user_id = ? AND mr.updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND)
                 ORDER BY mr.updated_at DESC LIMIT 1";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $update = $result->fetch_assoc();
            
            $eventData = [
                'request_id' => $update['request_id'],
                'status' => $update['status'],
                'message' => $update['message'],
                'timestamp' => $update['updated_at']
            ];
            
            echo "id: status_" . time() . "\n";
            echo "event: status_update\n";
            echo "data: " . json_encode($eventData) . "\n\n";
            
            if (ob_get_level()) ob_flush();
            flush();
        }
        
        // Check if client disconnected
        if (connection_aborted()) {
            break;
        }
        
        // Wait 2 seconds before next check
        sleep(2);
    }
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    echo "event: error\ndata: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
} finally {
    // Clean up resources safely
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>