<?php
session_start();
require_once 'config/condb.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $queue_id = $_POST['queue_id'] ?? null;

    if (!$queue_id) {
        $_SESSION['delete_queue_error'] = 'ไม่พบรหัสคิว';
        header('Location: show_queue.php');
        exit;
    }

    $conn->beginTransaction();
    try {
        // ลบข้อมูลนักเรียนในคิว
        $stmt = $conn->prepare("DELETE FROM queue_student WHERE queue_id = ?");
        $stmt->execute([$queue_id]);

        // ลบคิว
        $stmt = $conn->prepare("DELETE FROM queue WHERE queue_id = ?");
        $stmt->execute([$queue_id]);

        $conn->commit();
        $_SESSION['delete_queue_success'] = true;
        header('Location: show_queue.php');
        exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['delete_queue_error'] = 'เกิดข้อผิดพลาดในการลบคิว: ' . $e->getMessage();
        header('Location: show_queue.php');
        exit;
    }
} else {
    header('Location: show_queue.php');
    exit;
}
?>