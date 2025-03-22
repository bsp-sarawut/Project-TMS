<?php
include('config/condb.php');

if (isset($_GET['province_id']) && !empty($_GET['province_id'])) {
    $province_id = $_GET['province_id'];
    
    try {
        $stmt = $conn->prepare("SELECT AMPHUR_ID, AMPHUR_NAME FROM amphur WHERE PROVINCE_ID = :province_id");
        $stmt->bindParam(':province_id', $province_id);
        $stmt->execute();
        $amphurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($amphurs); // ส่งข้อมูลอำเภอเป็น JSON
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}
?>
