<?php
include 'req/db_connection.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['us_id']) || !isset($_SESSION['us_dep'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['us_id'];
$current_user_dep = $_SESSION['us_dep'];
$current_user_role = $_SESSION['us_role']; // เพิ่ม role
$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

$query = "
    SELECT d.*, 
           GROUP_CONCAT(DISTINCT dep.dep_name SEPARATOR ', ') AS dep_names, 
           GROUP_CONCAT(DISTINCT u.us_name SEPARATOR ', ') AS doc_user_names, 
           sender.us_name AS who_sent_name
    FROM documents d
    LEFT JOIN document_recipients dr ON d.doc_id = dr.doc_id
    LEFT JOIN departments dep ON dr.dep_id = dep.dep_id
    LEFT JOIN users u ON dr.us_id = u.us_id
    LEFT JOIN users sender ON d.who_sent = sender.us_id
";

if ($current_user_role === 'admin') {
    $query .= " WHERE (d.doc_name LIKE ? OR 
                       dep.dep_name LIKE ? OR 
                       u.us_name LIKE ? OR 
                       sender.us_name LIKE ?)
                GROUP BY d.doc_id
                ORDER BY d.doc_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $search, $search, $search, $search);
} else {
    $query .= " WHERE (dr.dep_id = ? OR dr.us_id = ?) 
                AND (d.doc_name LIKE ? OR 
                     dep.dep_name LIKE ? OR 
                     u.us_name LIKE ? OR 
                     sender.us_name LIKE ?)
                GROUP BY d.doc_id
                ORDER BY d.doc_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissss", $current_user_dep, $current_user_id, $search, $search, $search, $search);
}


$stmt->execute();
$result = $stmt->get_result();

