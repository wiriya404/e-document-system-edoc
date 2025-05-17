<?php
// เชื่อมต่อฐานข้อมูล
include 'req/db_connection.php';

// ตรวจสอบว่ามี `id` ถูกส่งมาหรือไม่
if (!isset($_GET['id'])) {
    die("Invalid request!");
}

$dep_id = $_GET['id'];

// ดึงข้อมูลแผนกที่ต้องการแก้ไข
$query = "SELECT * FROM departments WHERE dep_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $dep_id);
$stmt->execute();
$result = $stmt->get_result();
$department = $result->fetch_assoc();

if (!$department) {
    die("Department not found!");
}

// ตรวจสอบว่ามีการส่งข้อมูลฟอร์มมาเพื่ออัปเดต
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dep_name = $_POST['dep_name'];

    // จัดการการอัปโหลดไฟล์รูปภาพ
    $dep_img = $department['dep_img']; // ใช้รูปเดิมถ้าไม่มีการอัปโหลดใหม่
    if (isset($_FILES['dep_img']) && $_FILES['dep_img']['error'] == 0) {
        $target_dir = "dep_img/";
        $file_extension = pathinfo($_FILES["dep_img"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid('dep_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // ย้ายไฟล์ใหม่และลบไฟล์เก่า (ถ้ามี)
        if (move_uploaded_file($_FILES["dep_img"]["tmp_name"], $target_file)) {
            if ($dep_img && file_exists($target_dir . $dep_img)) {
                unlink($target_dir . $dep_img);
            }
            $dep_img = $new_filename;
        } else {
            echo "Error uploading image.";
            exit;
        }
    }

    // อัปเดตข้อมูลแผนก
    $update_query = "UPDATE departments SET dep_name = ?, dep_img = ? WHERE dep_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssi", $dep_name, $dep_img, $dep_id);

    if ($stmt->execute()) {
        header("Location: manage_departments.php");
        exit;
    } else {
        echo "Error updating department: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
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
</head>
<body>
<div class="form-container mt-5">
    <h2 class="mb-4">Edit Department</h2>

    <form method="POST" action="edit_department.php?id=<?php echo $dep_id; ?>" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="dep_name" class="form-label">Department Name</label>
            <input type="text" class="form-control" id="dep_name" name="dep_name" value="<?php echo htmlspecialchars($department['dep_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="dep_img" class="form-label">Department Image</label><br>
            <?php if ($department['dep_img']): ?>
                <img src="dep_img/<?php echo htmlspecialchars($department['dep_img']); ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover; margin-bottom: 10px;">
            <?php endif; ?>
            <input type="file" class="form-control" id="dep_img" name="dep_img" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="manage_departments.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
