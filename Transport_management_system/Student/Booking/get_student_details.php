<?php
    require_once 'condb.php';

    // ตรวจสอบว่ามีพารามิเตอร์ stu_id หรือไม่
    if (!isset($_GET['stu_id']) || empty($_GET['stu_id'])) {
        echo json_encode(['error' => 'No student ID provided']);
        exit();
    }

    $stu_id = $_GET['stu_id'];

    // ดึงข้อมูลนักเรียนจากตาราง students
    $sql = "SELECT stu_license, stu_name, stu_lastname, stu_tel, stu_major, stu_faculty, stu_username, stu_img
            FROM students
            WHERE stu_ID = :stu_id";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':stu_id', $stu_id, PDO::PARAM_INT);
        $stmt->execute();
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode(['error' => 'Student not found']);
            exit();
        }

        // ส่งข้อมูลกลับในรูปแบบ JSON
        echo json_encode($student);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
?>