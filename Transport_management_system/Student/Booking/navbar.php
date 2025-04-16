<?php
    // ตรวจสอบว่าเซสชันยังไม่เริ่มต้น
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ตรวจสอบว่ามีการกดปุ่มออกจากระบบหรือไม่
    if (isset($_GET['logout'])) {
        session_destroy(); // ล้าง session ทั้งหมด
        header("Location: ../index.php"); // กลับไปหน้า login
        exit();
    }

    // กำหนดค่า user_image
    $user_image = !empty($_SESSION['stu_img']) ? "../Uploads/" . htmlspecialchars($_SESSION['stu_img']) : "../images/default-avatar.png";
?>

<!-- ส่วนของ Navigation Bar -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style_navbar.css?v=1.6">

<nav class="navbar">
    <div class="navbar-container">
        <a class="navbar-brand" href="stu_booking.php">ระบบจัดการ</a>
        <div class="navbar-links">
            <?php if (isset($_SESSION['stu_name'])): ?>
                <div class="dropdown">
                    <button class="dropdown-button">
                        <img src="<?php echo $user_image; ?>" alt="User Image">
                        <span><?php echo htmlspecialchars($_SESSION['stu_name']); ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="stu_booking.php" class="menu-item">
                            <i class="fas fa-user"></i> หน้าแรก
                        </a>
                        <a href="enrollment.php" class="menu-item">
                            <i class="fas fa-tags"></i> ลงทะเบียนขึ้นรถรับส่ง
                        </a>
                        <a href="Show_enrollment.php" class="menu-item">
                            <i class="fas fa-lock"></i> ตรวจสอบสถานะการลงทะเบียน
                        </a>
                        <a href="Check_schedule.php" class="menu-item">
                            <i class="fas fa-calendar-alt"></i> ตรวจสอบตารางรถ
                        </a>
                        <a href="setting.php" class="menu-item">
                            <i class="fas fa-cog"></i> ตั้งค่า
                        </a>
                        <a href="?logout=true" class="menu-item text-danger">
                            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="../index.php"><i class="fas fa-sign-in-alt"></i> เข้าสู่ระบบ</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- JavaScript สำหรับ Navbar -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log("Navbar script loaded");

        const dropdownButton = document.querySelector('.dropdown-button');
        const dropdown = document.querySelector('.dropdown');
        const dropdownMenu = document.querySelector('.dropdown-menu');
        const navbar = document.querySelector('.navbar');
        let lastScrollTop = 0;

        // ตรวจสอบว่าเจอ element หรือไม่
        if (!dropdownButton || !dropdown || !dropdownMenu) {
            console.error("Dropdown elements not found!");
            return;
        }

        // การทำงานของ Dropdown
        dropdownButton.addEventListener('click', function (event) {
            event.stopPropagation();
            console.log("Dropdown button clicked");
            dropdown.classList.toggle('show');
            // ตรวจสอบสถานะหลัง toggle
            console.log("Dropdown has class 'show':", dropdown.classList.contains('show'));
            console.log("Dropdown menu styles:", {
                display: window.getComputedStyle(dropdownMenu).display,
                opacity: window.getComputedStyle(dropdownMenu).opacity,
                visibility: window.getComputedStyle(dropdownMenu).visibility
            });
        });

        document.addEventListener("click", function (event) {
            if (!dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
                console.log("Clicked outside dropdown");
                dropdown.classList.remove("show");
            }
        });

        // อนิเมชันเมื่อ scroll
        window.addEventListener('scroll', function () {
            let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;

            if (window.pageYOffset === 0) {
                navbar.classList.remove('scrolled');
            }
        });
    });
</script>