<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
include 'conn.php';

// Check if ID is sent
if (!isset($_POST['id'])) {
    header("Location: accept.php"); // make sure this matches your pending page
    exit();
}

$id = intval($_POST['id']);

// Fetch registration details
$stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='alert alert-danger'>No registration found for this ID.</div>";
    exit();
}

$row = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Registration</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }
        .table th {
            width: 200px;
        }
        iframe, img {
            border: 1px solid #ccc;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container" style="margin-top: 3%; margin-bottom: 5%;">
    <h2>Review Registration</h2>
    <a href="accept.php" class="btn btn-secondary btn-sm mb-3">← Back to Pending List</a>

    <!-- User Information -->
    <table class="table table-bordered">
        <tr><th>ID</th><td><?= htmlspecialchars($row['id']) ?></td></tr>
        <tr><th>Last Name</th><td><?= htmlspecialchars($row['lastname']) ?></td></tr>
        <tr><th>First Name</th><td><?= htmlspecialchars($row['firstname']) ?></td></tr>
        <tr><th>Course and Year</th><td><?= htmlspecialchars($row['c&y']) ?></td></tr>
        <tr><th>School</th><td><?= htmlspecialchars($row['school']) ?></td></tr>
        <tr><th>Contact</th><td><?= htmlspecialchars($row['contact']) ?></td></tr>
        <tr><th>Email</th><td><?= htmlspecialchars($row['email']) ?></td></tr>
        <tr><th>Address</th><td><?= htmlspecialchars($row['address']) ?></td></tr>
        <tr><th>Date Applied</th><td><?= htmlspecialchars($row['date']) ?></td></tr>
    </table>

    <hr>

    <!-- Uploaded Files Section -->
    <h4>Uploaded Documents</h4>
    <div style="display:flex; gap:40px; flex-wrap:wrap;">
        <!-- COR Display -->
        <div>
            <h5>COR (Certificate of Registration):</h5>
            <?php
            $cor = $row['cor'];
            if ($cor && file_exists($cor)) {
                if (preg_match('/\.pdf$/i', $cor)) {
                    echo "<iframe src='$cor' width='400' height='400'></iframe>";
                } else {
                    echo "<img src='$cor' alt='COR Image' width='300'>";
                }
            } else {
                echo "<p>No COR uploaded or file missing.</p>";
            }
            ?>
        </div>

        <!-- School ID Display -->
        <div>
            <h5>School ID:</h5>
            <?php
            $school_id = $row['school_id'];
            if ($school_id && file_exists($school_id)) {
                if (preg_match('/\.pdf$/i', $school_id)) {
                    echo "<iframe src='$school_id' width='400' height='400'></iframe>";
                } else {
                    echo "<img src='$school_id' alt='School ID Image' width='300'>";
                }
            } else {
                echo "<p>No School ID uploaded or file missing.</p>";
            }
            ?>
        </div>
    </div>

    <hr>

    <!-- Action Buttons -->
    <form action="approve.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button type="submit" class="btn btn-success">Accept</button>
    </form>

    <form action="reject.php" method="POST" style="display:inline;">
        <input type="hidden" name="id" value="<?= $row['id'] ?>">
        <button type="submit" class="btn btn-danger">Reject</button>
    </form>
</div>

<script>
    // Apply dark mode if saved
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>