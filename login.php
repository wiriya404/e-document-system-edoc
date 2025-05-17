<?php
session_start();
include 'req/db_connection.php';

// ตรวจสอบว่าผู้ใช้เข้าสู่ระบบแล้วหรือยัง
if (isset($_SESSION['us_id'])) {
    header('Location: show_doc.php');
    exit;
}

$error_message = ''; // ตัวแปรสำหรับเก็บข้อความผิดพลาด

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $query = "SELECT * FROM users WHERE us_name = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['us_pwd'])) {
            // ตั้งค่า session เมื่อเข้าสู่ระบบสำเร็จ
            $_SESSION['us_id'] = $user['us_id'];
            $_SESSION['us_name'] = $user['us_name'];
            $_SESSION['us_dep'] = $user['us_dep'];
            $_SESSION['us_role'] = $user['us_role'];
            $_SESSION['us_stat'] = $user['us_stat'];
            $_SESSION['imgprofile'] = $user['imgprofile'];

            header('Location: show_doc.php');
            exit;
        } else {
            $error_message = 'รหัสผ่านไม่ถูกต้อง'; // แสดงข้อความรหัสผ่านผิด
        }
    } else {
        $error_message = 'ไม่พบผู้ใช้'; // แสดงข้อความไม่พบผู้ใช้
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ปรับขนาดฟอนต์และจัดตำแหน่งให้สวยงาม */
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>

        <!-- แสดงข้อความผิดพลาด (ถ้ามี) -->
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
