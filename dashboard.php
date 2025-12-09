<?php
session_start();
include 'conn.php'; 

if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

$fullName = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];
$adminpos = $_SESSION['email'];


$showPopup = false;
if (isset($_SESSION['show_popup']) && $_SESSION['show_popup'] === true) {
    $showPopup = true;
    unset($_SESSION['show_popup']);
}   

// --- START: NEW STATISTICS LOGIC (Konektado sa receivers table) ---
$stats = [
    'total_active' => 0,
    'total_scheduled' => 0,
    'total_pending' => 0,
    'total_delivered' => 0
];

// 1. Total Active Recipients (Status = 0)
$sql_total_active = "SELECT COUNT(ID) as total_active FROM receivers WHERE status = 0";
$res_total_active = $conn->query($sql_total_active);
$stats['total_active'] = $res_total_active->fetch_assoc()['total_active'] ?? 0;

// 2. Scheduled for Payout (Status = 0 AND date_scheduled IS NOT NULL)
$sql_scheduled = "SELECT COUNT(ID) as total_scheduled FROM receivers WHERE status = 0 AND date_scheduled IS NOT NULL";
$res_scheduled = $conn->query($sql_scheduled);
$stats['total_scheduled'] = $res_scheduled->fetch_assoc()['total_scheduled'] ?? 0;

// 3. Pending Schedule (Status = 0 AND date_scheduled IS NULL)
$sql_pending = "SELECT COUNT(ID) as total_pending FROM receivers WHERE status = 0 AND date_scheduled IS NULL";
$res_pending = $conn->query($sql_pending);
$stats['total_pending'] = $res_pending->fetch_assoc()['total_pending'] ?? 0;

// 4. Total Paid Out (Status = 1)
$sql_delivered = "SELECT COUNT(ID) as total_delivered FROM receivers WHERE status = 1";
$res_delivered = $conn->query($sql_delivered);
$stats['total_delivered'] = $res_delivered->fetch_assoc()['total_delivered'] ?? 0;
// --- END: NEW STATISTICS LOGIC ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BEMAS Admin Dashboard</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">    
    <link rel="stylesheet" href="css/styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
<style>
    /* CSS Variables for Light/Dark Mode (Perfected) */
    :root {
        --background-primary: #ffffff;
        --background-secondary: #f8f9fa;
        --text-color-primary: #212529;
        --text-color-secondary: #6c757d;
        --border-color: #dee2e6;
        --primary-color: #007bff;
        --nav-bg: #343a40;
        --nav-text: #ffffff;
    }
    .dark-mode {
        --background-primary: #1e1e1e; /* Card/Inner BG */
        --background-secondary: #121212; /* Main Page BG */
        --text-color-primary: #e0e0e0;
        --text-color-secondary: #a0a0a0;
        --border-color: #3a3a3a;
        --primary-color: #79b8ff;
        --nav-bg: #1e1e1e;
        --nav-text: #e0e0e0;
    }

    /* General Body Styling */
    body {
        background-color: var(--background-secondary);
        color: var(--text-color-primary);
        transition: background-color 0.3s, color 0.3s;
        font-family: 'Roboto', sans-serif;
    }
    
    /* Sidebar/Navigation Styling */
    .sidebar {
        background-color: var(--nav-bg);
        color: var(--nav-text);
        width: 250px;
        height: 100vh;
        position: fixed;
        padding: 20px;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        z-index: 1000;
        transition: background-color 0.3s;
    }
    .sidebar a {
        color: var(--nav-text);
        padding: 10px 0;
        display: block;
        text-decoration: none;
        transition: background-color 0.2s, color 0.2s;
        border-radius: 4px;
        padding-left: 10px;
    }
    .sidebar a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    /* Main Content Area */
    .main-content {
        margin-left: 250px;
        padding: 20px;
        transition: margin-left 0.3s;
    }
    .main-content h1, .main-content h2 {
        color: var(--text-color-primary);
    }

    /* --- NEW: Statistics Grid and Box Styling --- */
    .stats-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-box {
        flex: 1 1 200px; /* Allows flexible sizing */
        padding: 20px;
        border-radius: 8px;
        background-color: var(--background-primary);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--border-color);
        text-align: center;
        transition: background-color 0.3s, transform 0.2s;
    }
    .dark-mode .stat-box {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
    }
    .stat-box:hover {
        transform: translateY(-3px);
    }
    .stat-box h3 {
        font-size: 1.1em;
        color: var(--text-color-secondary);
        margin-bottom: 5px;
    }
    .stat-box p {
        font-size: 2.5em;
        font-weight: bold;
        color: var(--primary-color);
        margin: 0;
    }
    /* --- End Statistics Grid --- */

</style>
</head>
<body>

<?php if ($showPopup): ?>
<div id="welcome-popup" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;">
    <div style="background: var(--background-primary); padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
        <h2 style="color: var(--primary-color);">Welcome, <?php echo $_SESSION['firstName']; ?>!</h2>
        <p style="color: var(--text-color-primary);">You are now logged in as an Administrator.</p>
        <button onclick="document.getElementById('welcome-popup').style.display='none';" class="btn btn-primary">Continue to Dashboard</button>
    </div>
