<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
include 'conn.php';

// Fetch pending registrations
$sql = "SELECT * FROM pending_registrations ORDER BY date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accept Registrations</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .notify-btn { margin-right: 8px; }
    </style>
</head>
<body>
<div class="cont">
    <h2>Pending Registrations</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table class="containerreg">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Course & Year</th>
                    <th>School</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                // Check if name exists in receivers
                $lname = $conn->real_escape_string($row['lastname']);
                $fname = $conn->real_escape_string($row['firstname']);
                $check = $conn->query("SELECT id FROM receivers WHERE lastname='$lname' AND firstname='$fname' LIMIT 1");
                $has_duplicate = $check && $check->num_rows > 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['lastname']) ?></td>
                    <td><?= htmlspecialchars($row['firstname']) ?></td>
                    <td><?= htmlspecialchars($row['c&y']) ?></td>
                    <td><?= htmlspecialchars($row['school']) ?></td>
                    <td><?= htmlspecialchars($row['contact']) ?></td>
                    <td class="email-cell"><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td>
                        <?php if ($has_duplicate): ?>
                            <form method="POST" action="notification.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm notify-btn">Notify</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="accept_action.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn btn-success btn-sm">Accept</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">No pending registrations.</div>
    <?php endif; ?>
</div>
</body>
</html>