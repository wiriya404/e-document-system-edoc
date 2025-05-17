<?php
include 'req/db_connection.php';
$us_id = $_SESSION['us_id'];

// ดึงข้อมูลหน่วยงาน
$departments = [];
$dep_query = "SELECT * FROM `departments`";
if ($result = $conn->query($dep_query)) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// ดึงข้อมูลผู้ใช้งาน
$users = [];
$us_query = "SELECT * FROM `users`";
if ($result = $conn->query($us_query)) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

if (isset($_POST['submit'])) {
    $doc_name = $_POST['doc_name'];
    $doc_date = date("Y-m-d");

    if (isset($_FILES['doc_file']) && $_FILES['doc_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['doc_file']['tmp_name'];
        $fileName = basename($_FILES['doc_file']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'xls', 'xlsx', 'txt', 'zip', 'mp4'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = uniqid('', true) . '.' . $fileExtension;
            $uploadPath = 'files/' . $newFileName;

            if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                $departments_selected = $_POST['dep_name'] ?? [];
                $users_selected = $_POST['user_sent'] ?? [];

                // **1. เพิ่มข้อมูลเอกสารลงในตาราง `documents`**
                $stmt = $conn->prepare("INSERT INTO `documents` (`doc_name`, `doc_date`, `doc_file`, `original_name`, `who_sent`) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $doc_name, $doc_date, $newFileName, $fileName, $us_id);
                $stmt->execute();

                // **2. ดึงค่า `doc_id` ที่เพิ่งเพิ่ม**
                $doc_id = $conn->insert_id;
                $stmt->close();

                // **3. เพิ่มข้อมูลเอกสารที่ส่งถึงหน่วยงาน**
                if (!empty($departments_selected)) {
                    $stmt = $conn->prepare("INSERT INTO `document_recipients` (`doc_id`, `dep_id`, `us_id`) VALUES (?, ?, NULL)");
                    foreach ($departments_selected as $dep_id) {
                        $stmt->bind_param('is', $doc_id, $dep_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                // **4. เพิ่มข้อมูลเอกสารที่ส่งถึงผู้ใช้งาน**
                if (!empty($users_selected)) {
                    $stmt = $conn->prepare("INSERT INTO `document_recipients` (`doc_id`, `dep_id`, `us_id`) VALUES (?, NULL, ?)");
                    foreach ($users_selected as $user_id) {
                        $stmt->bind_param('is', $doc_id, $user_id);
                        $stmt->execute();
                    }
                    $stmt->close();
                }

                echo "<script>alert('ส่งเอกสารสำเร็จ!');</script>";
            } else {
                echo "<script>alert('ไม่สามารถอัปโหลดไฟล์ได้!');</script>";
            }
        } else {
            echo "<script>alert('ไฟล์ต้องเป็นชนิด PDF, DOC, JPG, หรือ PNG เท่านั้น!');</script>";
        }
    } else {
        echo "<script>alert('กรุณาแนบไฟล์!');</script>";
    }
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งเอกสาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #eef2f7;
            font-family: 'Arial', sans-serif;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .button:hover {
            background-color: #218838;
        }
        .check-all-btn {
            background-color: #007bff;
            color: white;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .check-all-btn:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        function toggleCheckboxes(name, buttonId) {
            let checkboxes = document.querySelectorAll(`input[name="${name}[]"]`);
            let checkAllButton = document.getElementById(buttonId);
            let allChecked = [...checkboxes].every(checkbox => checkbox.checked);
            checkboxes.forEach(checkbox => checkbox.checked = !allChecked);
            checkAllButton.innerText = allChecked ? 'Check All' : 'Uncheck All';
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="form-container">
            <h4 class="mb-4 text-center text-primary">ส่งเอกสาร</h4>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="doc_name" class="form-label">ชื่อเรื่อง</label>
                    <input type="text" name="doc_name" id="doc_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">วันที่ส่ง</label>
                    <input type="text" class="form-control" value="<?php echo date('d-m-Y'); ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">ส่งถึงหน่วยงาน</label>
                    <div class="border p-2 rounded bg-light">
                        <span id="checkAllDepartments" class="check-all-btn" onclick="toggleCheckboxes('dep_name', 'checkAllDepartments')">Check All</span>
                        <?php foreach ($departments as $dep): ?>
                            <div class="form-check">
                                <input type="checkbox" name="dep_name[]" value="<?php echo $dep['dep_id']; ?>" class="form-check-input">
                                <label class="form-check-label"> <?php echo htmlspecialchars($dep['dep_name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">ส่งถึงผู้ใช้งานในระบบ</label>
                    <div class="border p-2 rounded bg-light">
                        <span id="checkAllUsers" class="check-all-btn" onclick="toggleCheckboxes('user_sent', 'checkAllUsers')">Check All</span>
                        <?php foreach ($users as $user): ?>
                            <div class="form-check">
                                <input type="checkbox" name="user_sent[]" value="<?php echo $user['us_id']; ?>" class="form-check-input">
                                <label class="form-check-label"> <?php echo htmlspecialchars($user['us_name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="doc_file" class="form-label">แนบไฟล์</label>
                    <input type="file" name="doc_file" id="doc_file" class="form-control" required>
                </div>
                <div class="text-center">
                    <button type="submit" name="submit" class="button">ส่งเอกสาร</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
