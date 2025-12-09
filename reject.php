
<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: accept.php");
    exit();
}

$id = intval($_POST['id']);

// Fetch stored filenames (handle multiple possible column names)
$stmt = $conn->prepare("SELECT cor_file, school_id_file, school_id, id_file, cor, certificate FROM pending_registrations WHERE id = ?");
if (!$stmt) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
    header("Location: accept.php");
    exit();
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $_SESSION['message'] = "Record not found.";
    $_SESSION['message_type'] = 'warning';
    header("Location: accept.php");
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

// Determine filenames (try several possible column names)
$cor_filename = $row['cor_file'] ?? $row['cor'] ?? $row['certificate'] ?? null;
$school_id_filename = $row['school_id_file'] ?? $row['school_id'] ?? $row['id_file'] ?? null;


// Delete DB record
$del = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
if (!$del) {
    $_SESSION['message'] = "Delete prepare failed: " . $conn->error;
    $_SESSION['message_type'] = 'danger';
    header("Location: accept.php");
    exit();
}
$del->bind_param("i", $id);
if ($del->execute()) {
    $del->close();
    $_SESSION['message'] = "Registration rejected and removed.";
    $_SESSION['message_type'] = 'success';
    header("Location: accept.php?status=rejected");
    exit();
} else {
    $err = $del->error;
    $del->close();
    $_SESSION['message'] = "Failed to delete record: " . $err;
    $_SESSION['message_type'] = 'danger';
    header("Location: accept.php");
    exit();
}
?>