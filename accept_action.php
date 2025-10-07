<?php
session_start();
include 'conn.php';

// Ensure the request is POST and contains the ID
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = intval($_POST['id']);

// --- Step 1: Check for Confirmation Status ---
// If 'confirmed' is set to 'true', the user clicked OK and we proceed with database actions.

if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
    // --- Execution block: User has confirmed (Clicked OK) ---

    // Get the registration info
    $sql = "SELECT * FROM pending_registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Start transaction for atomicity (ensures both succeed or both fail)
        $conn->begin_transaction();
        $success = false;
        
        try {
            // Insert into receivers table
            // Note: We use the ID from pending, assuming it's intended to be retained or that 'receivers.id' is not auto-increment.
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

            if ($insert->execute()) {
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
                $conn->rollback();
            }

        } catch (Exception $e) {
            $conn->rollback();
            // Optional: Log the error
        }
        
        // Redirect to accept.php to show the updated pending list
        if ($success) {
            header("Location: accept.php?status=accepted");
            exit();
        } else {
            // Failed to move record
            header("Location: accept.php?status=error&msg=database_error");
            exit();
        }
    } else {
        // Record not found in pending_registrations
        header("Location: accept.php?status=error&msg=record_not_found");
        exit();
    }
} else {
    // --- Confirmation prompt block: User needs to confirm (Initial POST) ---

    // Get the registration info to display the name in the prompt
    $sql = "SELECT firstname, lastname FROM pending_registrations WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $full_name = 'this registrant';
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $full_name = htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
    } else {
        // Record not found before confirmation.
        header("Location: accept.php?status=error&msg=record_not_found_pre");
        exit();
    }

    // Use JavaScript to prompt for confirmation and resubmit the form if confirmed
    ?>
    <!DOCTYPE html>
    <html>
    <body>
    <script>
        // Store form data to be submitted later
        var id = <?php echo json_encode($id); ?>;
        var fullName = "<?php echo $full_name; ?>";

        if (confirm('Are you sure you want to add ' + fullName + ' to the list?')) {
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
            window.location.href = 'accept.php?status=cancelled';
        }
    </script>
    </body>
    </html>
    <?php
    exit();
}
?>