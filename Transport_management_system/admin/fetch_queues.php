<?php
require_once 'config/condb.php';

header('Content-Type: application/json');

// รับค่าจาก AJAX
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = 10; // จำนวนข้อมูลต่อหน้า
$offset = ($page - 1) * $limit;

$search = $_POST['search'] ?? '';
$queue_date = $_POST['queue_date'] ?? '';
$province_id = $_POST['province_id'] ?? '';
$amphur_id = $_POST['amphur_id'] ?? '';
$location = $_POST['location'] ?? '';
$status = $_POST['status'] ?? '';

$where = [];
$params = [];
if (!empty($queue_date)) {
    $where[] = "q.queue_date = :queue_date";
    $params[':queue_date'] = $queue_date;
}
if (!empty($province_id)) {
    $where[] = "q.province_id = :province_id";
    $params[':province_id'] = $province_id;
}
if (!empty($amphur_id)) {
    $where[] = "q.amphur_id = :amphur_id";
    $params[':amphur_id'] = $amphur_id;
}
if (!empty($location)) {
    $where[] = "q.location = :location";
    $params[':location'] = $location;
}
if (!empty($search)) {
    $where[] = "(c.car_license LIKE :search OR s.stu_name LIKE :search OR s.stu_lastname LIKE :search)";
    $params[':search'] = "%$search%";
}

// ดึง queue_id ที่อยู่ใน queue_log เพื่อใช้ในการกรองสถานะ "ปิดงาน"
$queue_log_ids = [];
if ($status === 'ปิดงาน' || empty($status)) {
    $sql_log_all = "SELECT queue_id FROM queue_log";
    $stmt_log_all = $conn->prepare($sql_log_all);
    $stmt_log_all->execute();
    $queue_log_ids = $stmt_log_all->fetchAll(PDO::FETCH_COLUMN);
}

// กรองตามสถานะรถ
if (!empty($status)) {
    if ($status === 'ปิดงาน') {
        // ถ้ากรองสถานะ "ปิดงาน" ให้เลือกเฉพาะ queue_id ที่อยู่ใน queue_log
        if (!empty($queue_log_ids)) {
            $placeholders = [];
            foreach ($queue_log_ids as $index => $queue_id) {
                $placeholders[] = ":queue_log_id_$index";
                $params[":queue_log_id_$index"] = $queue_id;
            }
            $where[] = "q.queue_id IN (" . implode(',', $placeholders) . ")";
        } else {
            // ถ้าไม่มี queue_id ใน queue_log ให้คืนผลลัพธ์ว่าง
            $where[] = "1=0";
        }
    } else {
        // กรองสถานะอื่น ๆ (ว่าง, ไม่ว่าง) และต้องไม่อยู่ใน queue_log
        $where[] = "q.status_car = :status";
        $params[':status'] = $status;
        if (!empty($queue_log_ids)) {
            $placeholders = [];
            foreach ($queue_log_ids as $index => $queue_id) {
                $placeholders[] = ":queue_log_id_$index";
                $params[":queue_log_id_$index"] = $queue_id;
            }
            $where[] = "q.queue_id NOT IN (" . implode(',', $placeholders) . ")";
        }
    }
}

// นับจำนวนข้อมูลทั้งหมด
$sql_count = "
    SELECT COUNT(DISTINCT q.queue_id) as total
    FROM queue q
    LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
    LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
    LEFT JOIN car c ON q.car_id = c.car_id
    LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
    LEFT JOIN students s ON qs.student_id = s.stu_ID
";
if (!empty($where)) {
    $sql_count .= " WHERE " . implode(" AND ", $where);
}

try {
    $stmt_count = $conn->prepare($sql_count);
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    $stmt_count->execute();
    $totalRows = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
} catch (Exception $e) {
    echo json_encode(['error' => 'เกิดข้อผิดพลาดในการนับข้อมูล: ' . $e->getMessage()]);
    exit();
}

// คำนวณจำนวนหน้าทั้งหมด
$totalPages = ceil($totalRows / $limit);

// ดึงข้อมูลตามหน้า
$sql = "
    SELECT 
        q.queue_id, 
        p.PROVINCE_NAME AS province_name, 
        a.AMPHUR_NAME AS amphur_name, 
        q.location, 
        c.car_license, 
        c.car_brand, 
        q.created_at, 
        q.year, 
        q.status_car, 
        q.queue_date
    FROM queue q
    LEFT JOIN province p ON q.province_id = p.PROVINCE_ID
    LEFT JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
    LEFT JOIN car c ON q.car_id = c.car_id
    LEFT JOIN queue_student qs ON q.queue_id = qs.queue_id
    LEFT JOIN students s ON qs.student_id = s.stu_ID
";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " GROUP BY q.queue_id ORDER BY q.queue_date DESC, q.created_at DESC LIMIT :limit OFFSET :offset";

try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $queues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ตรวจสอบ queue_id ในตาราง queue_log และอัปเดตสถานะเป็น "ปิดงาน"
    $queue_ids = array_column($queues, 'queue_id');
    if (!empty($queue_ids)) {
        $placeholders = [];
        $log_params = [];
        foreach ($queue_ids as $index => $queue_id) {
            $placeholders[] = ":queue_id_$index";
            $log_params[":queue_id_$index"] = $queue_id;
        }
        $sql_log = "SELECT queue_id FROM queue_log WHERE queue_id IN (" . implode(',', $placeholders) . ")";
        $stmt_log = $conn->prepare($sql_log);
        
        foreach ($log_params as $key => $value) {
            $stmt_log->bindValue($key, $value, PDO::PARAM_INT);
        }
        
        $stmt_log->execute();
        $logged_queue_ids = $stmt_log->fetchAll(PDO::FETCH_COLUMN);

        foreach ($queues as &$queue) {
            if (in_array($queue['queue_id'], $logged_queue_ids)) {
                $queue['status_car'] = 'ปิดงาน';
            }
        }
        unset($queue);
    }

    $response = [
        'queues' => $queues,
        'totalRows' => $totalRows,
        'totalPages' => $totalPages,
        'currentPage' => $page
    ];
} catch (Exception $e) {
    $response = ['error' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()];
}

echo json_encode($response);
exit();
?>