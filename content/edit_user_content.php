<?php
// เชื่อมต่อฐานข้อมูล
include 'req/db_connection.php';

// ตรวจสอบว่าได้ส่งค่าจาก URL สำหรับ id ของผู้ใช้
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // ดึงข้อมูลของผู้ใช้ที่ต้องการแก้ไขจากฐานข้อมูล
    $query = "SELECT * FROM users WHERE us_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "User not found!";
        exit;
    }
} else {
    echo "Invalid user ID!";
    exit;
}

// ดึงข้อมูล departments
$departments_query = "SELECT dep_id, dep_name FROM departments";
$departments_result = $conn->query($departments_query);

if (!$departments_result) {
    die('Error retrieving departments: ' . $conn->error);
}

// ตรวจสอบการส่งข้อมูลฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับข้อมูลที่แก้ไขจากฟอร์ม
    $username = $_POST['username'];
    $password = $_POST['password'];
    $department = $_POST['department'];
    $role = $_POST['role'];
    $status = $_POST['status'];

    // การจัดการการอัปโหลดรูปภาพ
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "imgprofile/";
        $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
        $new_filename = uniqid('img_', true) . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
            $image_path = $new_filename;
        } else {
            $image_path = $user['imgprofile'];
        }
    } else {
        $image_path = $user['imgprofile'];
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $hashed_password = $user['us_pwd'];
    }

    $update_query = "UPDATE users SET us_name = ?, us_pwd = ?, us_dep = ?, us_role = ?, us_stat = ?, imgprofile = ? WHERE us_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssi", $username, $hashed_password, $department, $role, $status, $image_path, $user_id);

    if ($stmt->execute()) {
        header('Location: manage_users.php');
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <h2>แก้ไขข้อมูลผู้ใช้</h2>
            <form action="edit_user.php?id=<?php echo $user['us_id']; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                        value="<?php echo htmlspecialchars($user['us_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="ปล่อยว่าถ้าจะใช้รหัสผ่านเดิม">
                </div>

                <div class="form-group">
                    <label for="department">หน่วยงาน</label>
                    <select class="form-control" id="department" name="department" required>
                        <option value="" disabled selected>Select Department</option>
                        <?php while ($department = $departments_result->fetch_assoc()): ?>
                            <option value="<?php echo $department['dep_id']; ?>" <?php echo ($user['us_dep'] == $department['dep_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['dep_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role">ตำแน่ง</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="admin" <?php echo ($user['us_role'] == 'admin') ? 'selected' : ''; ?>>Admin
                        </option>
                        <option value="user" <?php echo ($user['us_role'] == 'user') ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">สถานะ</label>
                    <select class="form-control" id="status" name="status" required>
                        <option value="active" <?php echo ($user['us_stat'] == 'active') ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="inactive" <?php echo ($user['us_stat'] == 'inactive') ? 'selected' : ''; ?>>
                            Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="profile_image">รูปโปรไฟล์</label>
                    <input type="file" class="form-control" id="profile_image" name="profile_image">
                    <img src="imgprofile/<?php echo htmlspecialchars($user['imgprofile']); ?>"
                        alt="Current Profile Image" class="img-thumbnail mt-3" style="max-width: 150px;">
                </div>

                <button type="submit" class="btn btn-primary">บันทึกข้อมูลผู้ใช้</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>