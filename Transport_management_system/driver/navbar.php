<style>
    .navbar {
        background-color: #003087;
        font-family: 'IBM Plex Sans Thai', sans-serif;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .navbar-dark .navbar-brand {
        font-size: 1.5rem;
        font-weight: 600;
        color: #ffffff !important; /* เพิ่ม !important เพื่อให้แน่ใจว่าไม่ถูก override */
    }
    .navbar-dark .navbar-brand:hover {
        color: #e0e0e0 !important;
    }
    .navbar-dark .nav-link {
        color: #ffffff !important; /* เพิ่ม !important */
        font-size: 1rem;
        font-weight: 500;
        margin-left: 15px;
    }
    .navbar-dark .nav-link:hover {
        color: #e0e0e0 !important;
    }
    .navbar-dark .dropdown-menu {
        background-color: #003087; /* เปลี่ยนพื้นหลังเป็นสีน้ำเงินเข้มให้เข้ากับ navbar */
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #ffffff;
    }
    .navbar-dark .dropdown-item {
        color: #ffffff !important; /* ตัวอักษรสีขาวใน dropdown */
        font-size: 1rem;
    }
    .navbar-dark .dropdown-item:hover {
        background-color: #ffffff;
        color: #003087 !important;
    }
    .navbar-dark .dropdown-divider {
        border-top: 1px solid #ffffff;
    }
    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 1.2rem;
        }
        .nav-link {
            font-size: 0.9rem;
            margin-left: 10px;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="driver.php">ระบบจัดการการขนส่ง</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="driver_queue.php">ดูคิวงาน</a>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="update_status.php">อัปเดตสถานะรถ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="student_list.php">ข้อมูลนักศึกษา</a>
                </li> -->
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo isset($_SESSION['driver_user']) ? htmlspecialchars($_SESSION['driver_user']) : 'คนขับ'; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <!-- <li><a class="dropdown-item" href="driver_profile.php">ตั้งค่าโปรไฟล์</a></li>
                        <li><hr class="dropdown-divider"></li> -->
                        <li><a class="dropdown-item" href="driver_logout.php">ออกจากระบบ</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// ดีบัก: ตรวจสอบว่า Bootstrap Dropdown ทำงาน
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElement = document.getElementById('navbarDropdown');
    if (!dropdownElement) {
        console.error('Dropdown element not found');
    } else {
        dropdownElement.addEventListener('click', function() {
            console.log('Dropdown clicked');
        });
    }
});
</script>