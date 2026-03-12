<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_terrains.php?error=" . urlencode("Invalid field ID."));
    exit;
}

$id = intval($_GET["id"]);

$stmt_check_reservations = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE terrain_id = ? AND (statut = 'en attente' OR statut = 'confirmée') AND CONCAT(date, ' ', heure_fin) > NOW()");
$stmt_check_reservations->bind_param("i", $id);
$stmt_check_reservations->execute();
$result_count = $stmt_check_reservations->get_result()->fetch_row()[0];
$stmt_check_reservations->close();

if ($result_count > 0) {
    header("Location: admin_terrains.php?error=" . urlencode("Cannot delete the field because there are active or confirmed future bookings associated with it. Please cancel those bookings first or deactivate the field instead."));
    exit;
} else {
    $stmt = $conn->prepare("DELETE FROM terrains WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_terrains.php?msg=" . urlencode("Field deleted successfully."));
    } else {
        header("Location: admin_terrains.php?error=" . urlencode("Error deleting field: " . $stmt->error));
    }
    $stmt->close();
}
exit;
?>