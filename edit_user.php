<?php
session_start();
include 'conn.php'; 

// 1. Tiyakin na naka-login
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

$user_id = 0;
$user_data = null;
$message = '';
$message_type = ''; // success, danger

// --- Fetch User Data Logic ---
if (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
    
    // Kumuha ng kasalukuyang user data gamit ang ID
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $message = "Error: User ID not found.";
        $message_type = 'danger';
        $user_id = 0;
    } else {
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Handle Form Submission (Update Logic) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['password']);
    
    if ($user_id <= 0) {
        $message = "Invalid User ID for update.";
        $message_type = 'danger';
    } else {
        // Start building the update query
        $sql_update = "UPDATE users SET firstname = ?, lastname = ?, email = ?";
        $params = "sss";
        $values = [&$firstname, &$lastname, &$email];
        
        // Check if password needs to be updated
        if (!empty($new_password)) {
            // Gumagamit ng MD5, tulad ng nakita sa forgot_password.php niyo
            $hashed_pass = md5($new_password); 
            $sql_update .= ", password = ?";
            $params .= "s";
            $values[] = &$hashed_pass;
        }
        
        $sql_update .= " WHERE id = ?";
        $params .= "i";
        $values[] = &$user_id;

        $stmt = $conn->prepare($sql_update);
        
        // Bind parameters dynamically
        $stmt->bind_param($params, ...$values);
        
        if ($stmt->execute()) {
            // Success: I-redirect pabalik sa user management list
            header("Location: user_management.php?status=update_success");
            exit();
        } else {
            $message = "Update failed: " . $conn->error;
            $message_type = 'danger';
            // Re-fetch data in case the update failed to keep the form populated
            header("Location: edit_user.php?id=$user_id&status=update_fail");
            exit();
        }
        $stmt->close();
    }
}

// Kapag may error, kunin ulit ang user data para mapuno ang form
if (!$user_data && $user_id > 0) {
    // Re-fetch data if POST failed and user_data is null
    $stmt = $conn->prepare("SELECT id, firstname, lastname, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
    }
    $stmt->close();
}

// Kunin ang data na gagamitin sa form (gamitin ang POST data kung may failed submission)
$display_firstname = $_POST['firstname'] ?? $user_data['firstname'] ?? '';
$display_lastname = $_POST['lastname'] ?? $user_data['lastname'] ?? '';
$display_email = $_POST['email'] ?? $user_data['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User: <?= htmlspecialchars($user_data['firstname'] ?? 'N/A') ?></title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* Reusing consistent CSS styling */
        :root {
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa;
            --text-color-primary: #212529;
            --border-color: #dee2e6;
            --primary-color: #007bff;
            --form-bg: #ffffff;
        }
        .dark-mode {
            --background-primary: #121212;
            --background-secondary: #1e1e1e;
            --text-color-primary: #e0e0e0;
            --border-color: #3a3a3a;
            --primary-color: #79b8ff;
            --form-bg: #1e1e1e;
        }
        body {
            background-color: var(--background-secondary); 
            color: var(--text-color-primary); 
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        .edit-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: var(--form-bg);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }
        .dark-mode .edit-container {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.4);
        }
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

<div class="edit-container">
    <a href="user_management.php" class="back-link">
        <i class='bx bx-arrow-back'></i> Back to User List
    </a>

    <h3 class="text-center" style="color: var(--primary-color);">Edit User Details</h3>
    <hr>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'update_fail'): ?>
        <div class="alert alert-danger">Update failed. Please check the data and try again.</div>
    <?php endif; ?>

    <?php if ($user_id > 0 && $user_data): ?>
        <form method="POST" action="edit_user.php?id=<?= $user_id ?>">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
            
            <div class="mb-3">
                <label for="lastname" class="form-label">Last Name:</label>
                <input type="text" class="form-control" id="lastname" name="lastname" value="<?= htmlspecialchars($display_lastname) ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="firstname" class="form-label">First Name:</label>
                <input type="text" class="form-control" id="firstname" name="firstname" value="<?= htmlspecialchars($display_firstname) ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($display_email) ?>" required>
            </div>
            
            <hr>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password (Leave blank if unchanged):</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password to reset">
            </div>

            <button type="submit" class="btn btn-primary w-100 mt-3"><i class='bx bx-save'></i> Save Changes</button>
        </form>
    <?php elseif ($user_id > 0 && !$user_data): ?>
        <div class="alert alert-danger">The requested user does not exist.</div>
    <?php else: ?>
         <div class="alert alert-danger">Invalid request. User ID is missing.</div>
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