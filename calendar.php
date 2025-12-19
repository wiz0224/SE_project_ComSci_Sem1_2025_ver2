<?php
include 'conn.php'; 
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

// --- Dynamic Date Logic ---
$current_year = 2025; 

// Base month/year on GET parameters or default to current date
$base_month = date('n'); // Current month number (1-12)

// If a month parameter is set, use it
if (isset($_GET['month']) && is_numeric($_GET['month']) && $_GET['month'] >= 1 && $_GET['month'] <= 12) {
    $base_month = (int)$_GET['month'];
}

// NOTE: Since the system is focused on 2025 (as per your title), we'll lock the year unless you provide a $current_year GET parameter later.
$current_year = 2025; 
$current_month = $base_month;

// --- Calendar Calculation in PHP ---
$num_days = cal_days_in_month(CAL_GREGORIAN, $current_month, $current_year);
$first_day_of_month_ts = mktime(0, 0, 0, $current_month, 1, $current_year);
$day_of_week_start = date('w', $first_day_of_month_ts); // 0 (Sun) to 6 (Sat)
$month_name = date('F', $first_day_of_month_ts);

// --- Data Fetching Logic ---
$scheduled_dates = [];
$sql = "SELECT DISTINCT date_scheduled FROM receivers WHERE date_scheduled IS NOT NULL AND status = 0";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $scheduled_dates[] = $row['date_scheduled'];
}

$selected_date = $_GET['date'] ?? null;
$recipients_for_date = [];
$date_error = false;
$total_recipients = 0;

if ($selected_date && strtotime($selected_date) !== false) {
    // Ensure the calendar view matches the selected date's month
    $date_obj_selected = new DateTime($selected_date);
    $current_month = (int)$date_obj_selected->format('n'); 
    
    $safe_date = $conn->real_escape_string($selected_date);
    
    if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $safe_date)) {
        $stmt = $conn->prepare("SELECT ID, firstname, lastname, email, contact, `candy` FROM receivers WHERE date_scheduled = ? AND status = 0 ORDER BY lastname ASC");
        $stmt->bind_param("s", $safe_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $total_recipients = $result->num_rows;
        while ($row = $result->fetch_assoc()) {
            $recipients_for_date[] = $row;
        }
    } else {
        $selected_date = null; 
        $date_error = true;
    }
}

