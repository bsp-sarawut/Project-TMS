<?php
session_start();
include 'config/condb.php';

try {
    // ดึงรูปภาพก่อนลบ
    $stmt = $conn->prepare("SELECT news_image FROM news WHERE news_id = :news_id");
    $stmt->bindParam(':news_id', $_POST['news_id']);
    $stmt->execute();
    $news_image = $stmt->fetchColumn();

    // ลบข้อมูล
    $stmt = $conn->prepare("DELETE FROM news WHERE news_id = :news_id");
    $stmt->bindParam(':news_id', $_POST['news_id']);
    $stmt->execute();

    // ลบรูปภาพ
    if ($news_image && file_exists("uploads/news/" . $news_image)) {
        unlink("uploads/news/" . $news_image);
    }

    echo json_encode(['status' => 'success', 'message' => 'ลบข่าวสารสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>