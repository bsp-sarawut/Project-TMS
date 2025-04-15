<?php
session_start();
require_once 'config/condb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queue_id = $_POST['queue_id'] ?? null;
    $old_student_id = $_POST['old_student_id'] ?? null;
    $new_student_id = $_POST['new_student_id'] ?? null;

    if (!$queue_id || !$old_student_id || !$new_student_id) {
        $_SESSION['edit_error'] = 'ข้อมูลไม่ครบถ้วน';
        header('Location: show_queue.php');
        exit;
    }

    try {
        // อัปเดตนักเรียนในคิว
        $stmt = $conn->prepare("UPDATE queue_student SET student_id = ? WHERE queue_id = ? AND student_id = ?");
        $stmt->execute([$new_student_id, $queue_id, $old_student_id]);

        $_SESSION['edit_success'] = true;
        header('Location: show_queue.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['edit_error'] = 'เกิดข้อผิดพลาดในการแก้ไขนักเรียน: ' . $e->getMessage();
        header('Location: show_queue.php');
        exit;
    }
} else {
    header('Location: show_queue.php');
    exit;
}
?>