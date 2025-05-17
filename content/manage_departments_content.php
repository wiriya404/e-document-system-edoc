<?php
// เชื่อมต่อฐานข้อมูล
include 'req/db_connection.php';

// ตรวจสอบการลบ Department
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $delete_query = "DELETE FROM departments WHERE dep_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        header("Location: manage_departments.php");
        exit;
    } else {
        echo "Error deleting department: " . $stmt->error;
    }
}

// ดึงข้อมูล departments ทั้งหมด
$query = "SELECT * FROM departments";
$result = $conn->query($query);
if (!$result) {
    die("Error retrieving data: " . $conn->error);
}

// ตรวจสอบการเพิ่ม Department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_department'])) {
    $dep_name = $_POST['dep_name'];

    // จัดการการอัปโหลดรูปภาพ
    $dep_img = '';
    if (isset($_FILES['dep_img']) && $_FILES['dep_img']['error'] == 0) {
        $target_dir = "dep_img/";
        $file_extension = pathinfo($_FILES["dep_img"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid('dep_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        // ตรวจสอบและย้ายไฟล์ที่อัปโหลด
        if (move_uploaded_file($_FILES["dep_img"]["tmp_name"], $target_file)) {
            $dep_img = $new_filename;
        } else {
            echo "Error uploading image.";
            exit;
        }
    }

    $insert_query = "INSERT INTO departments (dep_name, dep_img) VALUES (?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ss", $dep_name, $dep_img);
    if ($stmt->execute()) {
        header("Location: manage_departments.php");
        exit;
    } else {
        echo "Error adding department: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments</title>
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
    <div class="container mt-5">
        <div class="form-container">
            <h2 class="mb-4">Manage Departments</h2>

            <!-- Form เพิ่ม Department -->
            <form method="POST" action="manage_departments.php" class="mb-4" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="dep_name" placeholder="Department Name" required>
                    </div>
                    <div class="col-md-4">
                        <input type="file" class="form-control" name="dep_img" accept="image/*">
                    </div>
                    <div class="col-md-2 mt-2">
                        <button type="submit" name="add_department" class="btn btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>

            <!-- ตารางแสดง Departments -->
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>#</th>
                        <th>Department Name</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['dep_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['dep_name']); ?></td>
                            <td>
                                <?php if ($row['dep_img']): ?>
                                    <img src="dep_img/<?php echo htmlspecialchars($row['dep_img']); ?>" alt="Department Image"
                                        style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    No Image
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_department.php?id=<?php echo $row['dep_id']; ?>"
                                    class="btn btn-warning btn-sm">Edit</a>
                                <a href="manage_departments.php?delete_id=<?php echo $row['dep_id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Are you sure you want to delete this department?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>