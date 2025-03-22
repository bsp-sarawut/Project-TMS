<?php
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "transport_management_system";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $province_id = $_POST['province_id'];
    $query       = "SELECT * FROM amphur WHERE PROVINCE_ID = :province_id";
    $stmt        = $conn->prepare($query);
    $stmt->bindParam(':province_id', $province_id);
    $stmt->execute();

    $amphurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $options = '<option value=""> กรุณาเลือกอำเภอ </option>';
    foreach ($amphurs as $amphur) {
        $options .= '<option value="' . $amphur['AMPHUR_ID'] . '">' . $amphur['AMPHUR_NAME'] . '</option>';
    }

    echo $options;
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
