<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตารางรถ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #212121;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            position: relative;
        }

        /* พื้นหลังอนิเมชันแบบคลื่นนุ่มนวล */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(66, 165, 245, 0.05), rgba(33, 33, 33, 0.1));
            animation: subtleWave 10s infinite ease-in-out;
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: #2d2d2d;
            color: #ffffff;
            position: fixed;
            height: 100%;
            top: 0;
            left: 0;
            padding: 20px;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .sidebar-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ffffff;
        }

        .close-btn {
            background: #424242;
            border: none;
            color: #b0b0b0;
            font-size: 20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: #42a5f5;
            color: #ffffff;
            transform: rotate(180deg);
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 15px;
            color: #b0b0b0;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 5px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }

        .sidebar-nav a i {
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .sidebar-nav a:hover {
            background: #42a5f5;
            color: #ffffff;
            transform: translateX(5px);
        }

        .sidebar-nav a.active {
            background: #42a5f5;
            color: #ffffff;
            font-weight: 600;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 20px;
            width: calc(100% - 40px);
        }

        .sidebar-footer .logout {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            background: #42a5f5;
            color: #ffffff;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .sidebar-footer .logout:hover {
            background: #2196f3;
            transform: scale(1.05);
        }

        /* Content */
        .content {
            margin-left: 280px;
            padding: 20px;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            transition: margin-left 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .open-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #42a5f5;
            border: none;
            color: #ffffff;
            font-size: 20px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(66, 165, 245, 0.4);
        }

        .open-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 3px 15px rgba(66, 165, 245, 0.6);
        }

        .sidebar.closed {
            transform: translateX(-280px);
        }

        .content.closed {
            margin-left: 0;
        }

        .sidebar.closed ~ .open-btn {
            display: flex;
        }

        /* Table Styling */
        .table-container {
            background: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-in-out;
        }

        h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212121;
            margin-bottom: 10px;
        }

        .dropdown-filter {
            margin-bottom: 20px;
        }

        .dropdown-toggle {
            background: #424242;
            border: none;
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 5px;
            color: #ffffff;
            transition: all 0.3s ease;
        }

        .dropdown-toggle:hover {
            background: #42a5f5;
        }

        .dropdown-menu {
            font-size: 0.9rem;
            border-radius: 5px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            border: 1px solid #e0e0e0;
        }

        .dropdown-item {
            padding: 8px 15px;
            color: #424242;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background: #42a5f5;
            color: #ffffff;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .table th {
            background: #f9f9f9;
            color: #424242;
            font-weight: 600;
        }

        .table td {
            color: #212121;
        }

        .status-vacant {
            color: #42a5f5;
            font-weight: 500;
        }

        .status-finished {
            color: #ffca28;
            font-weight: 500;
        }

        .status-onboard {
            color: #66bb6a;
            font-weight: 500;
        }

        .table tr:hover {
            background: #f5f5f5;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            color: #42a5f5;
            padding: 8px 12px;
            text-decoration: none;
            border: 1px solid #e0e0e0;
            margin: 0 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #42a5f5;
            color: #ffffff;
            border-color: #42a5f5;
        }

        .pagination .active {
            background: #42a5f5;
            color: #ffffff;
            border-color: #42a5f5;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes subtleWave {
            0% { transform: translateY(0); opacity: 0.8; }
            50% { transform: translateY(-10px); opacity: 1; }
            100% { transform: translateY(0); opacity: 0.8; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>Dashboard</h3>
        <button class="close-btn" id="close-btn"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-nav">
        <a href="#"><i class="fas fa-home"></i> หน้าแรก</a>
        <a href="#"><i class="fas fa-map-marked-alt"></i> จัดการข้อมูลเส้นทาง</a>
        <a href="#"><i class="fas fa-car"></i> จัดการข้อมูลรถ</a>
        <a href="#"><i class="fas fa-user-tie"></i> จัดการข้อมูลคนขับ</a>
        <a href="#"><i class="fas fa-calendar-check"></i> จัดตารางลงทะเบียน</a>
        <a href="#"><i class="fas fa-clipboard-list"></i> ข้อมูลการลงทะเบียน</a>
        <a href="#"><i class="fas fa-bus-alt"></i> จัดตารางรถ</a>
        <a href="#" class="active"><i class="fas fa-table"></i> ตารางรถ</a>
        <a href="#"><i class="fas fa-road"></i> ข้อมูลการเดินทาง</a>
        <a href="#"><i class="fas fa-bullhorn"></i> แจ้งข่าวสาร</a>
    </nav>
    <div class="sidebar-footer">
        <a href="#" class="logout" id="logout"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
    </div>
</div>

<!-- Content -->
<div class="content" id="content">
    <button class="open-btn" id="open-btn"><i class="fas fa-bars"></i></button>
    <div class="table-container">
        <h2>ตารางรถ (วันที่ 20 มี.ค. - 31 มี.ค.)</h2>

        <!-- Dropdown สถานะรถตู้ -->
        <div class="dropdown-filter">
            <div class="dropdown">
                <button class="btn dropdown-toggle" type="button" id="vanStatusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    สถานะรถตู้: ทั้งหมด
                </button>
                <ul class="dropdown-menu" aria-labelledby="vanStatusDropdown">
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ทั้งหมด</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ว่าง</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: กำลังไป</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ถึงจุดรับ</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ออกเดินทาง</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ถึงที่หมาย</a></li>
                    <li><a class="dropdown-item" href="#">สถานะรถตู้: ฉุกเฉิน</a></li>
                </ul>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>ลำดับที่</th>
                    <th>ชื่อ</th>
                    <th>เบอร์โทร</th>
                    <th>คณะ</th>
                    <th>สถานะ</th>
                    <th>เพิ่มเติม</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>สมชาย ใจดี</td>
                    <td>087625282</td>
                    <td>วิศวกรรมศาสตร์</td>
                    <td class="status-vacant">ว่าง</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>สมหญิง รักเรียน</td>
                    <td>086152728</td>
                    <td>วิทยาศาสตร์</td>
                    <td class="status-onboard">ขึ้นรถแล้ว</td>
                    <td>รายละเอียด</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>สมศักดิ์ อดทน</td>
                    <td>08xxxxxx</td>
                    <td>บริหารธุรกิจ</td>
                    <td class="status-finished">เลิกเรียนแล้ว</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>สมบูรณ์ เก่ง</td>
                    <td>09xxxxxx</td>
                    <td>เกษตรศาสตร์</td>
                    <td class="status-vacant">ว่าง</td>
                    <td>รายละเอียด</td>
                </tr>
            </tbody>
        </table>
        <div class="pagination">
            <a href="#" class="previous">«</a>
            <a href="#" class="active">1</a>
            <a href="#">2</a>
            <a href="#">3</a>
            <a href="#" class="next">»</a>
        </div>
        <p class="text-end text-muted mt-3">จำนวนทั้งหมด: 06 รายการ</p>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script>
    // Sidebar Toggle
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    const closeBtn = document.getElementById('close-btn');
    const openBtn = document.getElementById('open-btn');

    closeBtn.addEventListener('click', () => {
        sidebar.classList.add('closed');
        content.classList.add('closed');
    });

    openBtn.addEventListener('click', () => {
        sidebar.classList.remove('closed');
        content.classList.remove('closed');
    });

    // Table Row Hover Animation
    const rows = document.querySelectorAll('.table tr');
    rows.forEach(row => {
        row.addEventListener('mouseover', () => {
            row.style.transform = 'scale(1.01)';
            row.style.transition = 'transform 0.3s ease';
        });
        row.addEventListener('mouseout', () => {
            row.style.transform = 'scale(1)';
        });
    });
</script>

</body>
</html>