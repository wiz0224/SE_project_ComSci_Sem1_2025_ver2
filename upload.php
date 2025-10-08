<?php

include 'conn.php';

if (isset($_POST['submit'])) {
    // Collect form data
    $lastname = $_POST['lastname'];
    $firstname = $_POST['fullname'];
    $birthdate = $_POST['birthday'];
    $course_year = $_POST['course_year']; // corresponds to `c&y`
    $school = $_POST['school'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $date_now = date("Y-m-d H:i:s");
    $flagged = 0; // default value

    // File uploads
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // COR file
    $corName = basename($_FILES["cor"]["name"]);
    $corPath = $targetDir . $corName;
    move_uploaded_file($_FILES["cor"]["tmp_name"], $corPath);

    // School ID file
    $idName = basename($_FILES["school_id"]["name"]);
    $idPath = $targetDir . $idName;
    move_uploaded_file($_FILES["school_id"]["tmp_name"], $idPath);

    // Insert data into database
    $sql = "INSERT INTO pending_registrations (lastname, firstname, birthdate, `c&y`, school, contact, email, date, flagged, address)
            VALUES ('$lastname', '$firstname', '$birthdate', '$course_year', '$school', '$contact', '$email', '$date_now', '$flagged', '$address')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Registration submitted successfully!'); window.location.href='.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>