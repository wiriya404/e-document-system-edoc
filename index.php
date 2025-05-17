<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['us_id'])) {
    header('Location: login.php');
    exit;
}

$title = 'User Dashboard'; // ชื่อหน้า (สามารถปรับตามต้องการ)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Dashboard'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="#">E-Document Management</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($_SESSION['us_role'] == 'admin') : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="send_doc.php">📤 ส่งเอกสาร</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="show_doc.php">📁 แสดงเอกสารทั้งหมด</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doc.php">📝 จัดการเอกสารทั้งหมด</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">👥 จัดการผู้ใช้</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_departments.php">🏢 จัดการหน่วยงาน</a>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link" href="send_doc.php">📤 ส่งเอกสาร</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="show_doc.php">📥 จัดการเอกสาร ขาเข้า</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_doc.php">📤 จัดการเอกสาร ขาออก</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold" href="logout.php">🚪 ออกจากระบบ</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
    .nav-link {
        transition: 0.3s;
    }
    .nav-link:hover {
        color: #ffcc00 !important; /* เปลี่ยนเป็นสีเหลืองทอง */
    }
</style>


    <div class="container mt-5">
        <!-- Page Specific Content -->
        <?php
        // Include the specific page content
        if (isset($page_content)) {
            include $page_content;
        }
        ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
