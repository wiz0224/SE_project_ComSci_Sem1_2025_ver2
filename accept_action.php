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
            
            // Insert into receivers table (robust to different column names)
            function columnExistsLocal($conn, $table, $column) {
                $table = $conn->real_escape_string($table);
                $column = $conn->real_escape_string($column);
                $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
                return ($res && $res->num_rows > 0);
            }

            $lastname_cols = ['lastname','last_name','lname','surname','lastName'];
            $firstname_cols = ['firstname','first_name','fname','firstName'];
            $cy_cols = ['candy','course_year','c_y','courseyear','cy'];
            $school_cols = ['school','school_name'];
            $contact_cols = ['contact','contact_no','phone','contact_number'];
            $email_cols = ['email','email_address'];
            $address_cols = ['address','home_address'];
            $date_col_candidates = ['date_accepted','date','created_at'];

            $findCol = function($candidates) use ($conn) {
                foreach ($candidates as $c) {
                    if (columnExistsLocal($conn, 'receivers', $c)) return $c;
                }
                return null;
            };

            $ln = $findCol($lastname_cols);
            $fn = $findCol($firstname_cols);
            $name_col = $findCol(['name','full_name','fullname']);

            // If neither first nor last name columns exist, try combined name column
            if (!$ln && !$fn && $name_col) {
                // we'll insert combined name into single column
                $ln = null; $fn = null;
            }
            if (!$ln && !$fn && !$name_col) {
                throw new Exception("Receivers table is missing name columns (expected one of: " . implode(', ', array_merge($lastname_cols, $firstname_cols, ['name','full_name','fullname'])) . ").");
            }
            $cy = $findCol($cy_cols);
            $sc = $findCol($school_cols);
            $ct = $findCol($contact_cols);
            $em = $findCol($email_cols);
            $ad = $findCol($address_cols);
            $dt = $findCol($date_col_candidates) ?? null; // optional

            $cols = [];
            $place = [];
            $params = [];
            $types = '';

            if ($name_col && !$ln && !$fn) {
                $cols[] = "`$name_col`"; $place[] = '?'; $params[] = trim(($row['firstname'] ?? $row['firstName'] ?? '') . ' ' . ($row['lastname'] ?? $row['lastName'] ?? '')); $types .= 's';
            } else {
                if ($ln) { $cols[] = "`$ln`"; $place[] = '?'; $params[] = ($row['lastname'] ?? $row['lastName'] ?? ''); $types .= 's'; }
                if ($fn) { $cols[] = "`$fn`"; $place[] = '?'; $params[] = ($row['firstname'] ?? $row['firstName'] ?? ''); $types .= 's'; }
            }
            if ($cy) { $cols[] = "`$cy`"; $place[] = '?'; $params[] = ($row['candy'] ?? $row['course_year'] ?? $row['c_y'] ?? ''); $types .= 's'; }
            if ($sc) { $cols[] = "`$sc`"; $place[] = '?'; $params[] = ($row['school'] ?? ''); $types .= 's'; }
            if ($ct) { $cols[] = "`$ct`"; $place[] = '?'; $params[] = ($row['contact'] ?? ''); $types .= 's'; }
            if ($em) { $cols[] = "`$em`"; $place[] = '?'; $params[] = ($row['email'] ?? ''); $types .= 's'; }
            if ($ad) { $cols[] = "`$ad`"; $place[] = '?'; $params[] = ($row['address'] ?? ''); $types .= 's'; }

            // status (integer) only if exists
            if (columnExistsLocal($conn, 'receivers', 'status')) { $cols[] = "`status`"; $place[] = '?'; $params[] = 0; $types .= 'i'; }

            if ($dt) { $cols[] = "`$dt`"; $place[] = 'NOW()'; }

            $cols_sql = implode(', ', $cols);
            $place_sql = implode(', ', array_map(function($p){ return ($p === 'NOW()') ? 'NOW()' : '?'; }, $place));
            $insert_sql = "INSERT INTO receivers ({$cols_sql}) VALUES ({$place_sql})";

            $insert = $conn->prepare($insert_sql);
            if (!$insert) {
                throw new Exception("Prepare failed for receivers insert: " . $conn->error . " -- SQL: " . $insert_sql);
            }

            if (count($params) > 0) {
                $bind_names = [];
                $bind_names[] = $types;
                for ($i = 0; $i < count($params); $i++) $bind_names[] = &$params[$i];
                call_user_func_array([$insert, 'bind_param'], $bind_names);
            }

            if (!$insert->execute()) {
                throw new Exception("Error inserting into receivers: " . $insert->error . " -- SQL: " . $insert_sql);
            }
            $insert->close();

            // Delete from pending_registrations
            $delete = $conn->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $delete->bind_param("i", $id);
            if (!$delete->execute()) {
                throw new Exception("Error deleting from pending_registrations: " . $delete->error);
            }
            $delete->close();
            
            $new_receiver_id = $conn->insert_id;
            $conn->commit();
            $_SESSION['message'] = "Successfully accepted " . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . " to the list.";
            $_SESSION['message_type'] = 'success';
            // Redirect to schedule manager for the newly created receiver so you can set schedule immediately
            header("Location: schedule.php?view=id&id=" . intval($new_receiver_id));
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