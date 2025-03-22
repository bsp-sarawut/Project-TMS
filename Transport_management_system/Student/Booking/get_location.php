<?php
session_start();

if (isset($_POST['province_id']) && isset($_POST['amphur_id'])) {
    $province_id = $_POST['province_id'];
    $amphur_id   = $_POST['amphur_id'];

    // เชื่อมต่อฐานข้อมูล
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "transport_management_system";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ดึงข้อมูล location และ route_image ที่ตรงกับจังหวัดและอำเภอจากตาราง routes
        $query = "SELECT location, route_image FROM routes WHERE province = :province_id AND amphur = :amphur_id";
        $stmt  = $conn->prepare($query);
        $stmt->bindParam(':province_id', $province_id);
        $stmt->bindParam(':amphur_id', $amphur_id);
        $stmt->execute();

        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($locations) > 0) {
            foreach ($locations as $location) {
                $image_path = $location['route_image'];

                // ถ้ายังไม่มี admin/uploads/ ให้เติม
                if (! empty($image_path) && strpos($image_path, 'admin/uploads/') === false) {
                    $image_path = ' ../../admin/' . $image_path;
                }

                echo "<option value='" . htmlspecialchars($location['location'], ENT_QUOTES, 'UTF-8') . "'
                            data-image='" . htmlspecialchars($image_path, ENT_QUOTES, 'UTF-8') . "'>
                            " . htmlspecialchars($location['location'], ENT_QUOTES, 'UTF-8') . "
                      </option>";
            }
        }

    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
