<?php
require_once 'config/condb.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['payment_status'])) {
    $id = $_POST['id'];
    $new_status = $_POST['payment_status'];

    $valid_statuses = ['Paid', 'Pending Confirmation', 'Upload again!'];
    if (in_array($new_status, $valid_statuses)) {
        try {
            $sql = "UPDATE transport_registration SET payment_status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$new_status, $id]);

            echo json_encode([
                'status' => 'success',
                'message' => 'สถานะการชำระเงินอัปเดตเรียบร้อยแล้ว'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'ไม่สามารถอัปเดตสถานะได้: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'สถานะที่เลือกไม่ถูกต้อง'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'คำขอไม่ถูกต้อง'
    ]);
}
exit();
?>