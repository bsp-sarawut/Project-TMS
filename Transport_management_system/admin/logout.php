<?php
session_start();
session_unset();  // ลบข้อมูลในเซสชัน
session_destroy(); // ทำลายเซสชัน
header("Location: index.php");  // รีไดเรกต์ไปที่หน้าล็อกอิน
exit(); // หยุดการทำงานของสคริปต์
?>
