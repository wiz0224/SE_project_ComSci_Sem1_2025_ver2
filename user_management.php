<?php
session_start();
include 'conn.php'; // Tiyaking gumagamit ng tamang connection file

// Ensure the user is logged in
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

// *** FIXED SQL QUERY ***: Idinagdag ang 'last_login'
$sql = "SELECT id, firstname, lastname, email, last_login FROM users ORDER BY lastname ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin User Roster</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing CSS Variables for Light/Dark Mode (Consistent definitions) */
        :root {
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa; /* Background ng page/body */
            --text-color-primary: #212529;
            --border-color: #dee2e6;
            --primary-color: #007bff;
            --table-header-bg: #007bff;
            --table-header-text: #ffffff;
        }
        .dark-mode {
            --background-primary: #1e1e1e; /* Container/Inner BG */
            --background-secondary: #121212; /* Main Page BG */
            --text-color-primary: #e0e0e0;
            --border-color: #3a3a3a;
            --primary-color: #79b8ff;
            --table-header-bg: #2d2d2d;
            --table-header-text: #e0e0e0;
        }

        body {
            background-color: var(--background-secondary); 
            color: var(--text-color-primary); 
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }

        .main-container {
            max-width: 1000px;
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

        /* Table Styling */
        .user-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color-primary);
        }
        .user-table thead tr {
            background-color: var(--table-header-bg);
            color: var(--table-header-text);
        }
        .user-table th, .user-table td {
            padding: 12px;
            border: 1px solid var(--border-color);
            text-align: left;
        }
        .user-table tbody tr:nth-child(even) {
            background-color: var(--background-secondary);
        }
        
        /* Back Link Styling */
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--text-color-primary);
            font-weight: 500;
        }
        .back-link:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
        .back-link i {
            margin-right: 5px;
        }
    </style>
</head>
<body>

<div class="main-container">
    <a href="dashboard.php" class="back-link">
        <i class='bx bx-arrow-back'></i> Back to Dashboard
    </a>
    
    <h2 class="text-center" style="color: var(--primary-color);">Admin Roster and Login Status</h2>
    <hr>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="user-table"> 
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email (Username)</th>
                        <th>Last Login Time</th> </tr>
                </thead>
                <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <?php if ($row['last_login']): ?>
                                <?= date('M d, Y h:i A', strtotime($row['last_login'])) ?>
                            <?php else: ?>
                                Never logged in
                            <?php endif; ?>
                        </td>
                        </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div> 
    <?php else: ?>
        <div class="alert alert-info text-center">No admin users found in the system.</div>
    <?php endif; ?>
    
    </div>

<script>
    // Apply dark mode if stored
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>