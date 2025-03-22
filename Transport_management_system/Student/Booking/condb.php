<?php
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

// ดึงข้อมูลจากตาราง province และ amphur
$query_province  = "SELECT * FROM province";
$result_province = $conn->query($query_province);

$query_amphur  = "SELECT * FROM amphur";
$result_amphur = $conn->query($query_amphur);

$query_routes  = "SELECT * FROM routes";
$result_routes = $conn->query($query_routes);

$query_schedule  = "SELECT * FROM transport_schedule";
$result_schedule = $conn->query($query_schedule);
