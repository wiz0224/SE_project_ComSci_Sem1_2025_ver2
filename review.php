<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
include 'conn.php';

// CRITICAL FIX: Kunin ang ID mula sa POST (galing sa accept.php list) o GET (kung galing sa URL)
if (!isset($_POST['id']) && !isset($_GET['id'])) {
    header("Location: accept.php"); 
    exit();
}

// Gamitin ang ID na nakuha sa POST (primary) o GET (backup)
$id = intval($_POST['id'] ?? $_GET['id']);

// Tiyakin na may valid ID
if ($id <= 0) {
    header("Location: accept.php?error=invalid_id"); 
    exit();
}

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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* CSS Variables for Light/Dark Mode (Consistent) */
        :root {
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa; /* Background ng Container */
            --text-color-primary: #212529;
            --text-color-secondary: #6c757d;
            --border-color: #dee2e6;
            --primary-color: #007bff;
            --table-header-bg: #007bff;
            --table-header-text: #ffffff;
            --review-container-bg: #ffffff;
        }
        .dark-mode {
            --background-primary: #121212;
            --background-secondary: #1e1e1e;
            --text-color-primary: #e0e0e0;
            --text-color-secondary: #a0a0a0;
            --border-color: #333333;
            --primary-color: #79b8ff;
            --table-header-bg: #333333;
            --table-header-text: #ffffff;
            --review-container-bg: #1e1e1e;
        }

        body {
            background-color: var(--background-primary);
            color: var(--text-color-primary);
            transition: background-color 0.3s, color 0.3s;
        }

        /* Main container style */
        .review-container {
            max-width: 1200px;
            margin: 50px auto;
            padding: 30px;
            background-color: var(--review-container-bg);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        .dark-mode .review-container {
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }

        /* Info Table Styling */
        .table {
            color: var(--text-color-primary);
            margin-bottom: 30px;
        }
        .table th {
            width: 200px;
            background-color: var(--background-secondary);
            border-color: var(--border-color);
            color: var(--text-color-primary);
        }
        .table td {
            border-color: var(--border-color);
            background-color: var(--review-container-bg);
        }
        .dark-mode .table th {
            background-color: #252525;
            color: var(--text-color-primary);
        }

        /* Document display styling */
        .document-view-section {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 30px;
        }
        .document-view-section > div {
            flex: 1 1 45%;
            min-width: 300px;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--background-secondary);
        }
        .dark-mode .document-view-section > div {
            background-color: #252525;
        }
        .document-view-section h5 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            color: var(--primary-color);
        }
        img, iframe {
            max-width: 100%;
            height: auto;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        .dark-mode img, .dark-mode iframe {
            border-color: #555555;
        }
        
        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            text-decoration: none;
            color: var(--text-color-secondary);
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
    <div class="review-container">
        <a href="accept.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Back to Pending List
        </a>

        <h3 class="mb-4 text-center">Reviewing Registration for: <?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></h3>
        
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th scope="row">Name:</th>
                    <td><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></td>
                    <th scope="row">Contact #:</th>
                    <td><?= htmlspecialchars($row['contact']) ?></td>
                </tr>
                <tr>
                    <th scope="row">Course & Year:</th>
                    <td><?= htmlspecialchars($row['c&y']) ?></td>
                    <th scope="row">Email:</th>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                </tr>
                <tr>
                    <th scope="row">School:</th>
                    <td><?= htmlspecialchars($row['school']) ?></td>
                    <th scope="row">Address:</th>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                </tr>
                <tr>
                    <th scope="row">Date Submitted:</th>
                    <td colspan="3"><?= htmlspecialchars($row['date']) ?></td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h4 class="mb-3 text-center" style="color: var(--primary-color);">Uploaded Documents</h4>
        
        <div class="document-view-section">
            
            <div>
                <h5>Certificate of Registration (COR):</h5>
                <?php
                $cor_path = $row['cor_path'];
                if ($cor_path && file_exists($cor_path)) {
                    if (preg_match('/\\.pdf$/i', $cor_path)) {
                        echo "<iframe src='$cor_path' width='100%' height='400' title='COR Document'></iframe>";
                    } else {
                        echo "<img src='$cor_path' alt='COR Image'>";
                    }
                } else {
                    echo "<p class='text-danger'>No COR uploaded or file missing.</p>";
                }
                ?>
            </div>

            <div>
                <h5>School ID:</h5>
                <?php
                $school_id = $row['school_id'];
                if ($school_id && file_exists($school_id)) {
                    if (preg_match('/\\.pdf$/i', $school_id)) {
                        echo "<iframe src='$school_id' width='100%' height='400' title='School ID Document'></iframe>";
                    } else {
                        echo "<img src='$school_id' alt='School ID Image'>";
                    }
                } else {
                    echo "<p class='text-danger'>No School ID uploaded or file missing.</p>";
                }
                ?>
            </div>
        </div>

        <hr>

        <div class="action-buttons">
            <form action="accept_action.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-success btn-lg"><i class='bx bx-check-circle'></i> Accept</button>
            </form>

            <form action="reject.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-danger btn-lg"><i class='bx bx-x-circle'></i> Reject</button>
            </form>
        </div>
    </div>

    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>