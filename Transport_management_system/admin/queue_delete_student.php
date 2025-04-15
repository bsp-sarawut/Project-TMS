<?php
session_start();
require_once 'config/condb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queue_id = $_POST['queue_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    if (!$queue_id || !$student_id) {
        $_SESSION['delete_student_error'] = 'ข้อมูลไม่ครบถ้วน';
        header('Location: show_queue.php');
        exit;
    }

    try {
        // ลบนักเรียนออกจากคิว
        $stmt = $conn->prepare("DELETE FROM queue_student WHERE queue_id = ? AND student_id = ?");
        $stmt->execute([$queue_id, $student_id]);

        $_SESSION['delete_student_success'] = true;
        header('Location: show_queue.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['delete_student_error'] = 'เกิดข้อผิดพลาดในการลบนักเรียน: ' . $e->getMessage();
        header('Location: show_queue.php');
        exit;
    }
} else {
    header('Location: show_queue.php');
    exit;
}
?>