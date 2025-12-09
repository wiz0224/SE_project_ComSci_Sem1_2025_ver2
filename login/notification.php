<?php
session_start();
include 'conn.php';

// Ensure the user is logged in
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: accept.php");
    exit();
}

$id = intval($_POST['id']);
$reason = isset($_POST['reason']) ? $_POST['reason'] : "N/A (Reason not specified)";

// --- Check for Confirmation Status ---
if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
    // --- Execution block: User has confirmed (Clicked OK) ---

    $conn->begin_transaction();
    $success = false;
    $name_to_redirect = "registrant";

    try {
        // Get the registration info
        $sql = "SELECT * FROM pending_registrations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $name_to_redirect = $row['lastname'];
            // Remove from pending_registrations
            $delete = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $delete->bind_param("i", $id);

            if ($delete->execute()) {
                $conn->commit();
                $success = true;
            } else {
                $conn->rollback();
            }

        } else {
             // Record not found during execution, commit is not possible
             $conn->rollback();
        }

    } catch (Exception $e) {
        $conn->rollback();
    }
    
    // Redirect back to accept page with status
    if ($success) {
        header("Location: accept.php?status=notified&name=" . urlencode($name_to_redirect));
        exit();
    } else {
        header("Location: accept.php?status=error&msg=notification_failed");
        exit();
    }

} else {
    // --- Confirmation prompt block: User needs to confirm (Initial POST) ---

    $sql = "SELECT firstname, lastname, contact FROM pending_registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $full_name = 'this registrant';
    $contact = 'N/A';
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $full_name = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
        $contact = htmlspecialchars($row['contact']);
    } else {
        header("Location: accept.php?status=error&msg=record_not_found_pre");
        exit();
    }

    // Use JavaScript to prompt for confirmation
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Confirm Ineligibility Notification</title></head>
    <body>
    <script>
        var id = <?php echo json_encode($id); ?>;
        var fullName = "<?php echo $full_name; ?>";
        var contact = "<?php echo $contact; ?>";
        var reason = "<?php echo htmlspecialchars($reason); ?>";

        var message = 'IMPORTANT REMINDER: You must first call ' + fullName + ' at Contact Number: ' + contact + ' to inform him/her that they are not eligible.\n\n' +
              'Reason: ' + reason + '\n\n' +
              'After calling, press "OK" delete the record from the pending list.';

        if (confirm(message)) {
            // User clicked OK: resubmit the form with a confirmation flag
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'notification.php';

            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;

            var confirmInput = document.createElement('input');
            confirmInput.type = 'hidden';
            confirmInput.name = 'confirmed';
            confirmInput.value = 'true';
            
            var reasonInput = document.createElement('input'); // Pass reason again for context in execution block
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;


            form.appendChild(idInput);
            form.appendChild(confirmInput);
            form.appendChild(reasonInput);
            document.body.appendChild(form);
            form.submit();
        } else {
            // User clicked Cancel: go back to the pending list without making changes
            window.location.href = 'accept.php?status=notification_cancelled';
        }
    </script>
    </body>
    </html>
    <?php
    exit();
}
?>