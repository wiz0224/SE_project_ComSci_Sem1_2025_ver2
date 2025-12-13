<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

include 'conn.php';

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: accept.php?error=invalid_request");
    exit();
}

$id = intval($_POST['id']);
if ($id <= 0) {
    header("Location: accept.php?error=invalid_id");
    exit();
}

// Step 1: Fetch filenames from your REAL database columns
$stmt = $conn->prepare("
    SELECT cor_file, school_id_file 
    FROM pending_registrations 
    WHERE id = ?
");
if (!$stmt) {
    $_SESSION['message'] = "Database error: " . $conn->error;
    $_SESSION['message_type'] = "danger";
    header("Location: accept.php");
    exit();
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $_SESSION['message'] = "Record not found.";
    $_SESSION['message_type'] = "warning";
    header("Location: accept.php");
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();

$cor_file     = $row['cor_file'] ?? null;
$school_id_file = $row['school_id_file'] ?? null;

// Step 2: Delete uploaded files if they exist
function deleteFileIfExists($filename) {
    if (!$filename) return;

    $path = __DIR__ . '/uploads/' . basename($filename);

    if (file_exists($path)) {
        unlink($path);
    }
}

deleteFileIfExists($cor_file);
deleteFileIfExists($school_id_file);

// Step 3: Delete the database row
$del = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
if (!$del) {
    $_SESSION['message'] = "Delete error: " . $conn->error;
    $_SESSION['message_type'] = "danger";
    header("Location: accept.php");
    exit();
}

$del->bind_param("i", $id);

if ($del->execute()) {
    $del->close();
    $_SESSION['message'] = "Registration rejected and removed.";
    $_SESSION['message_type'] = "success";
    header("Location: accept.php?status=rejected");
    exit();
} else {
    $err = $del->error;
    $del->close();
    $_SESSION['message'] = "Failed to delete record: " . $err;
    $_SESSION['message_type'] = "danger";
    header("Location: accept.php");
    exit();
}
?>
