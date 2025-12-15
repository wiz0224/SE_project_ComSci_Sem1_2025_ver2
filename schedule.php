<?php
include 'connect.php'; // Using connect.php consistently

if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}

$message = '';
$message_type = ''; // success, danger, info

// Retrieve flash message from session if set
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// --- Helpers and column detection ---
function columnExists($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return ($res && $res->num_rows > 0);
}

function getField($arr, $keys, $default = '') {
    if (!is_array($arr)) return $default;

    // Direct match first (exact keys)
    foreach ($keys as $k) {
        if (array_key_exists($k, $arr) && $arr[$k] !== null && $arr[$k] !== '') return $arr[$k];
    }

    // Case-insensitive match
    $lower = array_change_key_case($arr, CASE_LOWER);
    foreach ($keys as $k) {
        $lk = strtolower($k);
        if (array_key_exists($lk, $lower) && $lower[$lk] !== null && $lower[$lk] !== '') return $lower[$lk];
    }

    // Fallbacks: common combined name fields
    foreach (['name','full_name','fullname'] as $k) {
        if (array_key_exists($k, $arr) && $arr[$k]) return $arr[$k];
        $lk = strtolower($k);
        if (array_key_exists($lk, $lower) && $lower[$lk]) return $lower[$lk];
    }

    return $default;
}

// detect name column to order by
$preferred_name_cols = ['lastname','last_name','lname','surname'];
$preferred_first_cols = ['firstname','first_name','fname','firstName'];
$order_name_col = null;
foreach ($preferred_name_cols as $c) {
    if (columnExists($conn, 'receivers', $c)) { $order_name_col = $c; break; }
}
if (!$order_name_col) {
    foreach ($preferred_first_cols as $c) {
        if (columnExists($conn, 'receivers', $c)) { $order_name_col = $c; break; }
    }
}
if (!$order_name_col) { $order_name_col = 'ID'; } // fallback

// detect schedule and delivered date columns
$schedule_candidates = ['date_scheduled','schedule_date','scheduled_date'];
$schedule_col = null;
foreach ($schedule_candidates as $c) { if (columnExists($conn, 'receivers', $c)) { $schedule_col = $c; break; } }

