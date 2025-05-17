<?php
include 'req/db_connection.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['us_id']) || !isset($_SESSION['us_dep'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['us_id'];
$current_user_dep = $_SESSION['us_dep'];
$user_role = $_SESSION['us_role']; // ตรวจสอบบทบาทของผู้ใช้

// ตรวจสอบว่าได้ส่ง doc_id หรือไม่
if (isset($_GET['doc_id'])) {
    $doc_id = $_GET['doc_id'];

    // ตรวจสอบว่าเป็น admin หรือไม่
    if ($user_role === 'admin') {
        // Admin สามารถแก้ไขเอกสารได้ทุกฉบับ
        $query = "
            SELECT d.*, 
                   dep.dep_name, 
                   u.us_name AS doc_user_name, 
                   sender.us_name AS who_sent_name
            FROM documents d
            LEFT JOIN departments dep ON d.doc_dep = dep.dep_id
            LEFT JOIN users u ON d.doc_user = u.us_id
            LEFT JOIN users sender ON d.who_sent = sender.us_id
            WHERE d.doc_id = ?
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $doc_id);
    } else {
        // ผู้ใช้ทั่วไป ต้องเป็นเจ้าของเอกสารหรือเป็นผู้ส่งเท่านั้น
        $query = "
            SELECT d.*, 
                   dep.dep_name, 
                   u.us_name AS doc_user_name, 
                   sender.us_name AS who_sent_name
            FROM documents d
            LEFT JOIN departments dep ON d.doc_dep = dep.dep_id
            LEFT JOIN users u ON d.doc_user = u.us_id
            LEFT JOIN users sender ON d.who_sent = sender.us_id
            WHERE d.doc_id = ? AND (d.doc_user = ? OR d.who_sent = ?)
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $doc_id, $current_user_id, $current_user_id);
    }

    // ดำเนินการ SQL
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $doc = $result->fetch_assoc();
    } else {
        echo "<script>alert('คุณไม่มีสิทธิ์ในการแก้ไขเอกสารนี้'); window.location.href = 'manage_doc.php';</script>";
        exit;
    }
} else {
    header("Location: manage_doc.php");
    exit;
}

// ตรวจสอบว่าฟอร์มได้รับค่าหรือไม่
if (isset($_POST['submit'])) {
    $doc_name = $_POST['doc_name'];
    $doc_dep = $_POST['doc_dep'];
    $doc_user = $_POST['doc_user'];

    // ตรวจสอบว่า doc_name ถูกกรอกหรือไม่
    if (empty($doc_name)) {
        echo "<script>alert('กรุณากรอกชื่อเอกสาร');</script>";
    } else {
        // ฟังก์ชั่นอัปโหลดไฟล์ใหม่
        if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['doc_file']['tmp_name'];
            $fileName = $_FILES['doc_file']['name']; // ชื่อไฟล์ต้นฉบับ
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'png']; // นามสกุลที่อนุญาต

            // ตรวจสอบว่านามสกุลไฟล์ตรงกับที่อนุญาตหรือไม่
            if (in_array($fileExtension, $allowedExtensions)) {
                // สร้างชื่อไฟล์ใหม่โดยใช้ uniqid
                $newFileName = uniqid('', true) . '.' . $fileExtension;
                $uploadPath = 'files/' . $newFileName;

                // ย้ายไฟล์ไปยังโฟลเดอร์ที่ต้องการ
                if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                    // อัปเดตฐานข้อมูลพร้อมกับชื่อไฟล์ใหม่
                    $update_query = "UPDATE documents SET doc_name = ?, doc_dep = ?, doc_user = ?, doc_file = ?, original_name = ? WHERE doc_id = ?";
                    $stmt_update = $conn->prepare($update_query);
                    $stmt_update->bind_param("siissi", $doc_name, $doc_dep, $doc_user, $newFileName, $fileName, $doc_id);
                    if ($stmt_update->execute()) {
                        echo "<script>alert('เอกสารถูกแก้ไขเรียบร้อย'); window.location.href = 'manage_doc.php';</script>";
                    } else {
                        echo "<script>alert('เกิดข้อผิดพลาดในการแก้ไขเอกสาร');</script>";
                    }
                } else {
                    echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้');</script>";
                }
            } else {
                echo "<script>alert('นามสกุลไฟล์ไม่ถูกต้อง กรุณาอัปโหลดไฟล์ที่เป็น PDF, DOC, DOCX, JPG, หรือ PNG');</script>";
            }
        } else {
            // ถ้าไม่มีการอัปโหลดไฟล์ใหม่
            $update_query = "UPDATE documents SET doc_name = ?, doc_dep = ?, doc_user = ? WHERE doc_id = ?";
            $stmt_update = $conn->prepare($update_query);
            $stmt_update->bind_param("siii", $doc_name, $doc_dep, $doc_user, $doc_id);
            if ($stmt_update->execute()) {
                echo "<script>alert('เอกสารถูกแก้ไขเรียบร้อย'); window.location.href = 'manage_doc.php';</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการแก้ไขเอกสาร');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขเอกสาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7fc;
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
        <h2>แก้ไขเอกสาร</h2>
        <form action="" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="doc_name" class="form-label">ชื่อเอกสาร</label>
                <input type="text" name="doc_name" id="doc_name" class="form-control" value="<?php echo htmlspecialchars($doc['doc_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="doc_dep" class="form-label">หน่วยงาน</label>
                <select name="doc_dep" id="doc_dep" class="form-control">
                    <option value="">เลือกหน่วยงาน</option>
                    <?php
                    // ดึงข้อมูลหน่วยงานทั้งหมด
                    $dep_query = "SELECT * FROM departments";
                    $dep_result = $conn->query($dep_query);
                    while ($dep_row = $dep_result->fetch_assoc()) {
                        $selected = ($dep_row['dep_id'] == $doc['doc_dep']) ? 'selected' : '';
                        echo "<option value='" . $dep_row['dep_id'] . "' $selected>" . htmlspecialchars($dep_row['dep_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="doc_user" class="form-label">ผู้รับ</label>
                <select name="doc_user" id="doc_user" class="form-control">
                    <option value="">เลือกผู้รับ</option>
                    <?php
                    // ดึงข้อมูลผู้ใช้ทั้งหมด
                    $user_query = "SELECT * FROM users";
                    $user_result = $conn->query($user_query);
                    while ($user_row = $user_result->fetch_assoc()) {
                        $selected = ($user_row['us_id'] == $doc['doc_user']) ? 'selected' : '';
                        echo "<option value='" . $user_row['us_id'] . "' $selected>" . htmlspecialchars($user_row['us_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="doc_file" class="form-label">ไฟล์เอกสาร (ถ้าต้องการอัปโหลดไฟล์ใหม่)</label>
                <input type="file" name="doc_file" id="doc_file" class="form-control">
                <small class="form-text text-muted">
                    ไฟล์ปัจจุบัน:
                    <a href="files/<?php echo htmlspecialchars($doc['doc_file']); ?>" target="_blank">
                        <?php echo htmlspecialchars($doc['original_name']); ?>
                    </a>
                </small>
            </div>
            <button type="submit" name="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>