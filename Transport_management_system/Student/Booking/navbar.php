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
    <title>Modern Navbar with Dropdown</title>
    <!-- Font Awesome CDN สำหรับไอคอน -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Chakra Petch', sans-serif;
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
        }
        nav {
            background: linear-gradient(90deg, #1c2526 0%, #263238 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.4s ease;
        }
        nav.scrolled {
            transform: translateY(-100%);
        }
        nav:hover {
            transform: translateY(0); /* แสดง Navbar เมื่อ hover แม้ว่าจะเลื่อนลง */
        }
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            max-width: 1400px;
            margin: 0 auto;
        }
        .navbar-brand {
            color: #ffca28 !important;
            font-size: 1.8rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .navbar-brand:hover {
            color: #ffd54f !important;
            transform: scale(1.05);
        }
        .navbar-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .navbar-links a {
            color: #eceff1;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .navbar-links a:hover {
            color: #1c2526;
            background: #ffca28;
        }
        .dropdown {
            position: relative;
        }
        .dropdown-button {
            background: none;
            border: none;
            color: #eceff1;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .dropdown-button:hover {
            color: #1c2526;
            background: #ffca28;
        }
        .dropdown-button img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #ffca28;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .dropdown-button:hover img {
            transform: scale(1.1);
        }
        .dropdown-button i {
            font-size: 1rem;
            transition: transform 0.3s ease;
        }
        .dropdown.show .dropdown-button i {
            transform: rotate(180deg);
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: #263238 !important;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            min-width: 220px;
            z-index: 1000;
            overflow: hidden;
            transform: translateY(10px);
            opacity: 0;
            transition: all 0.3s ease;
        }
        .dropdown.show .dropdown-menu {
            display: block;
            transform: translateY(0);
            opacity: 1;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #eceff1 !important;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .menu-item i {
            font-size: 1.1rem;
            color: #ffca28;
        }
        .menu-item:hover {
            background: #ffca28 !important;
            color: #1c2526 !important;
        }
        .menu-item:hover i {
            color: #1c2526;
        }
        .text-danger {
            color: #ef5350 !important;
        }
        .text-danger i {
            color: #ef5350;
        }
        .text-danger:hover {
            background: #ef5350 !important;
            color: #fff !important;
        }
        .text-danger:hover i {
            color: #fff;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                padding: 10px 20px;
            }
            .navbar-brand {
                font-size: 1.5rem;
            }
            .navbar-links {
                gap: 10px;
            }
            .navbar-links a {
                font-size: 1rem;
                padding: 6px 10px;
            }
            .dropdown-button {
                font-size: 1rem;
                padding: 6px 10px;
                gap: 8px;
            }
            .dropdown-button img {
                width: 35px;
                height: 35px;
            }
            .dropdown-menu {
                min-width: 200px;
            }
            .menu-item {
                font-size: 0.9rem;
                padding: 10px 15px;
            }
            .menu-item i {
                font-size: 1rem;
            }
        }
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
            .navbar-links a {
                font-size: 0.9rem;
                padding: 5px 8px;
            }
            .dropdown-button {
                font-size: 0.9rem;
                gap: 6px;
            }
            .dropdown-button img {
                width: 30px;
                height: 30px;
            }
            .dropdown-menu {
                min-width: 180px;
            }
            .menu-item {
                font-size: 0.85rem;
                padding: 8px 12px;
            }
            .menu-item i {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="navbar-container">
            <a class="navbar-brand" href="stu_booking.php">ระบบจัดการ</a>

            <div class="navbar-links">
                <?php if (isset($_SESSION['stu_name'])): ?>
<?php
    $user_image = !empty($_SESSION['stu_img']) ? "../uploads/" . htmlspecialchars($_SESSION['stu_img']) : "../images/default-avatar.png";
?>
                    <div class="dropdown">
                        <button class="dropdown-button">
                            <img src="<?php echo $user_image?>" alt="User Image">
                            <span><?php echo htmlspecialchars($_SESSION['stu_name'])?></span>
                            <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <!-- Dropdown Items -->
                            <a href="stu_booking.php" class="menu-item">
                                <i class="fas fa-home"></i> หน้าแรก
                            </a>
                            <a href="enrollment.php" class="menu-item">
                                <i class="fas fa-bus"></i> ลงทะเบียนขึ้นรถรับส่ง
                            </a>
                            <a href="Show_enrollment.php" class="menu-item">
                                <i class="fas fa-check-circle"></i> ตรวจสอบสถานะการลงทะเบียน
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

    <script>
        // JavaScript สำหรับ toggle dropdown เมื่อคลิกปุ่ม
        const dropdownButton = document.querySelector('.dropdown-button');
        const dropdown = document.querySelector('.dropdown');

        dropdownButton.addEventListener('click', function (event) {
            event.stopPropagation(); // ป้องกันการปิด dropdown เมื่อคลิกที่ปุ่ม
            dropdown.classList.toggle('show'); // เปิด/ปิด dropdown
        });

        // JavaScript ซ่อน/แสดง Navbar เมื่อเลื่อน
        let lastScrollTop = 0;
        const navbar = document.querySelector('nav');

        window.addEventListener('scroll', function () {
            let currentScroll = window.pageYOffset || document.documentElement.scrollTop;
            if (currentScroll > lastScrollTop && currentScroll > 100) {
                navbar.classList.add('scrolled'); // ซ่อน Navbar เมื่อเลื่อนลง
            } else {
                navbar.classList.remove('scrolled'); // แสดง Navbar เมื่อเลื่อนขึ้น
            }
            lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
        });

        // ปิด dropdown เมื่อคลิกนอกเมนู
        document.addEventListener("click", function (event) {
            if (!dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });

        // เพิ่มการแสดง Navbar เมื่อเลื่อนไปด้านบนสุดของหน้า
        window.addEventListener('scroll', function () {
            if (window.pageYOffset === 0) {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>