$delivered_candidates = ['date_delivered','date_paid','delivered_date','date_delivered_on','date_delivered_at','date'];
$delivered_col = null;
foreach ($delivered_candidates as $c) { if (columnExists($conn, 'receivers', $c)) { $delivered_col = $c; break; } }

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $action = $_POST['action'] ?? '';

    if ($receiver_id > 0) {
        if ($action === 'schedule') {
            $schedule_date = $_POST['schedule_date'] ?? null;
            
            if (!$schedule_col) {
                $message = "Scheduling is not enabled for this installation (no schedule column).";
                $message_type = 'danger';
            } elseif ($schedule_date && strtotime($schedule_date) !== false) {
                // Build WHERE clause optionally including status if the column exists
                $where_clause = "WHERE ID = ?";
                if (columnExists($conn, 'receivers', 'status')) {
                    $where_clause .= " AND status = 0";
                }
                $sql_update = "UPDATE receivers SET `{$schedule_col}` = ? " . $where_clause;
                $update_receiver = $conn->prepare($sql_update);
                if (!$update_receiver) {
                    $message = "Prepare failed for scheduling: " . $conn->error;
                    $message_type = 'danger';
                } else {
                    $update_receiver->bind_param("si", $schedule_date, $receiver_id);
                    if ($update_receiver->execute()) {
                        if ($update_receiver->affected_rows > 0) {
                            // Ensure event exists in schedule_events (UPSERT logic)
                            $upsert_event = $conn->prepare("INSERT INTO schedule_events (schedule_date) VALUES (?) ON DUPLICATE KEY UPDATE schedule_date=schedule_date");
                            if ($upsert_event) {
                                $upsert_event->bind_param("s", $schedule_date);
                                $upsert_event->execute();
                            }

                            $message = "Recipient successfully scheduled for payout on " . date('F d, Y', strtotime($schedule_date)) . ".";
                            $message_type = 'success';
                        } else {
                            $message = "No changes made: recipient may already be scheduled or not in pending status.";
                            $message_type = 'info';
                        }
                    } else {
                        $message = "Execute failed for scheduling: " . $update_receiver->error;
                        $message_type = 'danger';
                    }
                }
            } else {
                $message = "Invalid schedule date.";
                $message_type = 'danger';
            }
        } elseif ($action === 'deliver') {
            $deliver_date = date('Y-m-d');

            // If there's no delivered date column, try to add one so we can store the date
            if (!$delivered_col) {
                $alter_sql = "ALTER TABLE receivers ADD COLUMN date_delivered DATE DEFAULT NULL";
                try {
                    $conn->query($alter_sql);
                    // If successful, set the local variable so we can use it immediately
                    $delivered_col = 'date_delivered';
                } catch (Exception $e) {
                    // ignore failure; we'll still set status without a date
                    $delivered_col = null;
                }
            }

            if ($delivered_col && $schedule_col) {
                $update_delivery = $conn->prepare("UPDATE receivers SET status = 1, `{$delivered_col}` = ?, `{$schedule_col}` = NULL WHERE ID = ?");
                $update_delivery->bind_param("si", $deliver_date, $receiver_id);
            } elseif ($delivered_col) {
                $update_delivery = $conn->prepare("UPDATE receivers SET status = 1, `{$delivered_col}` = ? WHERE ID = ?");
                $update_delivery->bind_param("si", $deliver_date, $receiver_id);
            } else {
                // no delivered date column: just set status
                $update_delivery = $conn->prepare("UPDATE receivers SET status = 1 WHERE ID = ?");
                $update_delivery->bind_param("i", $receiver_id);
            }
            
            if ($update_delivery->execute()) {
                if ($delivered_col) {
                    $message = "Assistance marked as DELIVERED on " . date('F d, Y', strtotime($deliver_date)) . ".";
                } else {
                    $message = "Assistance marked as DELIVERED (date not tracked).";
                }
                $message_type = 'success';
            } else {
                $message = "Error marking as delivered: " . $conn->error;
                $message_type = 'danger';
            }
        } elseif ($action === 'reschedule_remove') {
            if (!$schedule_col) {
                $message = "Unschedule operation not available (no schedule column).";
                $message_type = 'danger';
            } else {
                $where_clause = "WHERE ID = ?";
                if (columnExists($conn, 'receivers', 'status')) {
                    $where_clause .= " AND status = 0";
                }
                $update_reschedule = $conn->prepare("UPDATE receivers SET `{$schedule_col}` = NULL " . $where_clause);
                $update_reschedule->bind_param("i", $receiver_id);

                if ($update_reschedule->execute()) {
                    if ($update_reschedule->affected_rows > 0) {
                        $message = "Recipient unscheduled. Now on the **Pending Schedule** list for rescheduling.";
                        $message_type = 'info';
                    } else {
                        $message = "No changes made while trying to unschedule. Recipient may already be unscheduled or not in pending status.";
                        $message_type = 'info';
                    }
                } else {
                    $message = "Error rescheduling: " . $update_reschedule->error;
                    $message_type = 'danger';
                }
            }
        }
        
        // To prevent double submission on refresh, redirect to the 'id' view after form processing
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
        header("Location: schedule.php?view=id&id=" . $receiver_id);
        exit();
    }
}

// --- Fetch Data for Display ---
$view_mode = $_GET['view'] ?? 'pending'; // 'pending', 'scheduled', 'delivered', 'id'
$receiver_data = null; 

// Retrieve messages from GET if redirected after a successful POST
if (isset($_GET['msg']) && isset($_GET['type'])) {
    $message = urldecode($_GET['msg']);
    $message_type = urldecode($_GET['type']);
}


if ($view_mode === 'id' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM receivers WHERE ID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $receiver_data = $result->fetch_assoc();
    if (!$receiver_data) {
        header("Location: dashboard.php?status=error&msg=record_not_found");
        exit();
    }
}

// SQL for List Views
$list_sql = '';
$list_title = 'Payout Management';

