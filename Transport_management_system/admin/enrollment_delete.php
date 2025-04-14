<?php
require_once 'config/condb.php';

header('Content-Type: application/json');

if (isset($_POST['delete']) && isset($_POST['id'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM transport_registration WHERE id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'การลงทะเบียนถูกลบแล้ว!'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ไม่สามารถลบข้อมูลได้: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบข้อมูลที่ต้องการลบ'
    ]);
}
exit();
?>