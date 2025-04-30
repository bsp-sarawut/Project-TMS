<?php
include 'admin/config/condb.php';

try {
    $stmt = $conn->prepare("SELECT * FROM news ORDER BY news_date DESC");
    $stmt->execute();
    $news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $news_items = [];
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข่าวสารประชาสัมพันธ์ - ระบบจัดการการขนส่ง</title>
    <link rel="icon" type="image/x-icon" href="Logo/favicon.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background: linear-gradient(to bottom, #e0f7fa, #ffffff);
            min-height: 100vh;
            margin: 0;
            position: relative;
            overflow-x: hidden;
        }
        .navbar {
            background-color: rgba(0, 48, 135, 0.9);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s ease;
        }
        .navbar.scrolled {
            background-color: rgba(0, 123, 255, 0.95);
        }
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px; /* ระยะห่างระหว่างโลโก้กับข้อความ */
        }
        .navbar-brand img {
            height: 50px;
            transition: transform 0.3s ease;
        }
        .navbar-brand img:hover {
            transform: scale(1.1);
        }
        .system-title {
            font-size: 1.2rem;
            color: #ffffff;
            font-weight: 400;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        .btn-login {
            background-color: #ffffff;
            color: #003087;
            border: none;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 500;
            margin-left: 10px;
            transition: all 0.3s ease;
            white-space: nowrap; /* ป้องกันข้อความปุ่มขึ้นบรรทัดใหม่ */
        }
        .btn-login:hover {
            background-color: #e0e0e0;
            color: #003087;
            transform: scale(1.05);
        }
        .header-title {
            font-size: 2.5rem;
            color: #2c3e50;
            font-weight: 600;
            text-align: center;
            margin: 40px 0;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
            display: inline-block;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .news-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }
        .news-card {
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: #fff;
            padding: 20px;
            transition: transform 0.2s ease, background-color 0.3s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        .news-card.reveal {
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        .news-card:hover {
            transform: translateY(-5px);
        }
        .news-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .news-card img:hover {
            transform: scale(1.05);
        }
        .news-card h3 {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .news-card p {
            color: #666;
            font-size: 1rem;
            line-height: 1.6;
        }
        .news-date {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .footer {
            background-color: #003087;
            color: #ffffff;
            text-align: center;
            padding: 20px 0;
            position: relative;
            margin-top: 60px;
        }
        .footer a {
            color: #ffffff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .wave {
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            height: 50px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%23003087' fill-opacity='1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
            background-repeat: no-repeat;
        }
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(45deg, #ff6f61, #ffb347);
            color: #fff;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .back-to-top.show {
            opacity: 1;
        }
        .back-to-top:hover {
            transform: scale(1.1);
            background: linear-gradient(45deg, #ffb347, #ff6f61);
        }
        .modal-content {
            border-radius: 15px;
        }
        .modal-body {
            padding: 0;
        }
        .modal-image {
            width: 100%;
            border-radius: 15px;
        }

        /* ปรับแต่งสำหรับหน้าจอขนาดเล็ก */
        @media (max-width: 768px) {
            .navbar-brand {
                flex-direction: row; /* ให้โลโก้และข้อความอยู่ในแถวเดียวกัน */
                align-items: center;
                gap: 5px; /* ลดระยะห่าง */
                flex: 1; /* ให้ .navbar-brand ขยายเต็มที่ */
                max-width: 70%; /* เพิ่มความกว้างเพื่อรองรับข้อความ */
            }
            .navbar-brand img {
                height: 30px; /* ลดขนาดโลโก้ */
            }
            .system-title {
                font-size: 0.65rem; /* ลดขนาดตัวอักษรเพื่อให้ข้อความแสดงครบ */
                line-height: 1.1; /* ปรับระยะห่างระหว่างบรรทัด */
                white-space: normal; /* อนุญาตให้ข้อความขึ้นบรรทัดใหม่ */
            }
            .navbar .container-fluid {
                display: flex;
                align-items: center;
                justify-content: space-between; /* จัดให้โลโก้อยู่ซ้ายและปุ่มอยู่ขวา */
                padding: 5px 10px; /* ลด padding เพื่อเพิ่มพื้นที่ */
            }
            .ms-auto.d-flex {
                flex: 0 0 auto; /* ให้ปุ่มไม่ขยาย */
                gap: 3px; /* ลดระยะห่างระหว่างปุ่ม */
            }
            .btn-login {
                padding: 4px 8px; /* ลดขนาดปุ่ม */
                font-size: 0.7rem; /* ลดขนาดตัวอักษร */
                margin-left: 3px;
            }
            .header-title {
                font-size: 1.8rem;
                margin: 30px 0;
            }
            .news-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            .news-card {
                padding: 20px;
                border-radius: 12px;
            }
            .news-card img {
                height: 180px;
            }
            .news-card h3 {
                font-size: 1.4rem;
            }
            .news-card p {
                font-size: 1rem;
                line-height: 1.7;
            }
            .news-date {
                font-size: 0.9rem;
            }
            .footer {
                padding: 25px 0;
                margin-top: 50px;
                font-size: 0.95rem;
            }
            .wave {
                top: -40px;
                height: 40px;
            }
            .back-to-top {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
                font-size: 1.5rem;
                background: linear-gradient(45deg, #ff4d4d, #ff8c1a);
            }
        }

        /* ปรับแต่งเพิ่มเติมสำหรับหน้าจอขนาดเล็กมาก (เช่น มือถือ < 576px) */
        @media (max-width: 576px) {
            .navbar-brand {
                max-width: 65%; /* ลดความกว้างเพิ่มเพื่อให้มีที่ว่างสำหรับปุ่ม */
            }
            .navbar-brand img {
                height: 25px; /* ลดขนาดโลโก้เพิ่ม */
            }
            .system-title {
                font-size: 0.55rem; /* ลดขนาดตัวอักษรเพิ่ม */
                line-height: 1.1;
            }
            .btn-login {
                padding: 3px 6px; /* ลดขนาดปุ่มเพิ่ม */
                font-size: 0.65rem; /* ลดขนาดตัวอักษรเพิ่ม */
                margin-left: 2px;
            }
            .header-title {
                font-size: 1.5rem;
                margin: 25px 0;
            }
            .news-card {
                padding: 15px;
            }
            .news-card img {
                height: 160px;
            }
            .news-card h3 {
                font-size: 1.3rem;
            }
            .news-card p {
                font-size: 0.95rem;
                line-height: 1.6;
            }
            .news-date {
                font-size: 0.85rem;
            }
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }
            .footer {
                padding: 20px 0;
                margin-top: 40px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="Logo/logo_index.png" alt="โลโก้ระบบจัดการการขนส่ง">
                <span class="system-title">ระบบบริหารจัดการรถตู้รับส่งนักศึกษามหาวิทยาลัยธนบุรี</span>
            </a>
            
            <div class="ms-auto d-flex">
                <a href="Student/index.php" class="btn btn-login"><i class="fas fa-user-graduate me-1"></i>นักเรียน</a>
                <a href="driver/driver_signin.php" class="btn btn-login"><i class="fas fa-car me-1"></i>คนขับรถ</a>
            </div>
        </div>
    </nav>

    <!-- เนื้อหาข่าวสาร -->
    <div class="container">
        <h1 class="header-title" id="headerTitle"></h1>

        <!-- การ์ดข่าวสาร -->
        <?php if (count($news_items) > 0): ?>
            <div class="news-container">
                <?php foreach ($news_items as $news): ?>
                    <div class="news-card">
                        <img src="admin/uploads/news/<?php echo htmlspecialchars($news['news_image'] ?? 'default_news.jpg'); ?>" 
                             alt="ภาพข่าวสาร" 
                             data-bs-toggle="modal" 
                             data-bs-target="#imageModal"
                             data-image="admin/uploads/news/<?php echo htmlspecialchars($news['news_image'] ?? 'default_news.jpg'); ?>">
                        <div class="news-date"><?php echo date('d F Y', strtotime($news['news_date'])); ?></div>
                        <h3><?php echo htmlspecialchars($news['news_title']); ?></h3>
                        <p><?php echo nl2br(htmlspecialchars($news['news_content'])); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted">
                <p>ไม่มีข่าวสารในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="wave"></div>
        <p>© 2568 ระบบจัดการการขนส่ง | ติดต่อ: <br> <a href="mailto:support@tms.ac.th">bsp.sarawut@gmail.com & kitti@gmail.com</a></p>
    </footer>

    <!-- Modal สำหรับขยายรูปภาพ -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <img id="modalImage" class="modal-image" src="" alt="ภาพขยาย">
                </div>
            </div>
        </div>
    </div>

    <!-- ปุ่มกลับไปด้านบน -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันสร้างสีแบบสุ่ม
        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color + '20';
        }

        // เพิ่มสีพื้นหลังแบบสุ่มเมื่อ Hover News Card
        document.querySelectorAll('.news-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.backgroundColor = getRandomColor();
            });
            card.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '#fff';
            });
        });

        // อนิเมชัน Fade-in เมื่อเลื่อนไปถึง News Card
        function revealOnScroll() {
            const cards = document.querySelectorAll('.news-card');
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                if (cardTop < windowHeight - 100) {
                    card.classList.add('reveal');
                }
            });
        }

        window.addEventListener('scroll', revealOnScroll);
        window.addEventListener('load', revealOnScroll);

        // เปลี่ยนสี Navbar เมื่อเลื่อน
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // อนิเมชันพิมพ์ข้อความสำหรับ Header Title
        const headerTitle = document.getElementById('headerTitle');
        const text = "ข่าวสารประชาสัมพันธ์";
        let index = 0;

        function typeText() {
            if (index < text.length) {
                headerTitle.textContent += text.charAt(index);
                index++;
                setTimeout(typeText, 100);
            }
        }

        window.addEventListener('load', () => {
            headerTitle.textContent = '';
            typeText();
        });

        // แสดง/ซ่อนปุ่ม Back to Top
        const backToTopButton = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopButton.classList.add('show');
            } else {
                backToTopButton.classList.remove('show');
            }
        });

        // ฟังก์ชันเลื่อนกลับไปด้านบน
        backToTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ตั้งค่า Modal รูปภาพเมื่อกด
        document.querySelectorAll('.news-card img').forEach(img => {
            img.addEventListener('click', function() {
                const imageSrc = this.getAttribute('data-image');
                document.getElementById('modalImage').src = imageSrc;
            });
        });
    </script>
</body>
</html>