</div>
<?php endif; ?>

<div class="sidebar">
    <h3 style="color: white; margin-bottom: 30px;">BEMAS Admin</h3>
    <p style="font-size: 0.9em; color: var(--text-color-secondary); margin-bottom: 15px;">Welcome, <?php echo $fullName; ?></p>
    
    <a href="dashboard.php"><i class='bx bxs-dashboard' ></i> Dashboard</a>
    <a href="accept.php"><i class='bx bxs-user-plus' ></i> Pending Registrations</a>
    <a href="schedule.php"><i class='bx bxs-calendar-check' ></i> Manage Schedule & Payout</a>
    <a href="calendar.php"><i class='bx bxs-calendar' ></i> Payout Calendar</a>
    <a href="user_management.php"><i class='bx bxs-group' ></i> User Management</a> <div style="position: absolute; bottom: 20px; width: 85%;">
        <hr style="border-top: 1px solid var(--border-color);">
        <a href="#" onclick="toggleDarkMode()" style="display: flex; align-items: center;">
            <i id="darkModeIcon" class='bx bxs-moon'></i> <span style="margin-left: 10px;">Toggle Dark Mode</span>
        </a>
        <a href="logout.php" onclick="confirmLogout(event)" style="color: #dc3545;">
            <i class='bx bxs-log-out'></i> Logout
        </a>
    </div>
</div>

<div class="main-content">
    
    <h1 style="color: var(--text-color-primary);">Admin Dashboard</h1>
    
    <h2 style="color: var(--text-color-primary); margin-top: 30px; margin-bottom: 20px;">System Overview</h2>

    <div class="stats-grid">
        <div class="stat-box">
            <i class='bx bxs-user-detail' style='font-size: 24px; color: var(--primary-color);'></i>
            <h3>Total Active Recipients</h3>
            <p><?= $stats['total_active'] ?></p>
        </div>
        <div class="stat-box">
            <i class='bx bxs-calendar-check' style='font-size: 24px; color: #28a745;'></i>
            <h3>Scheduled for Payout</h3>
            <p><?= $stats['total_scheduled'] ?></p>
        </div>
        <div class="stat-box">
            <i class='bx bxs-time' style='font-size: 24px; color: #ffc107;'></i>
            <h3>Pending Schedule</h3>
            <p><?= $stats['total_pending'] ?></p>
        </div>
        <div class="stat-box">
            <i class='bx bxs-truck' style='font-size: 24px; color: #17a2b8;'></i>
            <h3>Total Paid Out (Delivered)</h3>
            <p><?= $stats['total_delivered'] ?></p>
        </div>
    </div>
    <h2 style="color: var(--text-color-primary); margin-top: 30px; margin-bottom: 20px;">Quick Actions</h2>
    <div class="stats-grid" style="gap: 10px;">
        <a href="accept.php" class="btn btn-primary" style="flex: 1 1 150px;">Review Pending (<?= $stats['total_pending'] ?>)</a>
        <a href="schedule.php?view=scheduled" class="btn btn-success" style="flex: 1 1 150px;">View Scheduled</a>
        <a href="calendar.php" class="btn btn-info" style="flex: 1 1 150px;">Check Calendar</a>
    </div>

</div>

<script>
// --- DARK MODE LOGIC ---
function updateDarkModeIcon() {
    const icon = document.getElementById('darkModeIcon');
    if (icon) {
        // Tinitingnan kung may 'dark-mode' class ang body
        if (document.body.classList.contains('dark-mode')) {
             // Dark Mode: Palitan ng Sun icon
             icon.classList.remove('bxs-moon');
             icon.classList.add('bxs-sun'); 
        } else {
             // Light Mode: Palitan ng Moon icon
             icon.classList.remove('bxs-sun');
             icon.classList.add('bxs-moon'); 
        }
    }
}

function toggleDarkMode() {
    // 1. I-toggle ang 'dark-mode' class sa <body>
    document.body.classList.toggle('dark-mode');
    
    // 2. I-save ang estado sa browser storage
    const isDarkModeEnabled = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDarkModeEnabled);
    
    // 3. I-update ang icon
    updateDarkModeIcon();
}

// --- LOGOUT CONFIRMATION FUNCTION ---
function confirmLogout(event) {
    // Para hindi mag-reload ang page nang walang confirmation
    event.preventDefault(); 
    
    // Ipakita ang confirmation box
    const userConfirmed = confirm("Are you sure you want to log out?");

    if (userConfirmed) {
        // Kung nag-confirm (OK), i-redirect sa logout.php
        window.location.href = 'logout.php';
    }
}

// --- INITIAL LOAD ---
document.addEventListener('DOMContentLoaded', function() {
    // 1. I-apply ang Dark Mode state mula sa local storage
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
    // 2. I-set ang tama icon sa pag-load
    updateDarkModeIcon();
});
</script>
</body>
</html>