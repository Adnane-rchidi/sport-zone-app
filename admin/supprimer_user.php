<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_users.php?error=" . urlencode("Invalid user ID."));
    exit;
}

$user_id_to_delete = intval($_GET["id"]);

if ($user_id_to_delete == $_SESSION['user_id']) {
    header("Location: admin_users.php?error=" . urlencode("You cannot delete your own account."));
    exit;
}

$conn->begin_transaction();

try {
    $stmt_cancel_reservations = $conn->prepare("UPDATE reservations SET statut = 'annulée' WHERE user_id = ? AND (statut = 'en attente' OR statut = 'confirmée') AND CONCAT(date, ' ', heure_debut) > NOW()");
    $stmt_cancel_reservations->bind_param("i", $user_id_to_delete);
    $stmt_cancel_reservations->execute();
    $stmt_cancel_reservations->close();

    $stmt_delete_user = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt_delete_user->bind_param("i", $user_id_to_delete);

    if ($stmt_delete_user->execute()) {
        $conn->commit();
        header("Location: admin_users.php?msg=" . urlencode("User and their future bookings successfully deleted/cancelled."));
    } else {
        $conn->rollback();
        header("Location: admin_users.php?error=" . urlencode("Error deleting user: " . $stmt_delete_user->error));
    }
    $stmt_delete_user->close();

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    header("Location: admin_users.php?error=" . urlencode("Database error: " . $e->getMessage()));
}

exit;
?>