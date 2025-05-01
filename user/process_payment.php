<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';
include '../toast.php';

// Verify CSRF token (recommended)
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['payment_id'])) {
    header("Location: payment.php?toast=invalid_request");
    exit();
}

$payment_id = (int)$_POST['payment_id'];
$user_id = $_SESSION['id'];

// Start transaction
$conn->begin_transaction();

try {
    // Verify payment belongs to user and get details
    $query = "SELECT sp.id, sp.request_id, sp.mechanic_id, sp.amount, 
              mr.description, u.name as mechanic_name
              FROM service_payments sp
              JOIN maintenance_requests mr ON sp.request_id = mr.id
              JOIN users u ON sp.mechanic_id = u.id
              WHERE sp.id = ? AND sp.user_id = ? AND sp.status = 'pending'
              FOR UPDATE"; // Lock row for update
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $payment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception("Invalid payment or already processed");
    }
    
    $payment = $result->fetch_assoc();
    
    // Process payment (in real app, integrate with payment gateway here)
    $update = $conn->prepare("UPDATE service_payments 
                             SET status = 'paid', payment_date = NOW() 
                             WHERE id = ?");
    if (!$update) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $update->bind_param("i", $payment_id);
    $update->execute();
    
    if ($update->affected_rows !== 1) {
        throw new Exception("Payment update failed");
    }
    
    // Create notification for mechanic
    $message = "Payment of ₹" . number_format($payment['amount'], 2) . 
               " received for service: " . $payment['description'];
    
    $notif_stmt = $conn->prepare("INSERT INTO notifications 
                                 (user_id, request_id, type, message) 
                                 VALUES (?, ?, 'payment_received', ?)");
    if (!$notif_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $notif_stmt->bind_param("iis", $payment['mechanic_id'], $payment['request_id'], $message);
    $notif_stmt->execute();
    
    // Create notification for user
    $user_message = "Payment of ₹" . number_format($payment['amount'], 2) . 
                   " processed for service #" . $payment['request_id'];
    
    $user_notif = $conn->prepare("INSERT INTO notifications 
                                 (user_id, request_id, type, message) 
                                 VALUES (?, ?, 'payment_confirmation', ?)");
    if (!$user_notif) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $user_notif->bind_param("iis", $user_id, $payment['request_id'], $user_message);
    $user_notif->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Redirect with success message
    header("Location: payment.php?toast=payment_success");
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Payment Error: " . $e->getMessage());
    header("Location: payment.php?toast=payment_error");
    exit();
}
?>