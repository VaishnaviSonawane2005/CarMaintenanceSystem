<?php
session_start();
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth.php");
    exit();
}

include '../db_connect.php';

if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
}

// Assign mechanic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mechanic_id = $_POST['id'];

    // Fetch mechanic details
    $mechanic = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM mechanics WHERE id = '$mechanic_id'"));

    // Update request with mechanic details
    $update = mysqli_query($conn, "UPDATE maintenance_requests 
        SET status = 'Accepted',
            mechanic_name = '{$mechanic['name']}',
            mechanic_contact = '{$mechanic['contact']}',
            mechanic_availability = '{$mechanic['availability']}'
        WHERE id = '$request_id'");

    // Set mechanic as busy
    mysqli_query($conn, "UPDATE mechanics SET availability = 'Busy' WHERE id = '$mechanic_id'");

    if ($update) {
        header("Location: all_requests.php?assigned=1");
        exit();
    } else {
        $error = "Failed to assign mechanic.";
    }
}

// Get mechanics
$mechanics = mysqli_query($conn, "SELECT * FROM mechanics WHERE availability = 'Available'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Mechanic</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f8f9fa; }
        form { background: white; padding: 25px; border-radius: 10px; max-width: 500px; margin: auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        select, button { padding: 10px; width: 100%; margin-top: 10px; border-radius: 5px; }
        button { background-color: #28a745; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #218838; }
    </style>
</head>
<body>

<h2>Assign Mechanic to Request #<?= $request_id ?></h2>

<?php if (!empty($error)): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<form method="POST">
    <label>Select Mechanic</label>
    <select name="mechanic_id" required>
        <option value="">-- Choose Mechanic --</option>
        <?php while ($row = mysqli_fetch_assoc($mechanics)): ?>
            <option value="<?= $row['id'] ?>"><?= $row['name'] ?> (<?= $row['contact'] ?>)</option>
        <?php endwhile; ?>
    </select>
    <button type="submit">Assign Mechanic</button>
</form>

</body>
</html>
