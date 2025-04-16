<?php
session_start();
include 'config/condb.php';

$response = ['status' => '', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('คำขอไม่ถูกต้อง');
    }

    $stu_id = trim($_POST['stu_id'] ?? '');
    if (empty($stu_id) || !is_numeric($stu_id)) {
        throw new Exception('รหัสนักศึกษาไม่ถูกต้อง');
    }

    error_log("Received stu_id for delete: " . $stu_id); // ดีบัก

    // ดึงข้อมูลรูปภาพเพื่อลบไฟล์
    $stmt_img = $conn->prepare("SELECT stu_img FROM students WHERE stu_ID = :stu_ID");
    $stmt_img->bindParam(':stu_ID', $stu_id, PDO::PARAM_INT);
    $stmt_img->execute();
    $student = $stmt_img->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('ไม่พบข้อมูลนักศึกษา');
    }

    // ลบรูปภาพถ้ามี
    if ($student['stu_img']) {
        $file_path = '../Student/uploads/' . $student['stu_img'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // ลบข้อมูลนักศึกษา
    $stmt = $conn->prepare("DELETE FROM students WHERE stu_ID = :stu_ID");
    $stmt->bindParam(':stu_ID', $stu_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'ลบข้อมูลนักศึกษาเรียบร้อยแล้ว';
    } else {
        throw new Exception('ไม่สามารถลบข้อมูลนักศึกษาได้');
    }
} catch (Exception $e) {
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>