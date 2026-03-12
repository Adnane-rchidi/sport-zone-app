<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: admin_reservations.php?error=' . urlencode("Invalid reservation ID."));
    exit;
}

$reservation_id = intval($_GET['id']);

$stmt_check = $conn->prepare("SELECT statut, date_reservation, heure_fin FROM reservations WHERE id = ?");
$stmt_check->bind_param("i", $reservation_id);
$stmt_check->execute();
$reservation = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if (!$reservation) {
    header('Location: admin_reservations.php?error=' . urlencode("Reservation not found."));
    exit;
}

$reservation_datetime_end = new DateTime($reservation['date_reservation'] . ' ' . $reservation['heure_fin']);
$current_datetime = new DateTime();

if ($reservation_datetime_end < $current_datetime) {
    header('Location: admin_reservations.php?error=' . urlencode("Cannot cancel a reservation that has already ended."));
    exit;
}
if ($reservation['statut'] === 'annulée') {
    header('Location: admin_reservations.php?error=' . urlencode("Reservation is already cancelled."));
    exit;
}

$stmt_cancel = $conn->prepare("UPDATE reservations SET statut = 'annulée' WHERE id = ?");
$stmt_cancel->bind_param("i", $reservation_id);

if ($stmt_cancel->execute()) {
    header('Location: admin_reservations.php?msg=' . urlencode("Reservation cancelled successfully by Admin."));
} else {
    header('Location: admin_reservations.php?error=' . urlencode("Error cancelling reservation: " . $stmt_cancel->error));
}
$stmt_cancel->close();
exit;
?>