<?php
session_start();
include 'config/condb.php';

try {
    $stmt = $conn->prepare("INSERT INTO news (news_title, news_content, news_date, news_image) 
                           VALUES (:news_title, :news_content, :news_date, :news_image)");
    $stmt->bindParam(':news_title', $_POST['news_title']);
    $stmt->bindParam(':news_content', $_POST['news_content']);
    $stmt->bindParam(':news_date', $_POST['news_date']);

    $news_image = '';
    if (!empty($_FILES['news_image']['name'])) {
        $targetDir = "uploads/news/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = uniqid() . '.' . pathinfo($_FILES['news_image']['name'], PATHINFO_EXTENSION);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['news_image']['tmp_name'], $targetFile)) {
            $news_image = $fileName;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่สามารถอัปโหลดรูปภาพได้']);
            exit;
        }
    }
    $stmt->bindParam(':news_image', $news_image);

    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'เพิ่มข่าวสารสำเร็จ']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>