<?php
include 'conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if a column exists in a table
function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return ($res && $res->num_rows > 0);
}

if (isset($_POST['submit'])) {
    // Get POST data safely
    $lastname     = isset($_POST['lastname']) ? trim($_POST['lastname']) : '';
    $firstname    = isset($_POST['firstname']) ? trim($_POST['firstname']) : '';
    $birthdate    = isset($_POST['birthdate']) ? $_POST['birthdate'] : ''; // Ensure form field is "birthdate"
    $course_year  = isset($_POST['course_year']) ? trim($_POST['course_year']) : '';
    $school       = isset($_POST['school']) ? trim($_POST['school']) : '';
    $contact      = isset($_POST['contact']) ? trim($_POST['contact']) : '';
    $email        = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address      = isset($_POST['address']) ? trim($_POST['address']) : '';
    $date_now     = date("Y-m-d H:i:s");
    $flagged      = 0;

    // Upload folder
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);

    // Safe file name function
    function safeFileName($name) {
        return preg_replace("/[^A-Za-z0-9._-]/", "_", $name);
    }

    // Check files
    if (!isset($_FILES["cor"]) || !isset($_FILES["school_id"])) {
        die("Missing upload files.");
    }

    $corTmp  = $_FILES["cor"]["tmp_name"];
    $corName = uniqid("cor_") . "_" . safeFileName($_FILES["cor"]["name"]);
    $corPath = $targetDir . $corName;

    $idTmp   = $_FILES["school_id"]["tmp_name"];
    $idName  = uniqid("id_") . "_" . safeFileName($_FILES["school_id"]["name"]);
    $idPath  = $targetDir . $idName;

    // Move files
    if (!move_uploaded_file($corTmp, $corPath)) die("Failed to upload COR file.");
    if (!move_uploaded_file($idTmp, $idPath)) {
        if (file_exists($corPath)) unlink($corPath);
        die("Failed to upload School ID file.");
    }

    // Check if the columns exist in the DB
    $hasCorCol = columnExists($conn, 'pending_registrations', 'cor_file');
    $hasIdCol  = columnExists($conn, 'pending_registrations', 'school_id_file');

    // Columns to insert
    $cols = ['lastname','firstname','birthdate','`candy`','school','contact','email','date','flagged','address'];
    $params = [$lastname, $firstname, $birthdate, $course_year, $school, $contact, $email, $date_now, $flagged, $address];
    $placeholders = array_fill(0, count($cols), '?');

    if ($hasCorCol && $hasIdCol) {
        $cols[] = 'cor_file';
        $cols[] = 'school_id_file';
        $placeholders[] = '?';
        $placeholders[] = '?';
        $params[] = $corName;
        $params[] = $idName;
    }

    $cols_sql = implode(', ', $cols);
    $place_sql = implode(', ', $placeholders);

    // Prepare statement
    $sql = "INSERT INTO pending_registrations ($cols_sql) VALUES ($place_sql)";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        if (file_exists($corPath)) unlink($corPath);
        if (file_exists($idPath)) unlink($idPath);
        die("SQL Prepare failed: " . $conn->error);
    }

    // Build types string
    $types = "ssssssssis"; // lastname..date => 8 s, flagged => i, address => s
    if ($hasCorCol && $hasIdCol) $types .= "ss";

    $bind_names = [$types];
    foreach ($params as $i => $param) $bind_names[] = &$params[$i];

    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    // Execute
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        echo "<script>alert('Registration submitted successfully!'); window.location.href='index.php';</script>";
        exit();
    } else {
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
