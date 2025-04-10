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
            background: #1c2526;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        nav.scrolled {
            transform: translateY(-100%);
        }
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            max-width: 1300px;
            margin: 0 auto;
        }
        .navbar-brand {
            color: #ffca28 !important; /* ใช้ !important เพื่อให้แน่ใจว่าสีแสดงผล */
            font-size: 1.5rem;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .navbar-brand:hover {
            color: #ff8f00 !important;
        }
        .navbar-links {
            display: flex;
            align-items: center;
        }
        .navbar-links a {
            color: #eceff1;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .navbar-links a:hover {
            color: #ffca28;
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
            gap: 10px;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .dropdown-button:hover {
            color: #ffca28;
        }
        .dropdown-button img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid #ffca28;
            object-fit: cover;
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
            background: #263238 !important; /* ใช้ !important เพื่อให้แน่ใจว่าสีพื้นหลังแสดงผล */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
            border-radius: 8px;
            min-width: 200px;
            z-index: 1000;
            overflow: hidden;
        }
        .dropdown.show .dropdown-menu {
            display: block;
        }
        .menu-item {
            display: block;
            padding: 12px 20px;
            color: #eceff1 !important; /* ใช้ !important เพื่อให้แน่ใจว่าสีตัวอักษรแสดงผล */
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: background 0.3s ease, color 0.3s ease;
        }
        .menu-item:hover {
            background: #ffca28 !important;
            color: #1c2526 !important;
        }
        .text-danger {
            color: #ef5350 !important;
        }
        .text-danger:hover {
            background: #ef5350 !important;
            color: #fff !important;
        }
        @media (max-width: 768px) {
            .navbar-container {
                padding: 10px 20px;
            }
            .navbar-brand {
                font-size: 1.3rem;
            }
            .dropdown-button {
                font-size: 1rem;
            }
            .dropdown-button img {
                width: 35px;
                height: 35px;
            }
            .dropdown-menu {
                min-width: 180px;
            }
            .menu-item {
                font-size: 0.9rem;
                padding: 10px 15px;
            }
        }
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            .dropdown-button {
                font-size: 0.9rem;
            }
            .dropdown-button img {
                width: 30px;
                height: 30px;
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
    $user_image = !empty($_SESSION['stu_img']) ? "../" . htmlspecialchars($_SESSION['stu_img']) : "../images/default-avatar.png";
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

        // ปิด dropdown เมื่อคลิกนอกเมนู
        document.addEventListener("click", function (event) {
            if (!dropdown.contains(event.target) && !dropdownButton.contains(event.target)) {
                dropdown.classList.remove("show");
            }
        });
    </script>
</body>
</html>