<?php
// เชื่อมต่อฐานข้อมูล
include 'req/db_connection.php';

// ดึงข้อมูล Departments
$departments_query = "SELECT dep_id, dep_name FROM departments";
$departments_result = $conn->query($departments_query);

// ตรวจสอบว่าเป็นการส่งข้อมูลผ่าน POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password'];
    $department = $_POST['department'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // เข้ารหัสรหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // จัดการไฟล์รูปโปรไฟล์
    $imgprofile = '';
    if (isset($_FILES['imgprofile']) && $_FILES['imgprofile']['error'] == 0) {
        $target_dir = "imgprofile/";
        $file_extension = pathinfo($_FILES["imgprofile"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid('img_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        move_uploaded_file($_FILES["imgprofile"]["tmp_name"], $target_file);
        $imgprofile = $new_filename;
    }

    // สั่งเพิ่มผู้ใช้ใหม่ในฐานข้อมูล
    $query = "INSERT INTO users (us_name, us_pwd, us_dep, us_role, us_stat, imgprofile) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssss", $username, $hashed_password, $department, $role, $status, $imgprofile);

    if ($stmt->execute()) {
        echo "User added successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }

    // ปิดการเชื่อมต่อ
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
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
            <h2>เพิ่มผู้ใช้</h2>
            <form action="add_user.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="กรอก Username ผู้ใช้">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="กรอก Password ผู้ใช้">
                </div>

                <div class="form-group">
                    <label for="department">หน่วยงาน</label>
                    <select class="form-control" id="department" name="department" required>
                        <option value="" disabled selected>เลือกหน่วยงาน</option>
                        <?php while ($row = $departments_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['dep_id']; ?>"><?php echo htmlspecialchars($row['dep_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role">ตำแหน่ง</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">สถานะ</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="imgprofile">รูปโปรไฟล์</label>
                    <input type="file" class="form-control" id="imgprofile" name="imgprofile">
                </div>

                <button type="submit" class="btn btn-primary">เพิ่มผู้ใช้</button>
            </form>
        </div>
    </div>

</body>

</html>