<?php
session_start();
session_destroy();
header("Location: /CarMaintenanceSystem/auth.php");
exit();
?>
