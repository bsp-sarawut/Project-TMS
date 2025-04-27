<!-- sidebar.php -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">


<?php
// ดึงชื่อไฟล์ปัจจุบัน เพื่อใช้ตรวจสอบว่าอยู่ในหน้าไหน
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <button class="close-btn" id="close-btn">×</button>
    <h3 class="text-center text-white mb-3">Dashboard</h3>
    <a href="admin_dashboard.php" class="<?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> หน้าแรก</a>
    <a href="route.php" class="<?php echo $current_page == 'route.php' ? 'active' : ''; ?>"><i class="fas fa-map"></i> จัดการข้อมูลเส้นทาง</a>
    <a href="car.php" class="<?php echo $current_page == 'car.php' ? 'active' : ''; ?>"><i class="fas fa-car"></i> จัดการข้อมูลรถ</a>
    <a href="driver.php" class="<?php echo $current_page == 'driver.php' ? 'active' : ''; ?>"><i class="fas fa-user-tie"></i> จัดการข้อมูลคนขับ</a>
    <a href="student.php" class="<?php echo $current_page == 'student.php' ? 'active' : ''; ?>"><i class="fas fa-user-graduate"></i> จัดการข้อมูลนักศึกษา</a>
    <a href="manage_faculties_majors.php" class="<?php echo $current_page == 'manage_faculties_majors.php' ? 'active' : ''; ?>"><i class="fas fa-university me-2"></i> จัดการคณะและสาขา</a>
    <a href="choice.php" class="<?php echo $current_page == 'choice.php' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> จัดการรูปแบบลงทะเบียน</a>
    <a href="enrollment.php" class="<?php echo $current_page == 'enrollment.php' ? 'active' : ''; ?>"><i class="fas fa-file-alt"></i> จัดการการลงทะเบียน</a>
    <a href="queue.php" class="<?php echo $current_page == 'queue.php' ? 'active' : ''; ?>"><i class="fas fa-bus"></i> จัดคิวรถ</a>
    <a href="show_queue.php" class="<?php echo $current_page == 'show_queue.php' ? 'active' : ''; ?>"><i class="fas fa-table"></i> จัดการคิวรถ</a>
    <a href="queue_log.php" class="<?php echo $current_page == 'queue_log.php' ? 'active' : ''; ?>"><i class="fas fa-road"></i> ข้อมูลการเดินทาง</a>
    <a href="edit_payment_info.php" class="<?php echo $current_page == '' ? 'active' : ''; ?>"><i class="fas fa-money-bill-wave"></i>จัดการรูปแบบชำระเงิน</a>
    <a href="manage_news.php" class="<?php echo $current_page == 'manage_news.php' ? 'active' : ''; ?>"><i class="fas fa-bell"></i> แจ้งข่าวสาร</a>
    <a href="logout.php" class="logout" id="logout"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
</div>

<!-- ปุ่มเปิด Sidebar -->
<button class="open-btn" id="open-btn"><i class="fas fa-bars"></i></button>