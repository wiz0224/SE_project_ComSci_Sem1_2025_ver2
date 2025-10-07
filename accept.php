<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
include 'conn.php';

// Define the 6-month cut-off date for renewal eligibility
$six_months_ago = date('Y-m-d', strtotime('-6 months'));

// Fetch pending registrations
$sql = "SELECT * FROM pending_registrations ORDER BY date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Registrations</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Highlight rows that are potentially ineligible (received within 6 months) */
        .ineligible-row {
            background-color: #ffcccc; /* Light red/pink */
        }
        /* Highlight rows that are exact duplicates */
        .duplicate-row {
            background-color: #ff9999; /* Darker red for severe ineligibility (Duplicate) */
        }
        .eligible-row {
            background-color: #ccffcc; /* Light green for acceptance */
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Pending Registrations</h2>
    <?php
    // Display status messages from redirect
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        if ($status == 'accepted') {
            echo '<div class="alert alert-success">Registration successfully Accepted and added to the recipients list!</div>';
        } elseif ($status == 'cancelled') {
            echo '<div class="alert alert-warning">Acceptance Cancelled by user.</div>';
        } elseif ($status == 'notified') {
            echo '<div class="alert alert-danger">Registrant was marked Ineligible, removed from pending, and an email notification was sent.</div>';
        } elseif ($status == 'notification_cancelled') {
            echo '<div class="alert alert-info">Ineligibility notification Cancelled by user.</div>';
        } elseif ($status == 'error') {
            echo '<div class="alert alert-danger">An error occurred during the process. Please check the logs.</div>';
        }
    }
    ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>C&Y</th>
                    <th>School</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Date Applied</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): 
                $is_ineligible = false;
                $is_duplicate = false;
                $reason = "";

                // --- 1. Check for Exact Duplicate (7 fields match) ---
                $stmt_duplicate = $conn->prepare("
                    SELECT id FROM receivers 
                    WHERE lastname = ? AND firstname = ? AND `c&y` = ? AND school = ? AND contact = ? AND email = ? AND address = ? 
                    LIMIT 1
                ");
                $stmt_duplicate->bind_param("sssssss", 
                    $row['lastname'], $row['firstname'], $row['c&y'], $row['school'], $row['contact'], $row['email'], $row['address']
                );
                $stmt_duplicate->execute();
                $res_duplicate = $stmt_duplicate->get_result();

                if ($res_duplicate && $res_duplicate->num_rows > 0) {
                    $is_duplicate = true;
                    $is_ineligible = true; // Duplicate is the highest level of ineligibility
                    $reason = "Exact Duplicate Found. Already exists in the recipients list.";
                }
                $stmt_duplicate->close();

                // --- 2. Check for 6-Month Rule (if NOT a duplicate) ---
                if (!$is_duplicate) {
                    $stmt_check_6mos = $conn->prepare("
                        SELECT date FROM receivers 
                        WHERE lastname = ? AND firstname = ? 
                        ORDER BY date DESC 
                        LIMIT 1
                    ");
                    $stmt_check_6mos->bind_param("ss", $row['lastname'], $row['firstname']);
                    $stmt_check_6mos->execute();
                    $res_check_6mos = $stmt_check_6mos->get_result();

                    if ($res_check_6mos && $res_check_6mos->num_rows > 0) {
                        $receiver_row = $res_check_6mos->fetch_assoc();
                        $last_date = strtotime($receiver_row['date']);
                        $six_months_ago_timestamp = strtotime($six_months_ago);

                        if ($last_date > $six_months_ago_timestamp) {
                            $is_ineligible = true;
                            $reason = "Received assistance on " . date('Y-m-d', $last_date) . " (less than 6 months ago).";
                        } else {
                            $reason = "Last received assistance on " . date('Y-m-d', $last_date) . " (Eligible for renewal).";
                        }
                    }
                    $stmt_check_6mos->close();
                }

                // Determine row class based on eligibility
                if ($is_duplicate) {
                    $row_class = 'duplicate-row';
                } elseif ($is_ineligible) {
                    $row_class = 'ineligible-row';
                } else {
                    $row_class = 'eligible-row';
                }
                ?>
                
                <tr class="<?= $row_class ?>">
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
                        <?php if ($is_ineligible): ?>
                            <form method="POST" action="notification.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="reason" value="<?= htmlspecialchars($reason) ?>">
                                <button type="submit" class="btn btn-danger btn-sm notify-btn" title="<?= $reason ?>">Notify Ineligible</button>
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