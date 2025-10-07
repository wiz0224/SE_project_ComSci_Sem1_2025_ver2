<?php
session_start();
include 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Get the registration info
    $sql = "SELECT * FROM pending_registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Insert into receivers table (only bind 8 variables, status and date are hardcoded)
        $insert = $conn->prepare("INSERT INTO receivers (id, lastname, firstname, `c&y`, school, contact, email, address, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())");
        $insert->bind_param(
            "isssssss",
            $row['id'],
            $row['lastname'],
            $row['firstname'],
            $row['c&y'],
            $row['school'],
            $row['contact'],
            $row['email'],
            $row['address']
        );
        $insert->execute();

        // Remove from pending_registrations
        $delete = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
        $delete->bind_param("i", $id);
        $delete->execute();

        // Show confirmation message before redirect
        echo "<script>
            if (confirm('Are you sure you want to add " . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "?')) {
                window.location.href = 'accept.php';
            } else {
                window.location.href = 'accept.php';
            }
        </script>";
        exit();
    }
}

// Redirect to dashboard if not POST
header("Location: dashboard.php");
exit();
?>


