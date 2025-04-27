<?php
session_start();
include 'config/condb.php';

try {
    $stmt = $conn->prepare("UPDATE news SET news_title = :news_title, news_content = :news_content, news_date = :news_date, news_image = :news_image 
                           WHERE news_id = :news_id");
    $stmt->bindParam(':news_id', $_POST['news_id']);
    $stmt->bindParam(':news_title', $_POST['news_title']);
    $stmt->bindParam(':news_content', $_POST['news_content']);
    $stmt->bindParam(':news_date', $_POST['news_date']);

    // ดึงรูปภาพเก่าก่อน
    $stmt_old = $conn->prepare("SELECT news_image FROM news WHERE news_id = :news_id");
    $stmt_old->bindParam(':news_id', $_POST['news_id']);
    $stmt_old->execute();
    $old_image = $stmt_old->fetchColumn();

    $news_image = $old_image;
    if (!empty($_FILES['news_image']['name'])) {
        $targetDir = "uploads/news/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = uniqid() . '.' . pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['news_image']['tmp_name'], $targetFile)) {
            $news_image = $fileName;
            // ลบรูปภาพเก่า
            if ($old_image && file_exists($targetDir . $old_image)) {
                unlink($targetDir . $old_image);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้']);
            exit;
        }
    }
    $stmt->bindParam(':news_image', $news_image);

    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'แก้ไขข่าวสารสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>