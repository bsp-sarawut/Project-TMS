<?php
require_once '../config/condb.php';

header('Content-Type: application/json');

if (!isset($_GET['queue_id']) || !is_numeric($_GET['queue_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid queue ID']);
    exit;
}

$queue_id = (int)$_GET['queue_id'];

try {
    $stmt = $conn->prepare("SELECT status_car FROM queue WHERE queue_id = :queue_id");
    $stmt->bindParam(':queue_id', $queue_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['success' => true, 'status_car' => $result['status_car']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Queue not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>