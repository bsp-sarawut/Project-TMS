<?php
    session_start(); // เริ่ม session

    // ตรวจสอบว่ามีการกดปุ่มออกจากระบบหรือไม่
    if (isset($_GET['logout'])) {
        session_destroy();                // ล้าง session ทั้งหมด
        header("Location: ../index.php"); // กลับไปหน้า login
        exit();
    }
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Minimalist Navbar with Dropdown</title>

    <!-- Font Awesome CDN สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style_navbar.css">
</head>
<body>

    <nav>
        <div class="navbar-container">
            <a class="navbar-brand" href="stu_booking.php">ระบบจัดการ</a>

            <div class="navbar-links">
                <?php if (isset($_SESSION['stu_name'])): ?>
<?php
    $user_image = ! empty($_SESSION['stu_img']) ? "../" . htmlspecialchars($_SESSION['stu_img']) : "../images/default-avatar.png";
?>
                    <div class="dropdown">
                        <button class="dropdown-button">
                            <img src="<?php echo $user_image?>" alt="User Image">
                            <?php echo htmlspecialchars($_SESSION['stu_name'])?>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <!-- Dropdown Items -->
                            <a href="stu_booking.php" class="menu-item">หน้าแรก</a>
                            <a href="enrollment.php" class="menu-item">ลงทะเบียนขึ้นรถรับส่ง</a>
                            <a href="Show_enrollment.php" class="menu-item">ตรวจสอบสถานะการลงทะเบียน</a>
                            <a href="#" class="menu-item">ตรวจสอบตารางรถ</a>
                            <a href="#" class="menu-item">ตั้งค่า</a>
                            <a href="?logout=true" class="menu-item text-danger">ออกจากระบบ</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="../index.php">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <script>
        // JavaScript สำหรับ toggle dropdown เมื่อคลิกปุ่ม
        const dropdownButton = document.querySelector('.dropdown-button');
        const dropdown = document.querySelector('.dropdown');

        dropdownButton.addEventListener('click', function () {
            dropdown.classList.toggle('show'); // เปิด/ปิด dropdown
        });

        // JavaScript ซ่อน Navbar เมื่อเลื่อนลง
        let lastScrollTop = 0;
        let navbar = document.querySelector('nav');

        window.addEventListener('scroll', function () {
            let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > lastScrollTop) {
                navbar.classList.add('scrolled'); // เพิ่มคลาส scrolled เมื่อเลื่อนลง
            } else {
                navbar.classList.remove('scrolled'); // ลบคลาส scrolled เมื่อเลื่อนขึ้น
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });
        document.addEventListener("click", function (event) {
    if (!dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
        dropdown.classList.remove("show"); // ปิด dropdown ถ้าคลิกนอกเมนู
    }
});

    </script>

</body>

</html>
