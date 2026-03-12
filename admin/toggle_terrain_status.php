<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"]) || !isset($_GET["status"]) || !in_array($_GET["status"], ['0', '1'])) {
    header("Location: admin_terrains.php?error=" . urlencode("Invalid field ID or status parameter."));
    exit;
}

$id = intval($_GET["id"]);
$new_status = intval($_GET["status"]);

$stmt = $conn->prepare("UPDATE terrains SET is_active = ? WHERE id = ?");
$stmt->bind_param("ii", $new_status, $id);

if ($stmt->execute()) {
    header("Location: admin_terrains.php?msg=" . urlencode("Field status updated successfully."));
} else {
    header("Location: admin_terrains.php?error=" . urlencode("Error updating field status: " . $stmt->error));
}
$stmt->close();
exit;
?>