if (isset($_GET['doc_id'])) {
    $doc_id = intval($_GET['doc_id']);

    // ตรวจสอบว่ามีเอกสารนี้ในระบบหรือไม่
    $query_doc = "SELECT doc_file, original_name FROM documents WHERE doc_id = ?";
    $stmt_doc = $conn->prepare($query_doc);
    $stmt_doc->bind_param("i", $doc_id);
    $stmt_doc->execute();
    $doc_result = $stmt_doc->get_result();

    if ($doc_result->num_rows > 0) {
        $doc_data = $doc_result->fetch_assoc();
        $file_path = "files/" . $doc_data['doc_file'];  // path to file
        $original_name = $doc_data['original_name'];    // original file name

        // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
        if (file_exists($file_path)) {
            // บันทึกการดาวน์โหลดในฐานข้อมูล
            $query_insert = "INSERT INTO readchk (doc_id, us_dep, us_id) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($query_insert);
            $stmt_insert->bind_param("iii", $doc_id, $current_user_dep, $current_user_id);
            $stmt_insert->execute();
            $stmt_insert->close();

            // กำหนด header สำหรับดาวน์โหลดไฟล์
            header('Content-Description: File Transfer');
            header('Content-Type: ' . mime_content_type($file_path));
            header('Content-Disposition: attachment; filename="' . basename($original_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            ob_clean();
            flush();
            readfile($file_path);
            exit();
        } else {
            echo "<script>alert('ไม่พบไฟล์ในระบบ'); window.location='show_doc.php';</script>";
        }
    } else {
        echo "<script>alert('ไม่พบเอกสารที่ต้องการ'); window.location='show_doc.php';</script>";
    }
    $stmt_doc->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents</title>
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

        .modal-body iframe {
            width: 100%;
            height: 80vh;
            /* Adjusts the height of the iframe relative to the viewport height */
            border: none;
        }

        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .file-icon {
            font-size: 48px;
        }

        .file-icon.pdf {
            color: red;
        }

        .file-icon.doc,
        .file-icon.docx {
            color: blue;
        }

        .file-icon.xls,
        .file-icon.xlsx {
            color: green;
        }

        .file-icon.txt {
            color: orange;
        }

        .file-icon.default {
            color: gray;
        }

        .file-info {
            font-size: 0.9rem;
            color: #555;
        }

        .file-info strong {
            font-weight: 600;
        }

        .download-btn,
        .preview-btn {
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="form-container">
            <h2 class="mb-4">จัดการเอกสาร</h2>
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาเอกสาร..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">ค้นหา</button>
                </div>
            </form>
            <div class="row">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $file_extension = pathinfo($row['doc_file'], PATHINFO_EXTENSION);
                        $icon_class = match ($file_extension) {
                            'pdf' => 'pdf',
                            'png' => 'png',
                            'jpg' => 'jpg',
                            'doc', 'docx' => 'doc',
                            'xls', 'xlsx' => 'xls',
                            'txt' => 'txt',
                            default => 'default',
                        };
                        $file_path = "files/" . $row['doc_file'];
                    ?>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['doc_name']); ?></h5>
                                    <div class="file-info">
                                        <p><strong>วันที่:</strong> <?php echo date("d-m-Y", strtotime($row['doc_date'])); ?>
                                        </p>
                                        <p><strong>หน่วยงานที่ได้รับ:</strong> <?php echo htmlspecialchars($row['dep_names'] ?? 'ไม่มีข้อมูล'); ?></p>
                                        <p><strong>ผู้รับ:</strong> <?php echo htmlspecialchars($row['doc_user_names'] ?? 'ไม่มีข้อมูล'); ?></p>
                                        <p><strong>ผู้ส่ง:</strong> <?php echo htmlspecialchars($row['who_sent_name']); ?></p>
                                    </div>
                                    <div class="text-center mb-3">
                                        <i class="file-icon <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <?php
                                    // Check if the file type is pdf, image, or txt
                                    if (in_array($file_extension, ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'txt'])):
                                    ?>
                                        <button class="btn btn-info btn-sm preview-btn" data-file-path="<?php echo $file_path; ?>"
                                            data-file-type="<?php echo $file_extension; ?>" data-bs-toggle="modal"
                                            data-bs-target="#previewModal">
                                            แสดงตัวอย่าง
                                        </button>
                                    <?php endif; ?>
                                    <?php
                                    $query_chk = "SELECT * FROM readchk WHERE doc_id = ? AND us_id = ?";
                                    $stmt_chk = $conn->prepare($query_chk);
                                    $stmt_chk->bind_param("ii", $row['doc_id'], $current_user_id);
                                    $stmt_chk->execute();
                                    $chk_result = $stmt_chk->get_result();
                                    if ($chk_result->num_rows == 0): ?>
                                        <a href="?doc_id=<?php echo $row['doc_id']; ?>" class="btn btn-primary btn-sm download-btn"
                                            onclick="return confirm('คุณต้องการดาวน์โหลดไฟล์นี้ใช่หรือไม่?');">
                                            ดาวน์โหลด
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">คุณได้ดาวน์โหลดไฟล์นี้แล้ว</span>
                                    <?php endif; ?>
                                    <?php $stmt_chk->close(); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center">ไม่มีเอกสารที่เกี่ยวข้อง</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal for Preview -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">ตัวอย่างไฟล์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Content will be inserted dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Handle preview button click to show the preview in modal
        document.querySelectorAll('.preview-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                var filePath = this.getAttribute('data-file-path');
                var fileType = this.getAttribute('data-file-type');
                var modalBody = document.getElementById('modalBody');

                // Clear previous content in modal
                modalBody.innerHTML = '';

                // Show preview based on file type
                if (fileType === 'pdf') {
                    var iframe = document.createElement('iframe');
                    iframe.src = filePath;
                    iframe.classList.add('embed-responsive-item'); // Bootstrap class to make iframe responsive
                    modalBody.appendChild(iframe);
                } else if (['png', 'jpg', 'jpeg'].includes(fileType)) {
                    var img = document.createElement('img');
                    img.src = filePath;
                    img.style.width = '100%';
                    img.style.height = 'auto';
                    modalBody.appendChild(img);
                } else if (fileType === 'txt') {
                    fetch(filePath)
                        .then(response => response.text())
                        .then(text => {
                            var pre = document.createElement('pre');
                            pre.textContent = text;
                            modalBody.appendChild(pre);
                        });
                } else {
                    modalBody.innerHTML = '<p>ไม่สามารถแสดงตัวอย่างไฟล์นี้ได้</p>';
                }
            });
        });
    </script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>