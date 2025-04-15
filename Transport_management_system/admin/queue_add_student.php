<?php
session_start();
require_once 'config/condb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queue_id = $_POST['queue_id'] ?? null;
    $student_id = $_POST['student_id'] ?? null;

    if (!$queue_id || !$student_id) {
        $_SESSION['add_error'] = 'ข้อมูลไม่ครบถ้วน';
        header('Location: show_queue.php');
        exit;
    }

    try {
        // ตรวจสอบว่านักเรียนอยู่ในคิวนี้แล้วหรือไม่
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM queue_student WHERE queue_id = ? AND student_id = ?");
        $check_stmt->execute([$queue_id, $student_id]);
        if ($check_stmt->fetchColumn() > 0) {
            $_SESSION['add_error'] = 'นักเรียนนี้อยู่ในคิวแล้ว';
            header('Location: show_queue.php');
            exit;
        }

        // เพิ่มนักเรียนเข้าในคิว
        $stmt = $conn->prepare("INSERT INTO queue_student (queue_id, student_id) VALUES (?, ?)");
        $stmt->execute([$queue_id, $student_id]);

        $_SESSION['add_success'] = true;
        header('Location: show_queue.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['add_error'] = 'เกิดข้อผิดพลาดในการเพิ่มนักเรียน: ' . $e->getMessage();
        header('Location: show_queue.php');
        exit;
    }
} else {
    header('Location: show_queue.php');
    exit;
}
?>