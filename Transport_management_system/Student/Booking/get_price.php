<?php
// เชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "transport_management_system";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (isset($_POST['location_id'])) {
    $location_id = $_POST['location_id'];

    $query_price = "SELECT price FROM routes WHERE location = :location_id"; // ใช้ 'location' แทน 'location_id'
    $stmt        = $conn->prepare($query_price);
    $stmt->bindParam(':location_id', $location_id);
    $stmt->execute();
    $price = $stmt->fetchColumn();

    echo $price; // ส่งกลับราคา
}