// Helper for navigation
$prev_month = $current_month == 1 ? 12 : $current_month - 1;
$next_month = $current_month == 12 ? 1 : $current_month + 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BEMAS Payout Calendar <?php echo $current_year; ?></title>
  <link rel="stylesheet" href="css/styles.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <style>
    /* CSS Variables for Light/Dark Mode (Consistent) */
    :root {
        --background-primary: #ffffff;
        --background-secondary: #f8f9fa;
        --text-color-primary: #212529;
        --text-color-secondary: #6c757d;
        --border-color: #dee2e6;
        --primary-color: #007bff;
        --header-background: #007bff;
        --header-text-color: #ffffff;
        --table-header-bg: #e9ecef;
        --table-header-text: #212529;
        --hover-color: #e2e6ea;
        --scheduled-day-bg: #dc3545; /* Red for Scheduled */
        --scheduled-day-hover: #c82333;
        --current-month-day-bg: #ffffff;
        --other-month-day-bg: #f8f9fa;
        --back-btn-bg: #6c757d;
        --back-btn-hover: #5a6268;
    }
    .dark-mode {
        --background-primary: #121212;
        --background-secondary: #1e1e1e;
        --text-color-primary: #e0e0e0;
        --text-color-secondary: #a0a0a0;
        --border-color: #3a3a3a;
        --primary-color: #79b8ff;
        --header-background: #1e1e1e;
        --header-text-color: #e0e0e0;
        --table-header-bg: #2d2d2d;
        --table-header-text: #e0e0e0;
        --hover-color: #272727;
        --scheduled-day-bg: #e74c3c; 
        --scheduled-day-hover: #c0392b;
        --current-month-day-bg: #1e1e1e;
        --other-month-day-bg: #121212;
        --back-btn-bg: #495057;
        --back-btn-hover: #555c63;
    }

    body {
        background-color: var(--background-primary);
        color: var(--text-color-primary);
        transition: background-color 0.3s, color 0.3s;
        font-family: 'Roboto', sans-serif;
    }
    
    /* 🖥️ Layout (Revised for better visual structure) */
    .calendar-container { 
        max-width: 1400px; 
        margin: 20px auto; 
        padding: 20px; 
        background-color: var(--background-secondary);
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        border: 1px solid var(--border-color);
        position: relative; /* CRITICAL: Reference point for absolute positioning */
    }
    .dark-mode .calendar-container {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    }
    
    .calendar-header { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 20px; 
        /* Gagamitin natin ang text-align: center para i-gitna ang H1 */
        text-align: center; 
    }
    
    .calendar-title-group { 
        /* Binawasan ang flex properties para hayaang ang H1 na mag-gitna */
        flex-grow: 1;
        position: relative; /* Para sa absolute positioning ng back button */
    }
    
    /* Back Button Styling (FIXED to align left and not break center alignment) */
    .back-link {
        /* CRITICAL FIX: Absolute positioning relative to .calendar-title-group */
        position: absolute; 
        left: -1px; /* Counteract the 20px padding of .calendar-container */
        top: 50%;
        transform: translateY(-50%);
        white-space: nowrap; /* Tiyakin na hindi ito mag-break line */
        
        background-color: var(--back-btn-bg);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        font-weight: 500;
        transition: background-color 0.2s;
        z-index: 10;
    }
    .back-link:hover {
        background-color: var(--back-btn-hover);
        color: white;
        text-decoration: none;
    }
    .back-link i {
        margin-right: 5px;
        font-size: 1.2em;
    }

    .calendar-nav { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        /* Dahil flex-grow: 1 ang title-group, itutulak nito ang nav sa kanan */
    }
    
    /* Payout Title (H1) */
    .calendar-title-group h1 {
        font-size: 1.75rem; 
        margin: 0; 
        color: var(--text-color-primary);
        /* Ito na ang automatic na nakagitna dahil flex-grow: 1 ang parent niya at center ang text-align */
    }
    
    .calendar-nav h2 {
        font-size: 1.5rem;
        margin: 0;
        min-width: 200px; 
        text-align: center;
        color: var(--primary-color);
    }
    .calendar-nav button {
        background: var(--primary-color);
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.2s;
        font-weight: bold;
    }
    .calendar-nav button:hover {
        background: #0056b3;
    }
    .dark-mode .calendar-nav button:hover {
        background: #5097d7;
    }
    
    /* 📅 Calendar Grid Styling (The key aesthetic fix) */
    .calendar-grid-container {
        border: 1px solid var(--border-color);
        border-radius: 4px;
        overflow: hidden;
    }
    .calendar-grid { 
        width: 100%; 
        display: grid; 
        grid-template-columns: repeat(7, 1fr); 
        background-color: var(--current-month-day-bg);
    }
    .day-header {
        background-color: var(--table-header-bg);
        color: var(--table-header-text);
        font-weight: bold;
        text-align: center;
        padding: 10px 5px;
        border-right: 1px solid var(--border-color);
    }
    .day-header:last-child {
        border-right: none;
    }

    .day-cell { 
        min-height: 100px; /* Standard Calendar Height */
        border: 1px solid var(--border-color); 
        border-top: none; /* Only need top border for headers */
        padding: 8px; 
        text-align: right;
        background-color: var(--current-month-day-bg);
        box-sizing: border-box;
        position: relative;
        transition: background-color 0.2s;
        display: flex;
        flex-direction: column;
    }
    .day-cell.empty-cell { 
        background-color: var(--other-month-day-bg);
        border-color: var(--border-color);
    }
    .day-cell:hover:not(.empty-cell) { 
        background-color: var(--hover-color); 
    }
    
    .day-cell.scheduled-day {
      background-color: var(--scheduled-day-bg) !important; 
      color: white !important;
      border-color: var(--scheduled-day-bg) !important;
      cursor: pointer;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    .dark-mode .day-cell.scheduled-day {
        border-color: var(--scheduled-day-bg) !important;
    }
    .day-cell.scheduled-day:hover { 
        background-color: var(--scheduled-day-hover) !important; 
        transform: scale(1.01);
    } 

    .day-number { 
        font-size: 1.2em; 
        font-weight: bold;
        color: var(--text-color-primary);
    }
    .day-cell.scheduled-day .day-number { 
        color: white; 
    }
    .day-cell.selected-date {
        border: 3px solid var(--primary-color) !important;
    }

    /* Appointment Indicator */
    .appointment-count {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 4px;
        padding: 2px 5px;
        font-size: 0.8em;
        position: absolute;
        bottom: 5px;
        left: 5px;
        font-weight: normal;
    }
    .dark-mode .day-cell .day-number { 
        color: var(--text-color-primary); 
    }

    /* List Box Styling (Kept Clean and Consistent) */
    .main-layout-split { display: flex; gap: 30px; align-items: flex-start; }
    .list-col { 
        flex: 0 0 35%; 
        max-width: 35%; 
        min-width: 300px; 
    }
    .calendar-col { flex: 1; }
    .schedule-list-box {
        padding: 20px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        background: var(--current-month-day-bg);
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .schedule-list-box table {
        width: 100%; 
        border-collapse: collapse;
    }
    .schedule-list-box th, .schedule-list-box td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-color-primary);
    }
    .schedule-list-box th {
        background-color: var(--table-header-bg);
        color: var(--table-header-text);
    }
    .schedule-list-box table tr:last-child td {
        border-bottom: none;
    }

    /* 📱 Responsive adjustments */
    @media (max-width: 900px) {
        .main-layout-split { flex-direction: column; gap: 20px; }
        .list-col, .calendar-col { flex: 1 1 100%; max-width: 100%; min-width: unset; }
        .calendar-header { 
            flex-direction: column; 
            align-items: flex-start; 
            gap: 15px;
        }
        /* Ayusin ang title alignment sa mobile view */
        .calendar-title-group {
            width: 100%;
            text-align: center;
        }
        .back-link {
            /* Keep back button position fixed relative to the container */
            position: absolute; 
            left: -20px;
            top: 20px; 
            transform: none; /* remove translateY for top alignment */
        }
    }
  </style>
