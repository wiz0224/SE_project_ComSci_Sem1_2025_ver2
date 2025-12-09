<?php
include 'conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return ($res && $res->num_rows > 0);
}

if (isset($_POST['submit'])) {
    // Use safe defaults to avoid undefined index warnings
    $lastname     = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $firstname    = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $birthdate    = isset($_POST['birthday']) ? $_POST['birthday'] : '';
    $course_year  = isset($_POST['course_year']) ? trim($_POST['course_year']) : '';
    $school       = isset($_POST['school']) ? trim($_POST['school']) : '';
    $contact      = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $email        = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address      = isset($_POST['address']) ? trim($_POST['address']) : '';
    $date_now     = date("Y-m-d H:i:s");
    $flagged      = 0;

    // Folder for uploads
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Function to sanitize filenames
    function safeFileName($name) {
        return preg_replace("/[^A-Za-z0-9._-]/", "_", $name);
    }

    // ---- COR file ----
    if (!isset($_FILES["cor"]) || !isset($_FILES["school_id"])) {
        die("Missing upload files.");
    }

    $corTmp  = $_FILES["cor"]["tmp_name"];
    $corName = uniqid("cor_") . "_" . safeFileName($_FILES["cor"]["name"]);
    $corPath = $targetDir . $corName;

    // ---- School ID file ----
    $idTmp   = $_FILES["school_id"]["tmp_name"];
    $idName  = uniqid("id_") . "_" . safeFileName($_FILES["school_id"]["name"]);
    $idPath  = $targetDir . $idName;

    // Move uploaded files (check return values)
    if (!move_uploaded_file($corTmp, $corPath)) {
        die("Failed to upload COR file.");
    }
    if (!move_uploaded_file($idTmp, $idPath)) {
        // cleanup the first file if second fails
        if (file_exists($corPath)) unlink($corPath);
        die("Failed to upload School ID file.");
    }

    // Decide whether DB has cor_file and school_id_file columns
    $hasCorCol = columnExists($conn, 'pending_registrations', 'cor_file');
    $hasIdCol  = columnExists($conn, 'pending_registrations', 'school_id_file');
    $includeFiles = $hasCorCol && $hasIdCol;

    // Build SQL and params dynamically
    $cols = ['lastname','firstname','birthdate','`c&y`','school','contact','email','date','flagged','address'];
    $placeholders = array_fill(0, count($cols), '?');
    $params = [
        $lastname,
        $firstname,
        $birthdate,
        $course_year,
        $school,
        $contact,
        $email,
        $date_now,
        $flagged,
        $address
    ];

    if ($includeFiles) {
        $cols[] = 'cor_file';
        $cols[] = 'school_id_file';
        $placeholders[] = '?';
        $placeholders[] = '?';
        $params[] = $corName;
        $params[] = $idName;
    }

    $cols_sql = implode(', ', array_map(function($c){ return $c; }, $cols));
    $place_sql = implode(', ', $placeholders);

    $sql = "INSERT INTO pending_registrations ({$cols_sql}) VALUES ({$place_sql})";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        // cleanup files on error
        if (file_exists($corPath)) unlink($corPath);
        if (file_exists($idPath)) unlink($idPath);
        die("SQL Prepare failed: " . $conn->error);
    }

    // Build types string: first 8 strings, then integer (flagged), then string(s)
    // Order of params corresponds to $params array
    $types = "ssssssssis"; // lastname..date => 8 s, flagged => i, address => s
    // if files included, add two 's'
    if ($includeFiles) $types .= "ss";

    // bind_param requires references
    $bind_names = [];
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }

    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    // Execute query
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Registration submitted successfully!'); window.location.href='index.php';</script>";
        exit();
    } else {
        // cleanup files on error
        if (file_exists($corPath)) unlink($corPath);
        if (file_exists($idPath)) unlink($idPath);
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        die("Error: " . $err);
    }
}

$conn->close();
?>