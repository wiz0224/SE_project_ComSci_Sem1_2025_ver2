<?php
session_start();
include 'conn.php';

if (!isset($_SESSION['email'])) {
    // Fallback or redirect if not logged in
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Get the registration info
    $sql = "SELECT * FROM pending_registrations WHERE id = $id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Send notification email
        $to = $row['email']; // recipient is the pending registrant
        $subject = "Educational Assistance Application Status";
        $message = "You are not ineligible for this educational assistance. Thank you!";
        $from = $_SESSION['email']; // sender is the logged-in admin
        $headers = "From: $from\r\n";
        mail($to, $subject, $message, $headers);

        // Remove from pending_registrations
        $conn->query("DELETE FROM pending_registrations WHERE id = $id");
    }
}

// Redirect back to accept page
header("Location: accept.php");
exit();
?>