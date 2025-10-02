<?php
session_start();
include 'conn.php';

// Assuming you have the admin's email stored in a variable $admin_email
$_SESSION['email'] = $admin_email; // $admin_email should be the admin's email from your database

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Get the registration info
    $sql = "SELECT * FROM pending_registrations WHERE id = $id";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Insert into receivers table
        $stmt = $conn->prepare("INSERT INTO receivers (id, lastname, firstname, `c&y`, school, email, address, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        $stmt->bind_param(
            "issssss",
            $row['id'],
            $row['lastname'],
            $row['firstname'],
            $row['c&y'],
            $row['school'],
            $row['email'],
            $row['address']
        );
        $stmt->execute();

        // Remove from pending_registrations
        $conn->query("DELETE FROM pending_registrations WHERE id = $id");
    }
}

// Redirect to dashboard
header("Location: dashboard.php");
exit();
?>
