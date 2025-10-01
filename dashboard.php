<?php
session_start();
if (!isset($_SESSION['firstName'])) {
    header("Location: index.php");
    exit();
}
$fullName = $_SESSION['firstName'] . ' ' . $_SESSION['lastName'];

// Show popup only on first login, then unset the session variable
$showPopup = false;
if (isset($_SESSION['show_popup']) && $_SESSION['show_popup'] === true) {
    $showPopup = true;
    unset($_SESSION['show_popup']); // Remove so it only shows once
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">    
    <link rel="stylesheet" href="css/styles.css">
    <?php include 'conn.php'; ?>
    
</head>
<body class="sidebar-collapsed">

    <!-- Popup overlay -->
    <?php if ($showPopup): ?>
    <div id="welcomePopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" id="closePopupBtn">&times;</span>
            <h2>Welcome, <?php echo htmlspecialchars($fullName); ?>!</h2>
            <p>You have successfully logged in.</p>
            <button id="goDashboard">Continue</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="sidebar collapsed" id="sidebar" aria-expanded="false">
         <ul>
            <li>
                <a href="#" id="sidebarToggle" class="sidebar-toggle" role="button" aria-label="Toggle menu" aria-pressed="false">
                    <span class="icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/></svg></span>
                    <span class="label">Menu</span>
                </a>
            </li>
             <li><a href="#"><span class="icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 12h18"/><path d="M3 6h18"/><path d="M3 18h18"/></svg></span><span class="label">Dashboard</span></a></li>
             <li><a href="#"><span class="icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg></span><span class="label">Receivers</span></a></li>
             <li><a href="#"><span class="icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3h18v12H3z"/><path d="M7 21h10"/></svg></span><span class="label">Reports</span></a></li>
             <li><a href="#"><span class="icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.6 1.6 0 0 0 .2 1.9l.1.1-1.5 1.5-.1-.1a1.6 1.6 0 0 0-1.9-.2 6.7 6.7 0 0 1-2.3.9l-.3 1.8h-2l-.3-1.8a6.7 6.7 0 0 1-2.3-.9 1.6 1.6 0 0 0-1.9.2l-.1.1L4.3 17l.1-.1a1.6 1.6 0 0 0 .2-1.9 6.7 6.7 0 0 1-.9-2.3L2 12v-2l1.8-.3a6.7 6.7 0 0 1 .9-2.3A1.6 1.6 0 0 0 4 5.3L4.1 5 5.6 6.5l-.1.1a1.6 1.6 0 0 0 .2 1.9c.6.7 1.1 1.5 1.4 2.4"/></svg></span><span class="label">Settings</span></a></li>
         </ul>
     </div>
     
         <div class="grouped" >

             <div><h1 style="margin-bottom: 0; font-size: 2rem; display: inline; vertical-align: middle;">Educational Assistance list </h1><h3 style="color:#333;">Brgy.Lidong, Sto.Domingo, Albay</h3></div>
            <div class="profilehorizontal"><h3 style="margin-bottom: 0; display: inline; vertical-align: middle;">profile</h3><div></div> <button type="button" class="btn btn-primary">Return</button> </div> 

        </div>
    </div>
 <div class= 'nametable'>
    <form method="GET" class="controls-form" style="width:100%; display:flex;  align-items:center; margin-bottom:8px;background:#bbebff; padding:8px; border-radius:10px;justify-content: flex-end;" id="letterForm">
        <div style="display:flex; align-items:center; gap:6px; margin-right:12px;">
            <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search']); ?>">
            <?php endif; ?>
            <?php if (isset($_GET['order']) && $_GET['order'] !== ''): ?>
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order']); ?>">
            <?php endif; ?>
            <label for="letterSelect" style="margin-right:6px; font-size:14px;">Filter by last name:</label>
            <select name="letter" id="letterSelect" class="form-control" style="width:120px;" onchange="document.getElementById('letterForm').submit();">
                <option value="" <?php echo (!isset($_GET['letter']) || $_GET['letter']==='') ? 'selected':''; ?>>All</option>
                <?php foreach (range('A','Z') as $l): ?>
                    <option value="<?php echo $l; ?>" <?php echo (isset($_GET['letter']) && $_GET['letter']===$l) ? 'selected' : ''; ?>><?php echo $l; ?></option>
                <?php endforeach; ?>
            </select></div>
        
        <input type="text" name="search" class="form-control" style="width:260px;" placeholder="Search by name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <select name="order" class="form-control" style="width:180px;">
            <option value="">Sort by</option>
            <option value="lastname_asc" <?php echo (isset($_GET['order']) && $_GET['order']=='lastname_asc') ? 'selected':''; ?>>Last name A → Z</option>
            <option value="lastname_desc" <?php echo (isset($_GET['order']) && $_GET['order']=='lastname_desc') ? 'selected':''; ?>>Last name Z → A</option>
        </select>
        <button type="submit" class="btn btn-info">Apply</button>
        
    </form>
    <div class="letter-filter" style="margin-bottom:12px;">
        
     </div>
     
     <?php
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$letter = isset($_GET['letter']) ? $conn->real_escape_string($_GET['letter']) : '';
$order = isset($_GET['order']) ? $_GET['order'] : '';

$conditions = [];
if ($search !== '') {
    $conditions[] = "(ID LIKE '%$search%' OR firstname LIKE '%$search%' OR lastname LIKE '%$search%')";
}
if ($letter !== '') {
    $letter_esc = $conn->real_escape_string($letter);
    $conditions[] = "lastname LIKE '".$letter_esc."%'";
}

$sql = "SELECT * FROM receivers";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(' AND ', $conditions);
}

