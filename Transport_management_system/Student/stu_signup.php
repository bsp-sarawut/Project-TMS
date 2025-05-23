<?php
session_start();
require_once 'config/condb.php';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน - นักศึกษา</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1c2526 0%, #2c3e50 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            position: relative;
        }

        .signup-container {
            max-width: 500px;
            width: 100%;
            background: #263238;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .signup-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .logo {
            display: block;
            margin: 0 auto 15px;
            width: 80px;
            height: 80px;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #eceff1;
            text-align: center;
            margin-bottom: 20px;
        }

        hr {
            border: 0;
            height: 1px;
            background: #b0bec5;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #b0bec5;
            font-size: 1rem;
        }

        .form-control, .form-select {
            border-radius: 15px !important;
            padding: 10px;
            border: 1px solid #b0bec5;
            background: #2c3e50;
            color: #eceff1;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #ffca28 !important;
            box-shadow: 0 0 6px rgba(255, 202, 40, 0.4);
            background: #2c3e50;
            color: #eceff1;
        }

        .form-control::placeholder, .form-select option {
            color: #b0bec5;
        }

        .btn-primary {
            background: linear-gradient(45deg, #ffca28, #ff8f00);
            border: none;
            padding: 10px;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.3s ease;
            color: #1c2526;
            font-size: 0.9rem;
            width: 100%;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #ff8f00, #ffca28);
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(255, 202, 40, 0.4);
        }

        .alert {
            border-radius: 15px;
            font-size: 0.9rem;
            padding: 10px;
            margin-bottom: 20px;
            background: #2c3e50;
            border: 1px solid #b0bec5;
            color: #eceff1;
            text-align: center;
        }

        .alert a {
            color: #ffca28;
            text-decoration: none;
        }

        .alert a:hover {
            color: #ff8f00;
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #b0bec5;
        }

        .footer a {
            color: #ffca28;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: #ff8f00;
            text-decoration: underline;
        }

        .modal-content {
            background: #263238;
            color: #eceff1;
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #b0bec5;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff6f61;
        }

        .modal-body {
            font-size: 0.9rem;
        }

        .modal-footer {
            border-top: 1px solid #b0bec5;
        }

        .btn-secondary {
            background: #ffca28;
            color: #1c2526;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #ff8f00;
            transform: translateY(-1px);
        }

        .input-group {
            position: relative;
            border-radius: 15px !important;
            overflow: hidden;
        }

        .valid-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #28a745;
            font-size: 1.2rem;
            display: none;
        }

        .form-control.valid {
            padding-right: 40px;
            border-radius: 15px !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .signup-container {
                width: 90%;
                max-width: 400px;
                padding: 15px;
                margin: 10px;
                border-radius: 15px;
            }

            .logo {
                width: 60px;
                height: 60px;
            }

            h1 {
                font-size: 1.4rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .form-control, .form-select {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px !important;
            }

            .btn-primary {
                padding: 8px;
                font-size: 0.85rem;
                border-radius: 10px;
            }

            .footer {
                font-size: 0.85rem;
            }

            .footer a {
                font-size: 0.85rem;
            }

            .alert {
                font-size: 0.85rem;
                padding: 8px;
                border-radius: 10px;
            }

            .modal-title {
                font-size: 1.1rem;
            }

            .modal-body {
                font-size: 0.85rem;
            }

            .valid-icon {
                right: 10px;
                font-size: 1rem;
            }

            .form-control.valid {
                padding-right: 30px;
                border-radius: 10px !important;
            }

            .input-group {
                border-radius: 10px !important;
            }
        }

        @media (max-width: 400px) {
            .signup-container {
                width: 95%;
                max-width: 340px;
                padding: 12px;
                border-radius: 12px;
            }

            .logo {
                width: 50px;
                height: 50px;
            }

            h1 {
                font-size: 1.2rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .form-control, .form-select {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px !important;
            }

            .btn-primary {
                padding: 6px;
                font-size: 0.8rem;
                border-radius: 8px;
            }

            .footer {
                font-size: 0.8rem;
            }

            .footer a {
                font-size: 0.8rem;
            }

            .alert {
                font-size: 0.8rem;
                padding: 6px;
                border-radius: 8px;
            }

            .modal-title {
                font-size: 1rem;
            }

            .modal-body {
                font-size: 0.8rem;
            }

            .valid-icon {
                right: 8px;
                font-size: 0.9rem;
            }

            .form-control.valid {
                padding-right: 25px;
                border-radius: 8px !important;
            }

            .input-group {
                border-radius: 8px !important;
            }
        }
    </style>
</head>
<body>
<div class="container signup-container">
    <img src="../Logo/Logo_stu.png" alt="Logo" class="logo">
    <h1 id="typewriter">ลงทะเบียน</h1>
    <hr>

    <?php if (isset($_SESSION["error"])) { ?>
        <div class="alert alert-danger" role="alert" id="errorAlert" style="display:none;">
            <?php 
                echo $_SESSION["error"];
                unset($_SESSION["error"]);
            ?>
        </div>
    <?php } ?>

    <?php if (isset($_SESSION["success"])) { ?>
        <div class="alert alert-success" role="alert">
            <?php 
                echo $_SESSION["success"];
                unset($_SESSION["success"]);
            ?>
        </div>
    <?php } ?>

    <form action="stu_signup_db.php" method="post" enctype="multipart/form-data" id="signupForm">
        <div class="mb-3">
            <label for="stu_username" class="form-label">ชื่อผู้ใช้</label>
            <div class="input-group">
                <input type="text" class="form-control" name="stu_username" id="stu_username" value="<?php echo isset($_POST['stu_username']) ? htmlspecialchars($_POST['stu_username']) : ''; ?>" required>
                <span class="valid-icon" id="usernameValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_password" class="form-label">รหัสผ่าน</label>
            <div class="input-group">
                <input type="password" class="form-control" name="stu_password" id="stu_password" value="<?php echo isset($_POST['stu_password']) ? htmlspecialchars($_POST['stu_password']) : ''; ?>" required>
                <span class="valid-icon" id="passwordValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_year" class="form-label">ปีการศึกษา</label>
            <select class="form-select" name="stu_year" id="stu_year" required>
                <option value="" selected>เลือกปีการศึกษา</option>
                <?php
                    $current_year = date("Y") + 543;
                    for ($i = $current_year - 4; $i <= $current_year; $i++) {
                        $selected = (isset($_POST['stu_year']) && $_POST['stu_year'] == $i) ? 'selected' : '';
                        echo "<option value='$i' $selected>$i</option>";
                    }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_license" class="form-label">รหัสนักศึกษา</label>
            <div class="input-group">
                <input type="text" class="form-control" name="stu_license" id="stu_license" value="<?php echo isset($_POST['stu_license']) ? htmlspecialchars($_POST['stu_license']) : ''; ?>" required>
                <span class="valid-icon" id="licenseValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_name" class="form-label">ชื่อ</label>
            <div class="input-group">
                <input type="text" class="form-control" name="stu_name" id="stu_name" value="<?php echo isset($_POST['stu_name']) ? htmlspecialchars($_POST['stu_name']) : ''; ?>" required>
                <span class="valid-icon" id="nameValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_lastname" class="form-label">นามสกุล</label>
            <div class="input-group">
                <input type="text" class="form-control" name="stu_lastname" id="stu_lastname" value="<?php echo isset($_POST['stu_lastname']) ? htmlspecialchars($_POST['stu_lastname']) : ''; ?>" required>
                <span class="valid-icon" id="lastnameValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_tel" class="form-label">เบอร์โทร</label>
            <div class="input-group">
                <input type="tel" class="form-control" name="stu_tel" id="stu_tel" value="<?php echo isset($_POST['stu_tel']) ? htmlspecialchars($_POST['stu_tel']) : ''; ?>" required>
                <span class="valid-icon" id="telValidIcon"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>

        <div class="mb-3">
            <label for="stu_faculty" class="form-label">คณะ</label>
            <select class="form-select" name="stu_faculty" id="stu_faculty" onchange="updateMajors()" required>
                <option value="" selected>เลือกคณะ</option>
                <option value="วิศวกรรมศาสตร์" <?php echo isset($_POST['stu_faculty']) && $_POST['stu_faculty'] == 'วิศวกรรมศาสตร์' ? 'selected' : ''; ?>>วิศวกรรมศาสตร์</option>
                <option value="เทคโนโลยี" <?php echo isset($_POST['stu_faculty']) && $_POST['stu_faculty'] == 'เทคโนโลยี' ? 'selected' : ''; ?>>เทคโนโลยี</option>
                <option value="บริหารธุรกิจ" <?php echo isset($_POST['stu_faculty']) && $_POST['stu_faculty'] == 'บริหารธุรกิจ' ? 'selected' : ''; ?>>บริหารธุรกิจ</option>
                <option value="วิทยาศาสตร์" <?php echo isset($_POST['stu_faculty']) && $_POST['stu_faculty'] == 'วิทยาศาสตร์' ? 'selected' : ''; ?>>วิทยาศาสตร์</option>
                <option value="บัญชี" <?php echo isset($_POST['stu_faculty']) && $_POST['stu_faculty'] == 'บัญชี' ? 'selected' : ''; ?>>บัญชี</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_major" class="form-label">สาขา</label>
            <select class="form-select" name="stu_major" id="stu_major" required>
                <option value="" selected>เลือกสาขา</option>
                <?php
                if (isset($_POST['stu_faculty'])) {
                    $majorsByFaculty = [
                        "วิศวกรรมศาสตร์" => ["สาขาวิชาวิศวกรรมเครื่องกล", "สาขาวิชาวิศวกรรมไฟฟ้า", "สาขาวิชาวิศวกรรมอุตสาหการ"],
                        "เทคโนโลยี" => ["สาขาวิชาเทคโนโลยีการจัดการอุตสาหกรรม"],
                        "บริหารธุรกิจ" => ["สาขาวิชาการบัญชี", "สาขาวิชาคอมพิวเตอร์ธุรกิจ", "สาขาวิชาการจัดการทั่วไป"],
                        "วิทยาศาสตร์" => ["สาขาวิชาเทคโนโลยีสารสนเทศ", "สาขาวิชาเทคโนโลยีคอมพิวเตอร์"],
                        "บัญชี" => ["สาขาวิชาการบัญชี"]
                    ];
                    $selectedFaculty = $_POST['stu_faculty'];
                    if (isset($majorsByFaculty[$selectedFaculty])) {
                        foreach ($majorsByFaculty[$selectedFaculty] as $major) {
                            $selected = (isset($_POST['stu_major']) && $_POST['stu_major'] == $major) ? 'selected' : '';
                            echo "<option value='$major' $selected>$major</option>";
                        }
                    }
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="stu_img" class="form-label">รูปภาพประจำตัว</label>
            <input type="file" class="form-control" id="stu_img" name="stu_img" accept="image/*">
        </div>

        <button type="submit" name="signup" class="btn btn-primary mt-3">สมัครสมาชิก</button>
    </form>

    <p class="footer mt-3">เป็นสมาชิกแล้ว? <a href="index.php">เข้าสู่ระบบ</a></p>
</div>

<!-- Bootstrap Modal สำหรับแจ้งเตือน -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="errorModalLabel">ข้อผิดพลาด</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="errorModalBody">
                <!-- ข้อความแจ้งเตือนจะแสดงที่นี่ -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ตกลง</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery และ Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Typewriter effect สำหรับ h1
    const typewriterElement = document.getElementById('typewriter');
    const text = typewriterElement.textContent;
    typewriterElement.textContent = '';
    let i = 0;

    function typeWriter() {
        if (i < text.length) {
            typewriterElement.textContent += text.charAt(i);
            i++;
            setTimeout(typeWriter, 100);
        }
    }
    document.addEventListener('DOMContentLoaded', typeWriter);

    // อัปเดตสาขาตามคณะ
    const majorsByFaculty = {
        "วิศวกรรมศาสตร์": ["สาขาวิชาวิศวกรรมเครื่องกล", "สาขาวิชาวิศวกรรมไฟฟ้า", "สาขาวิชาวิศวกรรมอุตสาหการ"],
        "เทคโนโลยี": ["สาขาวิชาเทคโนโลยีการจัดการอุตสาหกรรม"],
        "บริหารธุรกิจ": ["สาขาวิชาการบัญชี", "สาขาวิชาคอมพิวเตอร์ธุรกิจ", "สาขาวิชาการจัดการทั่วไป"],
        "วิทยาศาสตร์": ["สาขาวิชาเทคโนโลยีสารสนเทศ", "สาขาวิชาเทคโนโลยีคอมพิวเตอร์"],
        "บัญชี": ["สาขาวิชาการบัญชี"]
    };

    function updateMajors() {
        const facultySelect = document.getElementById("stu_faculty");
        const majorSelect = document.getElementById("stu_major");
        const selectedFaculty = facultySelect.value;

        majorSelect.innerHTML = '<option value="" selected>เลือกสาขา</option>';

        if (majorsByFaculty[selectedFaculty]) {
            majorsByFaculty[selectedFaculty].forEach(major => {
                const option = document.createElement("option");
                option.value = major;
                option.textContent = major;
                majorSelect.appendChild(option);
            });
        }
    }

    // อนิเมชันเมื่อพิมพ์
    const inputs = document.querySelectorAll('.form-control, .form-select');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.style.transform = 'scale(1.02)';
            input.style.boxShadow = '0 0 6px rgba(255, 202, 40, 0.4)';
        });
        input.addEventListener('blur', () => {
            input.style.transform = 'scale(1)';
            input.style.boxShadow = 'none';
        });
    });

    // อนิเมชันปุ่มเมื่อคลิก
    const button = document.querySelector('.btn-primary');
    button.addEventListener('mousedown', () => {
        button.style.transform = 'scale(0.95)';
    });
    button.addEventListener('mouseup', () => {
        button.style.transform = 'scale(1)';
    });

    // Validation with Modal on Blur (AJAX และเงื่อนไขอื่น ๆ)
    const errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
    const errorModalBody = document.getElementById('errorModalBody');
    const errorModalLabel = document.getElementById('errorModalLabel');

    // ตรวจสอบชื่อผู้ใช้เมื่อออกจากช่อง
    const usernameInput = document.getElementById('stu_username');
    usernameInput.addEventListener('blur', function() {
        const username = this.value.trim();
        if (username === '') return;

        const usernamePattern = /^[a-zA-Z0-9]{4,20}$/;
        if (!usernamePattern.test(username)) {
            errorModalLabel.textContent = 'ข้อผิดพลาดชื่อผู้ใช้';
            errorModalBody.innerHTML = 'ชื่อผู้ใช้ต้องมีเฉพาะตัวอักษรและตัวเลข ความยาว 4-20 ตัวอักษร';
            errorModal.show();
            usernameInput.classList.remove('valid');
            document.getElementById('usernameValidIcon').style.display = 'none';
            return;
        }

        $.ajax({
            url: 'check_duplicate.php',
            type: 'POST',
            data: { type: 'username', value: username },
            success: function(response) {
                if (response === 'exists') {
                    errorModalLabel.textContent = 'ข้อผิดพลาดชื่อผู้ใช้';
                    errorModalBody.innerHTML = 'ชื่อผู้ใช้ ' + username + ' นี้มีอยู่แล้ว <a href="index.php">คลิกที่นี่เพื่อเข้าสู่ระบบ</a>';
                    errorModal.show();
                    usernameInput.classList.remove('valid');
                    document.getElementById('usernameValidIcon').style.display = 'none';
                } else {
                    usernameInput.classList.add('valid');
                    document.getElementById('usernameValidIcon').style.display = 'block';
                }
            }
        });
    });

    // ตรวจสอบรหัสผ่านเมื่อออกจากช่อง
    const passwordInput = document.getElementById('stu_password');
    passwordInput.addEventListener('blur', function() {
        const password = this.value.trim();
        if (password === '') return;

        const errors = [];
        if (password.length < 8) {
            errors.push('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
        }
        if (!/[a-z]/.test(password)) {
            errors.push('รหัสผ่านต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว');
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว');
        }
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            errors.push('รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว (เช่น !, @, #, $)');
        }

        if (errors.length > 0) {
            errorModalLabel.textContent = 'ข้อผิดพลาดรหัสผ่าน';
            errorModalBody.innerHTML = errors.join('<br>');
            errorModal.show();
            passwordInput.classList.remove('valid');
            document.getElementById('passwordValidIcon').style.display = 'none';
        } else {
            passwordInput.classList.add('valid');
            document.getElementById('passwordValidIcon').style.display = 'block';
        }
    });

    // ตรวจสอบรหัสนักศึกษาเมื่อออกจากช่อง
    const licenseInput = document.getElementById('stu_license');
    licenseInput.addEventListener('blur', function() {
        const license = this.value.trim();
        if (license === '') return;

        const licensePattern = /^[0-9]{13}$/;
        if (!licensePattern.test(license)) {
            errorModalLabel.textContent = 'ข้อผิดพลาดรหัสนักศึกษา';
            errorModalBody.innerHTML = 'รหัสนักศึกษาต้องเป็นตัวเลข 13 หลัก';
            errorModal.show();
            licenseInput.classList.remove('valid');
            document.getElementById('licenseValidIcon').style.display = 'none';
            return;
        }

        $.ajax({
            url: 'check_duplicate.php',
            type: 'POST',
            data: { type: 'license', value: license },
            success: function(response) {
                if (response === 'exists') {
                    errorModalLabel.textContent = 'ข้อผิดพลาดรหัสนักศึกษา';
                    errorModalBody.innerHTML = 'รหัสนักศึกษา ' + license + ' นี้ถูกใช้แล้ว กรุณาตรวจสอบรหัสนักศึกษา';
                    errorModal.show();
                    licenseInput.classList.remove('valid');
                    document.getElementById('licenseValidIcon').style.display = 'none';
                } else {
                    licenseInput.classList.add('valid');
                    document.getElementById('licenseValidIcon').style.display = 'block';
                }
            }
        });
    });

    // ตรวจสอบชื่อเมื่อออกจากช่อง
    const nameInput = document.getElementById('stu_name');
    nameInput.addEventListener('blur', function() {
        const name = this.value.trim();
        if (name === '') return;

        const namePattern = /^[ก-๙\s]{2,50}$/;
        if (!namePattern.test(name)) {
            errorModalLabel.textContent = 'ข้อผิดพลาดชื่อ';
            errorModalBody.innerHTML = 'ชื่อต้องเป็นตัวอักษรภาษาไทยเท่านั้น ความยาว 2-50 ตัวอักษร';
            errorModal.show();
            nameInput.classList.remove('valid');
            document.getElementById('nameValidIcon').style.display = 'none';
        } else {
            nameInput.classList.add('valid');
            document.getElementById('nameValidIcon').style.display = 'block';
        }
    });

    // ตรวจสอบนามสกุลเมื่อออกจากช่อง
    const lastnameInput = document.getElementById('stu_lastname');
    lastnameInput.addEventListener('blur', function() {
        const lastname = this.value.trim();
        if (lastname === '') return;

        const lastnamePattern = /^[ก-๙\s]{2,50}$/;
        if (!lastnamePattern.test(lastname)) {
            errorModalLabel.textContent = 'ข้อผิดพลาดนามสกุล';
            errorModalBody.innerHTML = 'นามสกุลต้องเป็นตัวอักษรภาษาไทยเท่านั้น ความยาว 2-50 ตัวอักษร';
            errorModal.show();
            lastnameInput.classList.remove('valid');
            document.getElementById('lastnameValidIcon').style.display = 'none';
        } else {
            lastnameInput.classList.add('valid');
            document.getElementById('lastnameValidIcon').style.display = 'block';
        }
    });

    // ตรวจสอบเบอร์โทรเมื่อออกจากช่อง
    const telInput = document.getElementById('stu_tel');
    telInput.addEventListener('blur', function() {
        const tel = this.value.trim();
        if (tel === '') return;

        const telPattern = /^[0-9]{10}$/;
        if (!telPattern.test(tel)) {
            errorModalLabel.textContent = 'ข้อผิดพลาดเบอร์โทร';
            errorModalBody.innerHTML = 'เบอร์โทรต้องเป็นตัวเลข 10 หลัก!';
            errorModal.show();
            telInput.classList.remove('valid');
            document.getElementById('telValidIcon').style.display = 'none';
        } else {
            telInput.classList.add('valid');
            document.getElementById('telValidIcon').style.display = 'block';
        }
    });

    // ตรวจสอบฟอร์มก่อนส่ง
    const form = document.querySelector('form');
    form.addEventListener('submit', (e) => {
        const username = document.getElementById('stu_username').value.trim();
        const password = document.getElementById('stu_password').value.trim();
        const license = document.getElementById('stu_license').value.trim();
        const name = document.getElementById('stu_name').value.trim();
        const lastname = document.getElementById('stu_lastname').value.trim();
        const tel = document.getElementById('stu_tel').value.trim();

        const errors = [];

        const usernamePattern = /^[a-zA-Z0-9]{4,20}$/;
        if (!usernamePattern.test(username)) {
            errors.push('ชื่อผู้ใช้ต้องมีเฉพาะตัวอักษรและตัวเลข ความยาว 4-20 ตัวอักษร');
        }

        if (password.length < 8) {
            errors.push('รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร');
        }
        if (!/[a-z]/.test(password)) {
            errors.push('รหัสผ่านต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว');
        }
        if (!/[A-Z]/.test(password)) {
            errors.push('รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว');
        }
        if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            errors.push('รหัสผ่านต้องมีอักขระพิเศษอย่างน้อย 1 ตัว (เช่น !, @, #, $)');
        }

        const licensePattern = /^[0-9]{13}$/;
        if (!licensePattern.test(license)) {
            errors.push('รหัสนักศึกษาต้องเป็นตัวเลข 13 หลัก');
        }

        const namePattern = /^[ก-๙\s]{2,50}$/;
        if (!namePattern.test(name)) {
            errors.push('ชื่อต้องเป็นตัวอักษรภาษาไทยเท่านั้น ความยาว 2-50 ตัวอักษร');
        }

        const lastnamePattern = /^[ก-๙\s]{2,50}$/;
        if (!lastnamePattern.test(lastname)) {
            errors.push('นามสกุลต้องเป็นตัวอักษรภาษาไทยเท่านั้น ความยาว 2-50 ตัวอักษร');
        }

        const telPattern = /^[0-9]{10}$/;
        if (!telPattern.test(tel)) {
            errors.push('เบอร์โทรต้องเป็นตัวเลข 10 หลัก!');
        }

        if (errors.length > 0) {
            e.preventDefault();
            errorModalLabel.textContent = 'ข้อผิดพลาด';
            errorModalBody.innerHTML = errors.join('<br>');
            errorModal.show();
        }
    });

    // แสดง Modal หากมีข้อผิดพลาดจาก Server
    document.addEventListener('DOMContentLoaded', () => {
        const errorAlert = document.getElementById('errorAlert');
        if (errorAlert && errorAlert.textContent.trim() !== '') {
            errorModalLabel.textContent = 'ข้อผิดพลาด';
            errorModalBody.innerHTML = errorAlert.textContent;
            errorModal.show();
            errorAlert.style.display = 'none'; // ซ่อน alert หลังจากแสดงใน modal
        }
    });
</script>
</body>
</html>