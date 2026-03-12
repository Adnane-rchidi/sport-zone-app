<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_closed_hours.php?error=" . urlencode("Invalid closed hour ID provided for deletion."));
    exit;
}

$id = intval($_GET["id"]);

$stmt_delete = $conn->prepare("DELETE FROM date_heures_fermees WHERE id = ?");
$stmt_delete->bind_param("i", $id);

if ($stmt_delete->execute()) {
    header("Location: admin_closed_hours.php?msg=" . urlencode("Closed hour deleted successfully."));
    exit;
} else {
    header("Location: admin_closed_hours.php?error=" . urlencode("Error deleting closed hour: " . $stmt_delete->error));
    exit;
}

$stmt_delete->close();
$conn->close();
?>