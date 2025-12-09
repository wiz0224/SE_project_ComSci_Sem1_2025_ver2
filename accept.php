<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
// Siguraduhin na 'conn.php' ang gagamitin
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
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables for Light/Dark Mode (Consistent definitions) */
        :root {
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa; /* Background ng page/body */
            --text-color-primary: #212529;
            --text-color-secondary: #6c757d;
            --border-color: #dee2e6;
            --primary-color: #007bff;
            --table-header-bg: #e9ecef;
        }
        .dark-mode {
            --background-primary: #1e1e1e; /* Container/Inner BG */
            --background-secondary: #121212; /* Main Page BG */
            --text-color-primary: #e0e0e0;
            --text-color-secondary: #a0a0a0;
            --border-color: #3a3a3a;
            --primary-color: #79b8ff;
            --table-header-bg: #2d2d2d;
        }

        body {
            background-color: var(--background-secondary); 
            color: var(--text-color-primary); 
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: var(--background-primary); 
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }
        .dark-mode .main-container {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
        }

        /* TABLE STYLING FIX for Readability */
        .pending-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color-primary); /* Ensure table text is readable */
        }
        .pending-table thead tr {
            background-color: var(--table-header-bg);
        }
        .pending-table th, .pending-table td {
            padding: 10px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        /* ALERT STYLES FIX (Ensuring readability in Dark Mode) */
        .alert { padding: 10px 15px; margin-bottom: 15px; border: 1px solid transparent; border-radius: 4px; }
        
        /* Light Mode Alerts */
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* Dark Mode Alerts Fix */
        .dark-mode .alert-success { background-color: #1b5e20; color: #a5d6a7; border-color: #388e3c; } /* Dark Green BG, Light Green Text */
        .dark-mode .alert-info { background-color: #01579b; color: #81d4fa; border-color: #0288d1; } /* Dark Blue BG, Light Blue Text */
        .dark-mode .alert-danger { background-color: #b71c1c; color: #ffcdd2; border-color: #d32f2f; } /* Dark Red BG, Light Red Text */

        /* Custom Back Button for consistency */
        .custom-back-btn {
            background-color: var(--text-color-secondary);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.2s;
            display: inline-block; /* Gawing block ang link */
        }
        .custom-back-btn:hover {
            background-color: #5a6268;
        }
        .dark-mode .custom-back-btn {
            background-color: #495057;
        }
        .dark-mode .custom-back-btn:hover {
            background-color: #555c63;
        }
    </style>
</head>
<body>

<div class="main-container">
    <div style="margin-bottom: 20px; display: flex; align-items: center; position: relative;">
        <a href="dashboard.php" class="custom-back-btn" style="position: absolute; left: 0;">
            ← Back to Dashboard
        </a>
        <h2 style="flex-grow: 1; text-align: center; color: var(--text-color-primary);">Pending Registrations for Review (<?= date('Y') ?>)</h2>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'acceptance_cancelled'): ?>
        <div class="alert alert-info">Acceptance process was cancelled.</div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'rejection_cancelled'): ?>
        <div class="alert alert-info">Rejection process was cancelled.</div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class='nametable' style='width:100%; margin: 0 auto; margin-bottom: 20px; overflow-x: auto;'>
            <div class="table-responsive-wrapper">
                <table border="1" class="pending-table"> 
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Last Name</th>
                            <th>First Name</th>
                            <th>Course & Year</th>
                            <th>Date Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php 
                        $review_file = 'review.php'; // Review file
                        $is_eligible_for_renewal = true;
                        
                        // Check renewal eligibility (based on date)
                        if ($row['date'] && strtotime($row['date']) < strtotime($six_months_ago)) {
                            $is_eligible_for_renewal = false;
                            $reason = "Ineligible: Renewal period exceeded 6 months.";
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($row['lastname']) ?></td>
                            <td><?= htmlspecialchars($row['firstname']) ?></td>
                            <td><?= htmlspecialchars($row['c&y']) ?></td>
                            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                            <td>
                                <?php if (!$is_eligible_for_renewal): ?>
                                    <form method="POST" action="notification.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="reason" value="<?= htmlspecialchars($reason) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="<?= htmlspecialchars($reason) ?>">Ineligible</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="<?= $review_file ?>" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Review</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div> 
        </div> 
    <?php else: ?>
        <div class="alert alert-info">No pending registrations.</div>
    <?php endif; ?>
</div>

<script>
    // Tiyakin na ang body ay mag-a-apply ng 'dark-mode' class sa pag-load
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>
