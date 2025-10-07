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

// --- Step 1: Check for Confirmation Status ---
if (isset($_POST['confirmed']) && $_POST['confirmed'] === 'true') {
    // --- Execution block: User has confirmed (Clicked OK) ---

    $conn->begin_transaction();
    $success = false;

    try {
        // 1. Get the registration info
        $sql = "SELECT * FROM pending_registrations WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();

            // 2. Insert into receivers table (NOTE: Date will be NOW() as the acceptance date)
            $insert = $conn->prepare("INSERT INTO receivers (lastname, firstname, `c&y`, school, contact, email, address, status, date) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            $insert->bind_param(
                "sssssss",
                $row['lastname'],
                $row['firstname'],
                $row['c&y'],
                $row['school'],
                $row['contact'],
                $row['email'],
                $row['address']
            );

            if ($insert->execute()) {
                // 3. Remove from pending_registrations
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

        }

    } catch (Exception $e) {
        $conn->rollback();
    }

    // Redirect to accept.php
    if ($success) {
        header("Location: accept.php?status=accepted");
        exit();
    } else {
        header("Location: accept.php?status=error&msg=database_error");
        exit();
    }
} else {
    // --- Confirmation prompt block: User needs to confirm (Initial POST) ---

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
        header("Location: accept.php?status=error&msg=record_not_found_pre");
        exit();
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head><title>Confirm Acceptance</title></head>
    <body>
    <script>
        var id = <?php echo json_encode($id); ?>;
        var fullName = "<?php echo $full_name; ?>";

       if (confirm('Are you sure you want to ACCEPT and add ' + fullName + ' to the list of assistance recipients?')) { // User clicked OK: resubmit the form with a confirmation flag
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