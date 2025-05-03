<?php
session_start();
require_once("config/condb.php");

if (isset($_POST['type']) && isset($_POST['value'])) {
    $type = $_POST['type'];
    $value = trim($_POST['value']);

    if ($type === 'username') {
        $stmt = $conn->prepare("SELECT stu_username FROM students WHERE stu_username = :value");
        $stmt->bindParam(':value', $value);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo 'exists';
        } else {
            echo 'available';
        }
    } elseif ($type === 'license') {
        $stmt = $conn->prepare("SELECT stu_license FROM students WHERE stu_license = :value");
        $stmt->bindParam(':value', $value);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo 'exists';
        } else {
            echo 'available';
        }
    }
}
?>