<?php
include 'req/db_connection.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['us_id']) || !isset($_SESSION['us_dep'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['us_id'];
$current_user_dep = $_SESSION['us_dep'];

// ตรวจสอบว่าเป็น admin หรือไม่
$is_admin = false;
$role_query = "SELECT us_role FROM users WHERE us_id = ?";
$role_stmt = $conn->prepare($role_query);
$role_stmt->bind_param("i", $current_user_id);
$role_stmt->execute();
$role_stmt->bind_result($role);
$role_stmt->fetch();
$role_stmt->close();

if (isset($_GET['delete_id'])) {
    $doc_id = $_GET['delete_id'];

    // ตรวจสอบว่าเอกสารมีอยู่หรือไม่
    $check_query = "SELECT doc_file FROM documents WHERE doc_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $doc_id);
    $check_stmt->execute();
    $check_stmt->bind_result($doc_file);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($doc_file) {
        // ลบไฟล์จากโฟลเดอร์
        $file_path = "files/" . $doc_file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // ลบข้อมูลจาก document_recipients ก่อน
        $delete_recipients = $conn->prepare("DELETE FROM document_recipients WHERE doc_id = ?");
        $delete_recipients->bind_param("i", $doc_id);
        $delete_recipients->execute();
        $delete_recipients->close();

        // ลบข้อมูลจาก documents
        $delete_query = "DELETE FROM documents WHERE doc_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $doc_id);
        if ($delete_stmt->execute()) {
            echo "<script>alert('ลบเอกสารเรียบร้อย'); window.location.href='manage_doc.php';</script>";
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการลบ');</script>";
        }
        $delete_stmt->close();
    }
}

$search = isset($_GET['search']) ? "%" . $_GET['search'] . "%" : "%";

if ($role === 'admin') {
    $is_admin = true;
    $query = "
    SELECT d.doc_id, d.doc_name, d.doc_date, d.doc_file, d.who_sent,
       GROUP_CONCAT(DISTINCT dep.dep_name ORDER BY dep.dep_name SEPARATOR ', ') AS dep_names, 
       GROUP_CONCAT(DISTINCT recipient.us_name ORDER BY recipient.us_name SEPARATOR ', ') AS doc_user_names,
       sender.us_name AS who_sent_name
    FROM documents d
    LEFT JOIN document_recipients dr ON d.doc_id = dr.doc_id
    LEFT JOIN departments dep ON dr.dep_id = dep.dep_id
    LEFT JOIN users recipient ON dr.us_id = recipient.us_id  -- ดึงชื่อผู้รับ
    LEFT JOIN users sender ON d.who_sent = sender.us_id
    WHERE d.doc_name LIKE ?
       OR dep.dep_name LIKE ?
       OR sender.us_name LIKE ?
    GROUP BY d.doc_id, sender.us_name
    ORDER BY d.doc_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search, $search, $search);
} else {
    $query = "
    SELECT d.doc_id, d.doc_name, d.doc_date, d.doc_file, d.who_sent,
       GROUP_CONCAT(DISTINCT dep.dep_name ORDER BY dep.dep_name SEPARATOR ', ') AS dep_names, 
       GROUP_CONCAT(DISTINCT recipient.us_name ORDER BY recipient.us_name SEPARATOR ', ') AS doc_user_names,
       sender.us_name AS who_sent_name
    FROM documents d
    LEFT JOIN document_recipients dr ON d.doc_id = dr.doc_id
    LEFT JOIN departments dep ON dr.dep_id = dep.dep_id
    LEFT JOIN users recipient ON dr.us_id = recipient.us_id  -- ดึงชื่อผู้รับ
    LEFT JOIN users sender ON d.who_sent = sender.us_id
    WHERE d.who_sent = ? 
      AND (d.doc_name LIKE ? OR dep.dep_name LIKE ? OR sender.us_name LIKE ?)
    GROUP BY d.doc_id, sender.us_name
    ORDER BY d.doc_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $current_user_id, $search, $search, $search);
}




