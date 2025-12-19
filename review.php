<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
include 'conn.php';

// Get ID from POST (preferred) or GET
$id = 0;
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
} elseif (isset($_GET['id'])) {
    $id = intval($_GET['id']);
}
if ($id <= 0) {
    header("Location: accept.php?error=invalid_id");
    exit();
}

// Fetch registration
$stmt = $conn->prepare("SELECT * FROM pending_registrations WHERE id = ?");
if (!$stmt) {
    die("DB prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: accept.php?error=not_found");
    exit();
}
$row = $result->fetch_assoc();
$stmt->close();

/**
 * Resolve upload filename to server path and public URL.
 * Accepts many DB variants: 'cor_file', 'cor_path', 'cor', 'uploads/...' etc.
 */
function resolveUpload($filename) {
    if (empty($filename)) return [ 'server' => null, 'url' => null ];

    $uploadsDir = __DIR__ . '/uploads/';
    $candidates = [];

    // If DB stored full path or starts with 'uploads/'
    if (strpos($filename, 'uploads/') !== false) {
        $candidates[] = __DIR__ . '/' . ltrim($filename, '/');
    }

    // basename inside uploads folder
    $candidates[] = $uploadsDir . basename($filename);

    // direct file in project root
    $candidates[] = __DIR__ . '/' . basename($filename);

    // check candidates for existence
    foreach ($candidates as $path) {
        if ($path && file_exists($path)) {
            // build URL relative to web root (assume 'uploads/' is web-accessible)
            $url = 'uploads/' . rawurlencode(basename($path));
            return [ 'server' => $path, 'url' => $url ];
        }
    }

    // fallback: still produce a reasonable URL for display if file might be present later
    return [ 'server' => null, 'url' => (strpos($filename, 'http') === 0 ? $filename : 'uploads/' . rawurlencode(basename($filename))) ];
}

// Normalize DB column names (try multiple possible names)
$cor_filename = $row['cor_file'] ?? $row['cor_path'] ?? $row['cor'] ?? $row['certificate'] ?? null;
$school_id_filename = $row['school_id_file'] ?? $row['school_id'] ?? $row['id_file'] ?? $row['schoolid'] ?? null;

$cor_res = resolveUpload($cor_filename);
$id_res  = resolveUpload($school_id_filename);

