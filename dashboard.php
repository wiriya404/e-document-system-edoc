<?php
if (!isset($_SESSION['us_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3>Welcome, <?php echo htmlspecialchars($_SESSION['us_name']); ?></h3>
    <p>Department: <?php echo htmlspecialchars($_SESSION['us_dep']); ?></p>
    <p>Role: <?php echo htmlspecialchars($_SESSION['us_role']); ?></p>
    <p>Status: <?php echo htmlspecialchars($_SESSION['us_stat']); ?></p>

    <!-- แสดงภาพโปรไฟล์ -->
    <img src="imgprofile/<?php echo isset($_SESSION['imgprofile']) ? htmlspecialchars($_SESSION['imgprofile']) : 'default-profile.png'; ?>" alt="Profile" class="img-thumbnail" style="max-width: 150px;">
    
    <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
</div>
</body>
</html>
