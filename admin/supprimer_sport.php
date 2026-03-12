<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_sports.php?error=" . urlencode("Invalid sport ID."));
    exit;
}

$id = intval($_GET["id"]);

$stmt_check_terrains = $conn->prepare("SELECT COUNT(*) FROM terrains WHERE sports_id = ?");
$stmt_check_terrains->bind_param("i", $id);
$stmt_check_terrains->execute();
$terrain_count = $stmt_check_terrains->get_result()->fetch_row()[0];
$stmt_check_terrains->close();

if ($terrain_count > 0) {
    header("Location: admin_sports.php?error=" . urlencode("Cannot delete this sport because there are fields associated with it. Please delete the associated fields first."));
    exit;
} else {
    $stmt = $conn->prepare("DELETE FROM sports WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_sports.php?msg=" . urlencode("Sport deleted successfully."));
    } else {
        header("Location: admin_sports.php?error=" . urlencode("Error deleting sport: " . $stmt->error));
    }
    $stmt->close();
}
exit;
?>