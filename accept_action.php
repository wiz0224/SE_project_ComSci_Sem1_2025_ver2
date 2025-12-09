<?php
session_start();
include 'conn.php';

// Ensure the user is logged in
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

// Ensure the request is POST and contains the ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: accept.php"); 
    exit();
}

$id = intval($_POST['id']);

// --- Check for Confirmation Status ---
if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
    // --- Execution block: User has confirmed (Clicked OK) ---

    $conn->begin_transaction();
    $success = false;

    try {
        // Get the registration info
        $sql = "SELECT * FROM pending_registrations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Insert into receivers table (NOTE: Date will be NOW() as the acceptance date)
            $insert = $conn->prepare("INSERT INTO receivers (lastname, firstname, `c&y`, school, contact, email, address, status, date_accepted) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            $insert->bind_param("sssssss", $row['lastname'], $row['firstname'], $row['c&y'], $row['school'], $row['contact'], $row['email'], $row['address']);
            
            if (!$insert->execute()) {
                throw new Exception("Error inserting into receivers: " . $insert->error);
            }
            $insert->close();

            // Delete from pending_registrations
            $delete = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $delete->bind_param("i", $id);
            if (!$delete->execute()) {
                throw new Exception("Error deleting from pending_registrations: " . $delete->error);
            }
            $delete->close();
            
            $conn->commit();
            $_SESSION['message'] = "Successfully accepted " . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . " to the list.";
            $_SESSION['message_type'] = 'success';
            header("Location: accept.php");
            exit();

        } else {
            throw new Exception("Registration with ID $id not found.");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "Acceptance failed: " . $e->getMessage();
        $_SESSION['message_type'] = 'danger';
        header("Location: accept.php");
        exit();
    }
} 
// --- Confirmation Display Block ---
else {
    // Fetch details for display in the confirmation dialog
    $sql = "SELECT firstname, lastname FROM pending_registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: accept.php");
        exit();
    }

    $row = $result->fetch_assoc();
    $full_name = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accept Applicant</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* ADD FONT CONSISTENCY */
        body {
            font-family: 'Roboto', sans-serif; 
        }
        /* Dark mode styling para sa confirmation dialog background */
        .dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
    <script>
        // CRITICAL: Apply dark mode class immediately BEFORE the confirm dialog is triggered.
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
        
        var id = <?php echo json_encode($id); ?>;
        var fullName = "<?php echo $full_name; ?>";

       if (confirm('Are you sure you want to ACCEPT and add ' + fullName + ' to the list of assistance recipients?')) { 
            // User clicked OK: resubmit the form with a confirmation flag
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'accept_action.php';

            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;

            var confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirmed';
            confirmInput.value = 'true';

            form.appendChild(idInput);
            form.appendChild(confirmInput);
            document.body.appendChild(form);
            form.submit();
        } else {
            // User clicked Cancel: go back to the pending list without making changes
            window.location.href = 'accept.php?status=acceptance_cancelled';
        }
    </script>
    </body>
    </html>