// Prepare display-safe values
$display_name = htmlspecialchars(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
$display_last_first = htmlspecialchars(($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? ''));
$display_course_year = htmlspecialchars($row['candy'] ?? $row['course_year'] ?? '');
$display_school = htmlspecialchars($row['school'] ?? '');
$display_contact = htmlspecialchars($row['contact'] ?? '');
$display_email = htmlspecialchars($row['email'] ?? '');
$display_address = htmlspecialchars($row['address'] ?? '');
$display_date = htmlspecialchars($row['date'] ?? $row['date_registered'] ?? '');

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
        :root {
            --primary: #007bff;
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa;
            --text-color-primary: #222;
            --text-color-secondary: #6c757d;
            --border-color: #e9ecef;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .dark-mode {
            --background-primary: #1e1e1e;
            --background-secondary: #121212;
            --text-color-primary: #e0e0e0;
            --text-color-secondary: #a0a0a0;
            --border-color: #3a3a3a;
            --primary: #79b8ff;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }

        body {
            font-family: Arial,Helvetica,sans-serif;
            background: var(--background-secondary);
            color: var(--text-color-primary);
            padding: 20px;
            transition: background-color .3s, color .3s;
        }

        .review-container{
            max-width:1100px;
            margin:30px auto;
            background:var(--background-primary);
            padding:24px;
            border-radius:8px;
            box-shadow:var(--card-shadow);
            border:1px solid var(--border-color);
        }

        .back-link{display:inline-flex;align-items:center;margin-bottom:18px;color:var(--text-color-secondary);text-decoration:none}
        .document-view-section{display:flex;gap:20px;flex-wrap:wrap;justify-content:center;margin-top:20px}
        .document-view-section > div{flex:1 1 45%;min-width:280px;padding:15px;border:1px solid var(--border-color);border-radius:8px;background:var(--background-primary)}
        img,iframe{max-width:100%;height:auto;border:1px solid var(--border-color);border-radius:4px;background:transparent}
        /* Tables & text
         * Override Bootstrap table defaults so they don't show bright white
         * when dark-mode is active. Keep transparent backgrounds and use
         * our color variables for text and borders.
         */
        .table { background: transparent; }
        .table th, .table td { background: transparent; color: var(--text-color-primary); border-color: var(--border-color) }
        .table thead th { color: var(--text-color-secondary) }
        .table tbody tr { background: transparent }

        /* Adjust inline status text colors for dark mode readability */
        .text-warning { color: #856404 }
        .text-danger { color: #8a1f1f }

        /* File link styling */
        .file-link a { color: var(--primary); }

        /* Buttons - reduce harsh borders in dark mode */
        .btn { border-radius: 6px }
        .btn:focus { box-shadow: none }
        .action-buttons{text-align:center;margin-top:18px;display:flex;gap:12px;justify-content:center}
        .btn-lg{padding:10px 18px;font-size:16px}
        .file-link {display:inline-block;margin-top:8px;color:var(--primary);}
        h3 { color: var(--primary); }
        h4 { color: var(--primary); }
    </style>
</head>
<body>
    <div class="review-container">
        <a href="accept.php" class="back-link"><i class='bx bx-arrow-back'></i> Back to Pending List</a>

        <h3 style="text-align:center;margin-bottom:10px">Reviewing Registration for: <?= $display_name ?></h3>

        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width:200px">Name:</th>
                    <td><?= $display_last_first ?></td>
                    <th style="width:200px">Contact #:</th>
                    <td><?= $display_contact ?></td>
                </tr>
                <tr>
                    <th>Course & Year:</th>
                    <td><?= $display_course_year ?></td>
                    <th>Email:</th>
                    <td><?= $display_email ?></td>
                </tr>
                <tr>
                    <th>School:</th>
                    <td><?= $display_school ?></td>
                    <th>Address:</th>
                    <td><?= $display_address ?></td>
                </tr>
                <tr>
                    <th>Date Submitted:</th>
                    <td colspan="3"><?= $display_date ?></td>
                </tr>
            </tbody>
        </table>

        <h4 style="text-align:center;color:var(--primary);margin-top:10px">Uploaded Documents</h4>
        <div class="document-view-section">
            <div>
                <h5>Certificate of Registration (COR):</h5>
                <?php if ($cor_res['server'] && file_exists($cor_res['server'])): ?>
                    <?php if (preg_match('/\.pdf$/i', $cor_res['server'])): ?>
                        <iframe src="<?= htmlspecialchars($cor_res['url']) ?>" width="100%" height="420" title="COR Document"></iframe>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($cor_res['url']) ?>" alt="COR Image">
                    <?php endif; ?>
                    <div class="file-link"><a href="<?= htmlspecialchars($cor_res['url']) ?>" download>Download COR</a></div>
                <?php elseif ($cor_res['url']): ?>
                    <!-- file not found on server, but show link if URL available -->
                    <p class="text-warning">File not found on server. You can try to open the link below:</p>
                    <div class="file-link"><a href="<?= htmlspecialchars($cor_res['url']) ?>" target="_blank" rel="noopener">Open COR</a></div>
                <?php else: ?>
                    <p class="text-danger">No COR uploaded.</p>
                <?php endif; ?>
            </div>

            <div>
                <h5>School ID:</h5>
                <?php if ($id_res['server'] && file_exists($id_res['server'])): ?>
                    <?php if (preg_match('/\.pdf$/i', $id_res['server'])): ?>
                        <iframe src="<?= htmlspecialchars($id_res['url']) ?>" width="100%" height="420" title="School ID Document"></iframe>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($id_res['url']) ?>" alt="School ID Image">
                    <?php endif; ?>
                    <div class="file-link"><a href="<?= htmlspecialchars($id_res['url']) ?>" download>Download School ID</a></div>
                <?php elseif ($id_res['url']): ?>
                    <p class="text-warning">File not found on server. You can try to open the link below:</p>
                    <div class="file-link"><a href="<?= htmlspecialchars($id_res['url']) ?>" target="_blank" rel="noopener">Open School ID</a></div>
                <?php else: ?>
                    <p class="text-danger">No School ID uploaded.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="action-buttons">
            <form id="acceptForm" action="accept_action.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                <button type="submit" class="btn btn-success btn-lg"><i class='bx bx-check-circle'></i> Accept</button>
            </form>

            <form id="rejectForm" action="reject.php" method="POST" style="display:inline;">
                <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                <button type="submit" class="btn btn-danger btn-lg"><i class='bx bx-x-circle'></i> Reject</button>
            </form>
        </div>
    </div>

<script>
    // apply dark mode if set
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }

    // Confirm before accepting. If canceled, do NOT submit (keeps entry in pending list).
    (function(){
        var acceptForm = document.getElementById('acceptForm');
        var fullName = <?= json_encode($display_name) ?>;
        acceptForm.addEventListener('submit', function(e){
            var ok = confirm('Are you sure you want to ACCEPT and add ' + fullName + ' to the list of assistance recipients?');
            if (!ok) {
                e.preventDefault();
                return;
            }
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'confirmed';
            input.value = 'true';
            acceptForm.appendChild(input);
        });

        var rejectForm = document.getElementById('rejectForm');
        rejectForm.addEventListener('submit', function(e){
            var ok = confirm('Are you sure you want to REJECT ' + fullName + '? This will remove the pending registration.');
            if (!ok) e.preventDefault();
        });
    })();
</script>
</body>
</html>