</head>
<body>
    <div class="calendar-container">
        <div class="calendar-header">
            
            <div class="calendar-title-group">
                <a href="dashboard.php" class="back-link">
                    <i class='bx bx-arrow-back'></i> Back
                </a>
                <h1 style="font-size: 1.75rem; margin: 0; color: var(--text-color-primary);">Payout Schedule Calendar — <?php echo $current_year; ?></h1>
            </div>

            <div class="calendar-nav">
                <button id="prevMonth" onclick="window.location.href='calendar.php?month=<?php echo $prev_month; ?>'">
                    <i class='bx bx-chevron-left'></i>
                </button>
                <h2 id="monthTitle"><?php echo $month_name . ' ' . $current_year; ?></h2> 
                <button id="nextMonth" onclick="window.location.href='calendar.php?month=<?php echo $next_month; ?>'">
                    <i class='bx bx-chevron-right'></i>
                </button>
            </div>
        </div>

        <?php if ($date_error): ?>
            <div class="alert alert-danger">Invalid date selected.</div>
        <?php endif; ?>

        <div class="main-layout-split">
            
            <?php if ($selected_date): ?>
            <div class="schedule-list-box list-col">
                <h3 style="font-size: 1.25rem;">Recipients Scheduled for Payout:</h3>
                <h4 style="font-size: 1.1rem; color: var(--primary-color);"><?php echo date('F d, Y', strtotime($selected_date)); ?> (<?php echo $total_recipients; ?>)</h4>
                <?php if (!empty($recipients_for_date)): ?>
                    <div class="table-responsive-wrapper" style="margin-top: 15px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>C&Y</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($recipients_for_date as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['lastname'] . ', ' . $r['firstname']); ?></td>
                                    <td><?php echo htmlspecialchars($r['candy']); ?></td>
                                    <td><a href="schedule.php?view=id&id=<?= $r['ID'] ?>" class="btn btn-primary btn-sm">Manage</a></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p style="margin-top: 15px; text-align: center;">
                        <a href="schedule.php?view=scheduled" class="back-link" style="position: static; transform: none; margin-left: 0;">Manage All Scheduled Payouts</a>
                    </p>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-top: 15px;">No recipients are currently scheduled for this date.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="calendar-col <?php echo $selected_date ? '' : 'default-calendar-view'; ?>">
                <div class="calendar-grid-container">
                    <div class="calendar-grid">
                        <div class="day-header">Sun</div>
                        <div class="day-header">Mon</div>
                        <div class="day-header">Tue</div>
                        <div class="day-header">Wed</div>
                        <div class="day-header">Thu</div>
                        <div class="day-header">Fri</div>
                        <div class="day-header">Sat</div>
                    </div>
                    
                    <div id="calendarDays" class="calendar-grid">
                        <?php
                        $day_counter = 1;
                        // Empty cells for the start of the month
                        for ($i = 0; $i < $day_of_week_start; $i++) {
                            echo '<div class="day-cell empty-cell"></div>';
                        }
                        
                        // Days of the month
                        while ($day_counter <= $num_days) {
                            $month_str = str_pad($current_month, 2, '0', STR_PAD_LEFT);
                            $day_str = str_pad($day_counter, 2, '0', STR_PAD_LEFT);
                            $full_date = "{$current_year}-{$month_str}-{$day_str}";
                            
                            $is_scheduled = in_array($full_date, $scheduled_dates);
                            $is_selected = $full_date === $selected_date;
                            
                            $classes = 'day-cell';
                            if ($is_scheduled) {
                                $classes .= ' scheduled-day';
                            }
                            if ($is_selected) {
                                $classes .= ' selected-date';
                            }
                            
                            $day_url = "calendar.php?date={$full_date}";
                            $onclick_attr = $is_scheduled ? "onclick=\"window.location.href='{$day_url}'\"" : "";
                            $title_attr = $is_scheduled ? "title='Click to view schedule'" : "title='No schedule'";
                            
                            echo "<div class='{$classes}' {$onclick_attr} {$title_attr}>";
                            echo "<div class='day-number'>{$day_counter}</div>";
                            
                            // Simple indicator
                            if ($is_scheduled) {
                                echo "<span class='appointment-count'>Scheduled</span>";
                            }
                            
                            echo "</div>";
                            $day_counter++;
                        }
                        
                        // Empty cells for the end of the month
                        $cells_used = $day_of_week_start + $num_days;
                        $remaining_cells = (ceil($cells_used / 7) * 7) - $cells_used; 
                        
                        for ($i = 0; $i < $remaining_cells; $i++) {
                            echo '<div class="day-cell empty-cell"></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>
        
    </div>

  <script>
    // Apply dark mode if stored
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
    // The calendar rendering is now handled by PHP.
  </script>

</body>

</html>