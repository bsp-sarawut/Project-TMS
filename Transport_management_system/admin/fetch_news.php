<?php
include 'config/condb.php';

$response = ['news' => []];

try {
    $stmt = $conn->prepare("SELECT * FROM news ORDER BY news_date DESC");
    $stmt->execute();
    $news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['news'] = $news_items;
} catch (PDOException $e) {
    $response['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>