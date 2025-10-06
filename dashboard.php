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

    <div class="sidebar collapsed" id="sidebar">
    <!-- Toggle behaves like a menu item -->
    <button class="menu-item" onclick="toggleSidebar()">
      <span class="icon">☰</span>
      <h4 class="menu-text" style="margin-bottom: 0; font-size: 0.9rem; display: inline; vertical-align: middle;">Menu</h4>
    </button>


    <a href="accept.php" class="menu-item" style="text-decoration:none;">
        <span class="icon">✓</span><h4 class="menu-text" style="margin-bottom: 0; font-size: 0.9rem; display: inline; vertical-align: middle;">Accept</h4>
    </a>
    <a href="calendar.php" class="menu-item" style="text-decoration:none;   "><span class="icon">++</span><h4 class="menu-text" style="margin-bottom: 0; font-size: 0.9rem; display: inline; vertical-align: middle;">calendar</h4></a>
        
    <div class="menu-item"><span class="icon">|||</span><h4 class="menu-text" style="margin-bottom: 0; font-size: 0.9rem; display: inline; vertical-align: middle;">Settings</h4></div>
    
  </div>

     
         <div class="grouped" >
             <div><h1 style="margin-bottom: 0; font-size: 2rem; display: inline; vertical-align: middle;">Educational Assistance list </h1><h3 style="color:#333;">Brgy.Lidong Sto.Domingo, Albay</h3></div>

            <div class="profilehorizontal"><div><img src="images/vecteezy_profile-icon-design-vector_5544718.jpg" alt="" style="width:60px;padding:2px;margin-right:20px;"><h3 style="margin-bottom: 0; display: inline; vertical-align: middle;">
    <?php echo htmlspecialchars($fullName); ?>
</h3></div> <button type="button" class="btn btn-primary">:</button> </div> 

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

        echo "<tr class='firsttr'><th>ID</th><th>Last Name</th><th>First Name</th><th>Course & Year</th><th>School</th><th>Email</th><th>Address</th><th>Status</th><th>Date Delivered</th><th>Action</th></tr>";

        
         while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["ID"] . "</td>";
            echo "<td>" . $row["lastname"] . "</td>";
            echo "<td>" . $row["firstname"] . "</td>";
            echo "<td>" . $row["c&y"] . "</td>";
            echo "<td>". $row["school"]."</td>";
            echo "<td class='email-cell'>". $row["email"]."</td>";
            echo "<td>". $row["address"]."</td>";
            if ($row["status"] == 1){
                echo "<td style='background:#80ff9f;'>Delivered</td>";}
            else{
                echo "<td style='background:#fffba8;' >Pending</td>";
            }
            
            if ($row["status"] == 1){
                echo "<td>".$row["date"]."</td>";}
            else{
                echo "<td>Not Applicable</td>";
            }
            echo "<td><button type='button' class='btn btn-outline-info btn-sm'>Update</button></td>";
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
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("collapsed");
}
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

<style>
/* Add to styles.css */
.email-cell {
    text-transform: none !important;
}
</style>