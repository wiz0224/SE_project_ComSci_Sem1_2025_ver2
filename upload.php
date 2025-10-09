<?php
include 'conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (isset($_POST['submit'])) {
    // Collect form data safely
    $lastname = $_POST['lastname'];
    $firstname = $_POST['fullname'];
    $birthdate = $_POST['birthday'];
    $course_year = $_POST['course_year']; // corresponds to `c&y`
    $school = $_POST['school'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $date_now = date("Y-m-d H:i:s"); // includes both date and time
    $flagged = 0; // default value

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
    $corTmp = $_FILES["cor"]["tmp_name"];
    $corName = uniqid("cor_") . "_" . safeFileName($_FILES["cor"]["name"]);
    $corPath = $targetDir . $corName;

    // ---- School ID file ----
    $idTmp = $_FILES["school_id"]["tmp_name"];
    $idName = uniqid("id_") . "_" . safeFileName($_FILES["school_id"]["name"]);
    $idPath = $targetDir . $idName;

    // Move uploaded files
    if (!move_uploaded_file($corTmp, $corPath)) {
        die("Failed to upload COR file.");
    }
    if (!move_uploaded_file($idTmp, $idPath)) {
        die("Failed to upload School ID file.");
    }

    // Use prepared statements for security
    $sql = "INSERT INTO pending_registrations 
        (lastname, firstname, birthdate, `c&y`, school, contact, email, date, flagged, address, Path, idPath)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare failed: " . $conn->error);
    }

    // Bind parameters (12 total)
    $stmt->bind_param("sssssssissss", 
        $lastname, 
        $firstname, 
        $birthdate, 
        $course_year, 
        $school, 
        $contact, 
        $email, 
        $date_now, 
        $flagged, 
        $address, 
        $corPath, 
        $idPath
    );

    // Execute query
    if ($stmt->execute()) {
        echo "<script>alert('Registration submitted successfully!'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }
}

$conn->close();
?>
