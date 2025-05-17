<?php
// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['us_id'])) {
    header('Location: login.php');
    exit;
}

// เชื่อมต่อฐานข้อมูล
include 'req/db_connection.php';

// ดึงข้อมูลผู้ใช้ทั้งหมดพร้อมชื่อและรูปภาพของหน่วยงาน
$query = "SELECT users.*, departments.dep_name, departments.dep_img 
          FROM users 
          LEFT JOIN departments ON users.us_dep = departments.dep_id";
$result = $conn->query($query);

// ตรวจสอบว่าผลลัพธ์การ query ถูกต้องหรือไม่
if (!$result) {
    die('Error executing query: ' . $conn->error);
}

// ลบผู้ใช้
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM users WHERE us_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php"); // รีเฟรชหน้าเมื่อทำการลบ
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้ในระบบ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f8f9fa;
    }

    .form-container {
        background-color: white;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
</style>

<body>
    <div class="container mt-5">
        <div class="form-container">
            <h2>จัดการผู้ใช้ในระบบ</h2>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>รหัสผู้ใช้</th>
                        <th>Username</th>
                        <th>หน่วยงาน</th>
                        <th>หน่วยงาน</th> <!-- คอลัมน์ใหม่สำหรับ dep_img -->
                        <th>ตำแน่ง</th>
                        <th>สถานะ</th>
                        <th>รูปโปรไฟล์</th>
                        <th>ตัวจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['us_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['us_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['dep_name'] ?? 'No Department'); ?></td>
                                <td>
                                    <?php if (!empty($row['dep_img'])): ?>
                                        <img src="dep_img/<?php echo htmlspecialchars($row['dep_img']); ?>" alt="Department Image"
                                            class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                    <?php else: ?>
                                        <span>ไม่มีรูปภาพ</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['us_role']); ?></td>
                                <td>
                                    <?php if ($row['us_stat'] == 'active'): ?>
                                        <span class="badge bg-success text-white">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger text-white">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['imgprofile'])): ?>
                                        <img src="imgprofile/<?php echo htmlspecialchars($row['imgprofile']); ?>"
                                            alt="Profile Image" class="img-thumbnail" style="max-width: 50px; max-height: 50px;">
                                    <?php else: ?>
                                        <span>ไม่มีรูปภาพ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $row['us_id']; ?>"
                                        class="btn btn-warning btn-sm">แก้ไข</a>
                                    <a href="manage_users.php?delete_id=<?php echo $row['us_id']; ?>"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('คุณต้องการที่จะลบผู้ใช้นี้ใช่ไหม?')">ลบผู้ใช้</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">ไม่มีผู้ใช้</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="add_user.php" class="btn btn-primary">เพิ่มผู้ใช้</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
// ปิดการเชื่อมต่อ
$conn->close();
?>