if ($order === 'lastname_asc') {
    $sql .= " ORDER BY lastname ASC";
} elseif ($order === 'lastname_desc') {
    $sql .= " ORDER BY lastname DESC";
} else {
    $sql .= " ORDER BY ID";
}
$result = $conn->query($sql);
     if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='15'>";
        echo "<tr class='firsttr'><th>ID</th><th>Last Name</th><th>First Name</th><th>Course & Year</th><th>School</th><th>Email</th><th>Address</th><th>Status</th><th>Date Delivered</th></tr>";
 
         while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["ID"] . "</td>";
            echo "<td>" . $row["lastname"] . "</td>";
            echo "<td>" . $row["firstname"] . "</td>";
            echo "<td>" . $row["c&y"] . "</td>";
            echo "<td>". $row["school"]."</td>";
            echo "<td>Email@email.com</td>";
            echo "<td>address</td>";
            if ($row["status"] == 1){
                echo "<td>Delivered</td>";}
            else{
                echo "<td>Needs Action </td>";
            }
            echo "<td class='horizontal_text'><div class='tdwbtn'> 01/23/25 <button type='button' class='btn btn-outline-info btn-sm' style='margin-left:20px;'>Update</button>  </div>  </td>";
            echo "</tr>";
         }
 
        echo "</table>";
     } else {
        echo "No results found.";
     }
 ?>
</div>

<!-- Footer -->
<footer>
    <div class="horizontal_footer">
        <div class = 'footer_div'><div>Privacy Policy</div><div>Contact Support</div></div>
        <div class = 'footer_div'><div>&copy; <?php echo date("Y"); ?> Your Organization. All rights reserved.</div><div></div>vr.1.0.1</div>
    </div>
</footer>
<script>
(function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    if (!toggle || !sidebar) return;

    if (sidebar.classList.contains('collapsed')) {
        document.body.classList.add('sidebar-collapsed');
        toggle.setAttribute('aria-pressed', 'true');
        sidebar.setAttribute('aria-expanded', 'false');
    } else {
        document.body.classList.remove('sidebar-collapsed');
        toggle.setAttribute('aria-pressed', 'false');
        sidebar.setAttribute('aria-expanded', 'true');
    }

    toggle.addEventListener('click', function (e) {
        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        var isCollapsed = sidebar.classList.toggle('collapsed');
        toggle.setAttribute('aria-pressed', isCollapsed ? 'true' : 'false');
        sidebar.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    });
})();

// Popup logic
window.onload = function() {
    var popup = document.getElementById("welcomePopup");
    if (popup) {
        popup.style.display = "block";
        var closeBtn = document.getElementById("closePopupBtn");
        var continueBtn = document.getElementById("goDashboard");
        closeBtn.onclick = function() {
            popup.style.display = "none";
        }
        continueBtn.onclick = function() {
            popup.style.display = "none";
        }
        window.onclick = function(event) {
            if (event.target == popup) {
                popup.style.display = "none";
            }
        }
    }
}
</script>
</body>
</html>