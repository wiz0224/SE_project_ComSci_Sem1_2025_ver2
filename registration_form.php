<!DOCTYPE html>
<html lang="en">
    
<head>
   <?php include 'conn.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration Form</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">    
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
  <div class="containerreg">
    <h2>Educational Assistance Registration Form</h2>
    <form action="upload.php" method="POST" enctype="multipart/form-data">
    <div>
      <label for="lastname">Last Name:</label>
      <input type="text" name="lastname" id="lastname" required>
    </div>
    <div>
      <label for="firstname">First Name:</label>
      <input type="text" name="fullname" id="fullname" required>
    </div>
    <div>
      <label for="course_year">Course & Year:</label>
      <input type="text" name="course_year" id="course_year" placeholder="e.g. BSIT 3rd Year" required>
    </div>
    <div>
        <label for="birthday">Birthday:</label>
        <input type="date" name="birthday" id="birthday" required>
    </div>
    <div>
      <label for="school">School:</label>
      <input type="text" name="school" id="school" required>
    </div>
    <div>
      <label for="contact">Contact Number:</label>
      <input type="text" name="contact" id="contact" pattern="[0-9]{11}" placeholder="e.g. 09123456789" required>
    </div>
    <div>
      <label for="email">Email Address:</label>
      <input type="email" name="email" id="email" required>
    </div>
    <div>
      <label for="address">Address:</label>
      <textarea name="address" id="address" rows="3" required></textarea>
    </div> 
    <div>
      <label for="cor">Upload COR from School (PDF or Image):</label>
      <input type="file" name="cor" id="cor" accept=".pdf, image/*" required>
    </div>
    <div>
      <label for="school_id">Upload School ID (Image or PDF):</label>
      <input type="file" name="school_id" id="school_id" accept=".pdf, image/*" required>
    </div>
    <br>
    <div style="display:flex;justify-content:flex-end;">  <button type="submit" name="submit" class="btn btn-primary" style="position:flex-end;">Register</button>
    </div></form>
  </div>
</body>
</html>
