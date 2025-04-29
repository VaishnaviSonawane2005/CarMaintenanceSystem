<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

// Get POST data
$request_id  = $_POST['request_id'] ?? null;
$mechanic_id = $_POST['mechanic_id'] ?? null;

if (!$request_id || !$mechanic_id) {
    die("❌ Missing request ID or mechanic ID.");
}

// Step 1: Fetch preferred date & time for the request
$maintenance_sql = "SELECT preferred_date, preferred_time FROM maintenance_requests WHERE id = ?";
$stmt = mysqli_prepare($conn, $maintenance_sql);
if (!$stmt) {
    die("Prepare failed for fetching maintenance request: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $request_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("❌ No maintenance request found with ID: $request_id");
}

$maintenance = mysqli_fetch_assoc($result);
$preferred_date = $maintenance['preferred_date'];
$preferred_time = $maintenance['preferred_time'];

// Step 2: Update the maintenance request status to "Accepted"
$update_request_sql = "UPDATE maintenance_requests SET status = 'Accepted', updated_at = NOW() WHERE id = ?";
$stmt_request = mysqli_prepare($conn, $update_request_sql);
if (!$stmt_request) {
    die("Prepare failed for maintenance request update: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_request, "i", $request_id);
if (!mysqli_stmt_execute($stmt_request)) {
    die("Execution failed for maintenance request update: " . mysqli_stmt_error($stmt_request));
}

// Step 3: Update the assigned mechanic's slot to "assigned"
$update_slot_sql = "
    UPDATE mechanic_slots
    SET status = 'assigned', updated_at = NOW()
    WHERE mechanic_id = ?
      AND date = ?
      AND start_time <= ?
      AND end_time >= ?
      AND status = 'active'
";
$stmt_slot = mysqli_prepare($conn, $update_slot_sql);
if (!$stmt_slot) {
    die("Prepare failed for mechanic slot update: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_slot, "isss", $mechanic_id, $preferred_date, $preferred_time, $preferred_time);
if (!mysqli_stmt_execute($stmt_slot)) {
    die("Execution failed for mechanic slot update: " . mysqli_stmt_error($stmt_slot));
}

// Step 4: Redirect with success message
header("Location: assign_mechanic.php?id=$request_id&success=true");
exit();
?>
