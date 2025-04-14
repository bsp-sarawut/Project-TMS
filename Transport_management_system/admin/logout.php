<?php
// logout.php
session_start();

// ล้างเซสชันทั้งหมด
$_SESSION = [];
session_destroy();

// เปลี่ยนเส้นทางไปยังหน้า login (สมมติว่าไฟล์ login คือ login.php)
header("Location: index.php");
exit();
?>