$stmt->execute();
$result = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเอกสาร</title>
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
    <div class="container mt-5">
        <div class="form-container">
            <h2 class="mb-4">จัดการเอกสาร</h2>
            <form method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาเอกสาร..."
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-primary">ค้นหา</button>
                </div>
            </form>
            <div class="row g-3 d-flex flex-wrap">
                <?php if ($result->num_rows > 0): ?>
                    <?php
                    while ($row = $result->fetch_assoc()):
                    ?>
                        <?php
                        $doc_id = $row['doc_id'];
                        $check_query = "SELECT * FROM readchk WHERE doc_id = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("i", $doc_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        $can_edit = $check_result->num_rows == 0 ? true : false;
                        $check_stmt->close();
                        ?>
                        <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12">
                            <div class="card h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['doc_name']); ?></h5>
                                    <p class="card-text">
                                        <strong>วันที่:</strong> <?php echo date("d-m-Y", strtotime($row['doc_date'])); ?><br>
                                        <strong>หน่วยงาน:</strong> <?php echo htmlspecialchars($row['dep_names'] ?? 'ไม่มีข้อมูล'); ?><br>
                                        <strong>ผู้รับ:</strong> <?php echo htmlspecialchars($row['doc_user_names'] ?? 'ไม่มีข้อมูล'); ?><br>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <!-- ปุ่มดาวน์โหลด -->
                                        <a href="files/<?php echo htmlspecialchars($row['doc_file']); ?>" class="btn btn-primary btn-sm">
                                            ดาวน์โหลด
                                        </a>

                                        <div class="d-flex gap-2">
                                            <?php if ($is_admin || $row['who_sent'] == $current_user_id): ?>
                                                <?php if ($can_edit): ?>
                                                    <a href="edit_document.php?doc_id=<?php echo $row['doc_id']; ?>" class="btn btn-warning btn-sm">
                                                        แก้ไข
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small align-self-center">ไม่สามารถแก้ไขได้</span>
                                                <?php endif; ?>

                                                <a href="?delete_id=<?php echo $row['doc_id']; ?>" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('คุณต้องการลบเอกสารนี้หรือไม่?');">
                                                    ลบ
                                                </a>
                                            <?php endif; ?>
                                        </div>

                                        <!-- ปุ่มดูประวัติการอ่าน -->
                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#readHistoryModal<?php echo $row['doc_id']; ?>">
                                            ประวัติการอ่าน
                                        </button>
                                    </div>

                                    <!-- Modal สำหรับประวัติการอ่าน -->
                                    <!-- Modal สำหรับประวัติการอ่าน -->
                                    <div class="modal fade" id="readHistoryModal<?php echo $row['doc_id']; ?>" tabindex="-1" aria-labelledby="readHistoryLabel<?php echo $row['doc_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="readHistoryLabel<?php echo $row['doc_id']; ?>">ประวัติการอ่านเอกสาร</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>วันที่อ่าน</th>
                                                                <th>ชื่อผู้ใช้</th>
                                                                <th>หน่วยงาน</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $read_query = "
                            SELECT r.read_date, u.us_name, d.dep_name 
                            FROM readchk r
                            LEFT JOIN users u ON r.us_id = u.us_id
                            LEFT JOIN departments d ON r.us_dep = d.dep_id
                            WHERE r.doc_id = ?";
                                                            $read_stmt = $conn->prepare($read_query);
                                                            $read_stmt->bind_param("i", $row['doc_id']);
                                                            $read_stmt->execute();
                                                            $read_result = $read_stmt->get_result();

                                                            if ($read_result->num_rows > 0) {
                                                                while ($read_row = $read_result->fetch_assoc()) {
                                                                    echo "<tr>
                                    <td>" . date("d-m-Y H:i", strtotime($read_row['read_date'])) . "</td>
                                    <td>" . htmlspecialchars($read_row['us_name'] ?? 'ไม่พบข้อมูล') . "</td>
                                    <td>" . htmlspecialchars($read_row['dep_name'] ?? 'ไม่พบข้อมูล') . "</td>
                                </tr>";
                                                                }
                                                            } else {
                                                                echo "<tr><td colspan='3' class='text-center'>ไม่มีข้อมูลการอ่าน</td></tr>";
                                                            }
                                                            $read_stmt->close();
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">ไม่พบเอกสาร</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>