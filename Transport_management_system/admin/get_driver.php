<?php
include('config/condb.php');

if (isset($_GET['driver_id'])) {
    $driver_id = $_GET['driver_id'];
    $stmt = $conn->prepare("SELECT CONCAT(driver_name, ' ', driver_lastname) AS driver_fullname FROM driver WHERE driver_id = :driver_id");
    $stmt->bindParam(':driver_id', $driver_id);
    $stmt->execute();
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($driver ?: []);
}
?>