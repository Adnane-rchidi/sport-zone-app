<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list_reservations.php?error=' . urlencode("Invalid reservation ID."));
    exit;
}

$reservation_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT id, date, heure_debut FROM reservations WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $reservation = $result->fetch_assoc();
    
    $reservation_datetime_start = new DateTime($reservation['date_reservation'] . ' ' . $reservation['heure_debut']);
    $current_datetime = new DateTime();

    if ($reservation_datetime_start < $current_datetime) {
        header('Location: list_reservations.php?error=' . urlencode("Cannot cancel a reservation that has already ended or started."));
        exit;
    }

    $stmt_cancel = $conn->prepare("UPDATE reservations SET statut = 'annulée' WHERE id = ? AND user_id = ?");
    $stmt_cancel->bind_param("ii", $reservation_id, $user_id);
    
    if ($stmt_cancel->execute()) {
        header('Location: list_reservations.php?msg=' . urlencode("Reservation cancelled successfully."));
    } else {
        header('Location: list_reservations.php?error=' . urlencode("Error cancelling reservation. Please try again."));
    }
    $stmt_cancel->close();

} else {
    header('Location: list_reservations.php?error=' . urlencode("Reservation not found or you do not have permission to cancel it."));
}
$stmt->close();
exit;
?>