// Build list queries using detected columns
if ($view_mode === 'pending') {
    if ($schedule_col) {
        $list_sql = "SELECT * FROM receivers WHERE `{$schedule_col}` IS NULL AND status = 0 ORDER BY `{$order_name_col}` ASC";
        $list_title = "List of Recipients Pending Schedule (Reschedule)";
    } else {
        $list_sql = "SELECT * FROM receivers WHERE status = 0 ORDER BY `{$order_name_col}` ASC";
        $list_title = "List of Recipients Pending Schedule (scheduling not tracked)";
    }
} elseif ($view_mode === 'scheduled') {
    if ($schedule_col) {
        $list_sql = "SELECT * FROM receivers WHERE `{$schedule_col}` IS NOT NULL AND status = 0 ORDER BY `{$schedule_col}` ASC, `{$order_name_col}` ASC";
        $list_title = "List of Recipients Scheduled for Payout";
    } else {
        $list_sql = "SELECT * FROM receivers WHERE status = 0 ORDER BY `{$order_name_col}` ASC";
        $list_title = "List of Recipients Scheduled for Payout (scheduling not available)";
    }
} elseif ($view_mode === 'delivered') {
    if ($delivered_col) {
        $list_sql = "SELECT * FROM receivers WHERE status = 1 ORDER BY `{$delivered_col}` DESC, `{$order_name_col}` ASC";
    } else {
        $list_sql = "SELECT * FROM receivers WHERE status = 1 ORDER BY `{$order_name_col}` ASC";
    }
}
$list_result = null;
if ($list_sql) {
    $res = $conn->query($list_sql);
    if ($res === false) {
        $message = "Failed to run list query: " . $conn->error;
        $message_type = 'danger';
        $list_result = null;
    } else {
        $list_result = $res;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedule & Delivery</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        /* CSS Variables for Light/Dark Mode (Consistency) */
        :root {
            --background-primary: #ffffff; /* Card/Inner BG */
            --background-secondary: #f8f9fa; /* Main Page BG */
            --text-color-primary: #212529;
            --text-color-secondary: #6c757d;
            --border-color: #dee2e6;
            --primary-color: #007bff;
            --table-header-bg: #e9ecef;
            --pending-status-color-light: #dc3545; /* Red for status */
        }
        .dark-mode {
            --background-primary: #1e1e1e; /* Card/Inner BG */
            --background-secondary: #121212; /* Main Page BG */
            --text-color-primary: #e0e0e0;
            --text-color-secondary: #a0a0a0;
            --border-color: #3a3a3a;
            --primary-color: #79b8ff;
            --table-header-bg: #2d2d2d;
            --pending-status-color-dark: #e74c3c; /* Brighter Red for dark mode status */
        }

        /* General Layout */
        body {
            background-color: var(--background-secondary); /* Ensure body uses the correct BG */
            color: var(--text-color-primary); /* Ensure body uses the correct text color */
        }
        
        .schedule-container { 
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px; 
            background: var(--background-secondary); 
            border-radius: 8px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            border: 1px solid var(--border-color); 
        }
        .dark-mode .schedule-container {
            box-shadow: 0 4px 6px rgba(0,0,0,0.4);
        }
        
        .schedule-form-card { 
            border: 1px solid var(--border-color); 
            padding: 15px; 
            margin-bottom: 20px; 
            border-radius: 6px; 
            background: var(--background-primary); 
            color: var(--text-color-primary); /* Ensure text is readable */
        }
        
        .list-controls a { 
            margin-right: 15px; 
            font-weight: bold; 
            padding-bottom: 5px; 
            text-decoration: none; 
            color: var(--text-color-primary); 
        }
        .list-controls a.active { 
            color: var(--primary-color); 
            border-bottom: 2px solid var(--primary-color); 
        }

        /* ALERT STYLES FIX (The main issue in dark mode) */
        .alert { padding: 10px 15px; margin-bottom: 15px; border: 1px solid transparent; border-radius: 4px; }
        
        /* LIGHT MODE ALERTS (Original colors, kept for light mode) */
        .alert-success { background-color: #d4edda; color: #155724; border-color: #c3e6cb; }
        .alert-info { background-color: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; }

        /* DARK MODE ALERTS FIX */
        .dark-mode .alert-success { background-color: #1b5e20; color: #a5d6a7; border-color: #388e3c; } /* Dark Green BG, Light Green Text */
        .dark-mode .alert-info { background-color: #01579b; color: #81d4fa; border-color: #0288d1; } /* Dark Blue BG, Light Blue Text */
        .dark-mode .alert-danger { background-color: #b71c1c; color: #ffcdd2; border-color: #d32f2f; } /* Dark Red BG, Light Red Text */


        /* TABLE STYLING FOR DARK MODE */
        .tableaccept { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: var(--background-primary); 
            color: var(--text-color-primary);
        }
        .tableaccept thead tr { 
            background-color: var(--table-header-bg); 
            color: var(--text-color-primary);
        }
        .tableaccept th, .tableaccept td {
            border-color: var(--border-color) !important;
        }

        /* PENDING STATUS COLOR FIX */
        .status-pending { 
            color: var(--pending-status-color-light); 
            font-weight: bold; 
        }
        .dark-mode .status-pending { 
            color: var(--pending-status-color-dark); 
        }

        /* Input field fix for Dark Mode (Date input readability) */
        input[type="date"] {
            background-color: var(--background-primary);
            color: var(--text-color-primary);
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <div class="schedule-container">
        <div style="margin-bottom: 20px; display: flex; align-items: center; position: relative;">
            <button onclick="history.back()" class="custom-back-btn" style="position: absolute; left: 0;">
                ← Back
            </button>
            <h2 style="flex-grow: 1; text-align: center; color: var(--text-color-primary);">Payout and Delivery Management</h2>
        </div>
        
        <?php if ($message): ?>
            <div class='alert alert-<?php echo $message_type; ?>'><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($receiver_data): // Individual Update View (from Dashboard) ?>
            <?php
                $display_first = htmlspecialchars(getField($receiver_data, ['firstname','first_name','fname','firstName']));
                $display_last = htmlspecialchars(getField($receiver_data, ['lastname','last_name','lname','surname','lastName','LastName']));
                $display_name = trim($display_first . ' ' . $display_last);
                $display_id = htmlspecialchars($receiver_data['ID'] ?? $receiver_data['id'] ?? '');
                $status_val = $receiver_data['status'] ?? null;
                $schedule_val = $schedule_col ? ($receiver_data[$schedule_col] ?? null) : null;
                $delivered_val = $delivered_col ? ($receiver_data[$delivered_col] ?? null) : null;
            ?>
            <div class="schedule-form-card">
                <h3>Update Recipient: <?php echo $display_name; ?> (ID: <?php echo $display_id; ?>)</h3>
                <p>Status: <strong>
                    <?php
                    if ($status_val == 1) {
                        if ($delivered_val) {
                            echo "DELIVERED on " . date('F d, Y', strtotime($delivered_val));
                        } else {
                            echo "DELIVERED"; // delivered but no date column available
                        }
                    } elseif ($schedule_val) {
                        echo "SCHEDULED for " . date('F d, Y', strtotime($schedule_val));
                    } else {
                        echo "PENDING SCHEDULE";
                    }
                    ?>
                </strong></p>

                <?php if ($status_val == 0): // If not yet delivered ?>
                    <hr>
                    
                    <form method="POST" style="display:inline-block; margin-right: 15px;">
                        <input type="hidden" name="receiver_id" value="<?php echo $receiver_data['ID']; ?>">
                        <input type="hidden" name="action" value="deliver">
                        <button type="submit" class="btn btn-success" onclick="return confirm('Confirm marking assistance for this recipient as DELIVERED? This action cannot be undone.');">Mark as Delivered Now</button>
                    </form>

                    <form method="POST" style="display:inline-block; margin-right: 15px;">
                        <input type="hidden" name="receiver_id" value="<?php echo $receiver_data['ID']; ?>">
                        <input type="hidden" name="action" value="schedule">
                        <label for="schedule_date">Schedule Payout:</label>
                        <input type="date" name="schedule_date" required value="<?php echo htmlspecialchars($schedule_val ?? ''); ?>" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                        <button type="submit" class="btn btn-info">Set Schedule Date</button>
                    </form>

                    <?php if ($schedule_val): ?>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="receiver_id" value="<?php echo $display_id; ?>">
                            <input type="hidden" name="action" value="reschedule_remove">
                            <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to UNSCHEDULE this recipient? They will appear in the Pending list for manual rescheduling.');">Unschedule (Reschedule)</button>
                        </form>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="alert alert-success">This assistance was delivered on <?php echo htmlspecialchars($delivered_val ? date('F d, Y', strtotime($delivered_val)) : 'N/A'); ?>.</div>
                <?php endif; ?>
            </div>
            
        <?php endif; ?>

        <div class="list-controls" style="margin-bottom: 20px; margin-top: 20px;">
            <a href="schedule.php?view=pending" class="<?php echo ($view_mode === 'pending') ? 'active' : ''; ?>">Recipients for Reschedule (Pending Schedule)</a> |
            <a href="schedule.php?view=scheduled" class="<?php echo ($view_mode === 'scheduled') ? 'active' : ''; ?>">Recipients Scheduled for Payout</a> |
            <a href="schedule.php?view=delivered" class="<?php echo ($view_mode === 'delivered') ? 'active' : ''; ?>">Recipients Paid Out (Delivered)</a>
        </div>
        
        <h3 style="margin-top: 20px; border-bottom: 2px solid var(--border-color); padding-bottom: 5px; color: var(--text-color-primary);"><?php echo $list_title ?? ''; ?></h3>

        <?php if ($list_result && $list_result->num_rows > 0): ?>
            <div class='nametable' style='width:100%; margin: 0 auto; margin-bottom: 20px; overflow-x: auto;'>
                <div class="table-responsive-wrapper">
                    <table border="1" class="tableaccept">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Course & Year</th>
                                <th>Email</th>
                                <th>
                                    <?php 
                                    if ($view_mode === 'pending') { echo "Current Status"; } 
                                    elseif ($view_mode === 'scheduled') { echo "Schedule Date"; }
                                    elseif ($view_mode === 'delivered') { echo "Date Delivered"; }
                                    ?>
                                </th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $list_result->fetch_assoc()): ?>
                            <?php
                                $row_id_raw = $row['ID'] ?? $row['id'] ?? '';
                                $row_id = htmlspecialchars($row_id_raw);
                                $row_last = htmlspecialchars(getField($row, ['lastname','last_name','lname','surname','lastName','LastName']));
                                $row_first = htmlspecialchars(getField($row, ['firstname','first_name','fname','firstName','FirstName']));
                                $row_cy = htmlspecialchars(getField($row, ['c&y','course_year','c_y','courseyear','cy','course']));
                                $row_email = htmlspecialchars($row['email'] ?? '');
                                $row_schedule = $schedule_col ? ($row[$schedule_col] ?? null) : null;
                                $row_delivered = $delivered_col ? ($row[$delivered_col] ?? null) : null;
                            ?>
                            <tr>
                                <td><?= $row_id ?></td>
                                <td><?= $row_last ?></td>
                                <td><?= $row_first ?></td>
                                <td><?= $row_cy ?></td>
                                <td class="email-cell"><?= $row_email ?></td>
                                <td>
                                    <?php if ($view_mode === 'pending'): ?>
                                        <span class="status-pending">Pending Schedule</span>
                                    <?php elseif ($view_mode === 'scheduled'): ?>
                                        <?= $row_schedule ? date('M d, Y', strtotime($row_schedule)) : '-' ?>
                                    <?php elseif ($view_mode === 'delivered'): ?>
                                        <?= $row_delivered ? date('M d, Y', strtotime($row_delivered)) : '<span class="status-pending">Delivered</span>' ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="schedule.php?view=id&id=<?= urlencode($row_id_raw) ?>" class="btn btn-primary btn-sm">Manage</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div> 
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <?php 
                if ($view_mode === 'pending') { echo "No recipient needs to be scheduled at the moment (Pending Schedule)."; }
                elseif ($view_mode === 'scheduled') { echo "No recipient is scheduled for payout."; }
                elseif ($view_mode === 'delivered') { echo "No assistance has been recorded as delivered yet."; }
                ?>
            </div>
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