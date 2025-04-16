<?php
session_start();
require_once 'config/condb.php';

// ฟังก์ชันแปลงวันที่เป็นรูปแบบภาษาไทย
function getFormattedThaiDate($date, $show_time = true) {
    if (!$date) return 'ไม่ระบุ';
    $dateTime = new DateTime($date);
    $day = $dateTime->format('j');
    $monthNames = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $month = $monthNames[(int)$dateTime->format('n')];
    $year = $dateTime->format('Y') + 543;
    if ($show_time) {
        return "$day $month $year " . $dateTime->format('H:i:s');
    }
    return "$day $month $year";
}

// รับค่าจาก AJAX
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$filter_date = isset($_POST['filter_date']) ? trim($_POST['filter_date']) : '';
$filter_province = isset($_POST['filter_province']) ? trim($_POST['filter_province']) : '';
$filter_amphur = isset($_POST['filter_amphur']) ? trim($_POST['filter_amphur']) : '';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

$limit = 1; // จำกัด 1 คิวต่อหน้า
$offset = ($page - 1) * $limit;

// ดึงข้อมูลคิวที่ปิดงานแล้วทั้งหมด (นับจำนวนทั้งหมดก่อน)
try {
    $sql_count = "SELECT COUNT(DISTINCT q.queue_id) as total
                  FROM queue q
                  JOIN province p ON q.province_id = p.PROVINCE_ID
                  JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
                  JOIN car c ON q.car_id = c.car_id
                  WHERE q.status_car = 'ปิดงาน'";

    $params = [];
    if (!empty($search)) {
        $sql_count .= " AND (q.queue_id LIKE :search 
                     OR q.queue_date LIKE :search 
                     OR c.car_brand LIKE :search 
                     OR c.car_license LIKE :search 
                     OR c.car_color LIKE :search 
                     OR p.PROVINCE_NAME LIKE :search 
                     OR a.AMPHUR_NAME LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($filter_date)) {
        $sql_count .= " AND q.queue_date = :filter_date";
        $params[':filter_date'] = $filter_date;
    }
    if (!empty($filter_province)) {
        $sql_count .= " AND p.PROVINCE_ID = :filter_province";
        $params[':filter_province'] = $filter_province;
    }
    if (!empty($filter_amphur)) {
        $sql_count .= " AND a.AMPHUR_ID = :filter_amphur";
        $params[':filter_amphur'] = $filter_amphur;
    }

    $stmt_count = $conn->prepare($sql_count);
    foreach ($params as $key => $value) {
        $stmt_count->bindValue($key, $value);
    }
    $stmt_count->execute();
    $total_queues = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    echo "<div class='no-data'><i class='fas fa-exclamation-triangle'></i><br>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// คำนวณจำนวนหน้าทั้งหมด
$total_pages = ceil($total_queues / $limit);

// ดึงข้อมูลคิวที่ปิดงานแล้ว (จำกัด 1 คิวต่อหน้า)
try {
    $sql = "SELECT q.queue_id, q.created_at, q.queue_date, p.PROVINCE_NAME, a.AMPHUR_NAME, 
                   c.car_license, c.car_brand, c.car_color
            FROM queue q
            JOIN province p ON q.province_id = p.PROVINCE_ID
            JOIN amphur a ON q.amphur_id = a.AMPHUR_ID
            JOIN car c ON q.car_id = c.car_id
            WHERE q.status_car = 'ปิดงาน'";

    $params = [];
    if (!empty($search)) {
        $sql .= " AND (q.queue_id LIKE :search 
                OR q.queue_date LIKE :search 
                OR c.car_brand LIKE :search 
                OR c.car_license LIKE :search 
                OR c.car_color LIKE :search 
                OR p.PROVINCE_NAME LIKE :search 
                OR a.AMPHUR_NAME LIKE :search)";
        $params[':search'] = "%$search%";
    }
    if (!empty($filter_date)) {
        $sql .= " AND q.queue_date = :filter_date";
        $params[':filter_date'] = $filter_date;
    }
    if (!empty($filter_province)) {
        $sql .= " AND p.PROVINCE_ID = :filter_province";
        $params[':filter_province'] = $filter_province;
    }
    if (!empty($filter_amphur)) {
        $sql .= " AND a.AMPHUR_ID = :filter_amphur";
        $params[':filter_amphur'] = $filter_amphur;
    }

    $sql .= " ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt_queues = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt_queues->bindValue($key, $value);
    }
    $stmt_queues->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt_queues->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt_queues->execute();
    $closed_queues = $stmt_queues->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='no-data'><i class='fas fa-exclamation-triangle'></i><br>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// ดึงข้อมูลจาก queue_log และกลุ่มตาม queue_id
$queue_logs = [];
try {
    $queue_ids = array_column($closed_queues, 'queue_id');
    if (!empty($queue_ids)) {
        $placeholders = implode(',', array_fill(0, count($queue_ids), '?'));
        $stmt_queue_logs = $conn->prepare(
            "SELECT queue_id, status_car, log_timestamp
             FROM queue_log
             WHERE queue_id IN ($placeholders)
             ORDER BY log_timestamp ASC"
        );
        foreach ($queue_ids as $index => $queue_id) {
            $stmt_queue_logs->bindValue($index + 1, $queue_id, PDO::PARAM_INT);
        }
        $stmt_queue_logs->execute();
        $logs = $stmt_queue_logs->fetchAll(PDO::FETCH_ASSOC);

        foreach ($logs as $log) {
            $queue_logs[$log['queue_id']][] = $log;
        }
    }
} catch (PDOException $e) {
    echo "<div class='no-data'><i class='fas fa-exclamation-triangle'></i><br>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// ดึงข้อมูล student_status_log พร้อมชื่อนักเรียน
$student_logs = [];
try {
    if (!empty($queue_ids)) {
        $placeholders = implode(',', array_fill(0, count($queue_ids), '?'));
        $stmt_student_logs = $conn->prepare(
            "SELECT `ssl`.`queue_id`, `ssl`.`student_id`, `ssl`.`stu_status`, `ssl`.`log_timestamp`,
                    `s`.`stu_name`, `s`.`stu_lastname`
            FROM `student_status_log` `ssl`
            LEFT JOIN `students` `s` ON `ssl`.`student_id` = `s`.`stu_ID`
            WHERE `ssl`.`queue_id` IN ($placeholders)
            ORDER BY `ssl`.`log_timestamp` ASC"
        );
        foreach ($queue_ids as $index => $queue_id) {
            $stmt_student_logs->bindValue($index + 1, $queue_id, PDO::PARAM_INT);
        }
        $stmt_student_logs->execute();
        $student_log_data = $stmt_student_logs->fetchAll(PDO::FETCH_ASSOC);

        foreach ($student_log_data as $log) {
            $student_logs[$log['queue_id']][] = $log;
        }
    }
} catch (PDOException $e) {
    echo "<div class='no-data'><i class='fas fa-exclamation-triangle'></i><br>เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit();
}

// สร้าง HTML สำหรับผลลัพธ์
if (count($closed_queues) > 0) {
    foreach ($closed_queues as $queue) {
        $queue_id = $queue['queue_id'];
        if (!isset($queue_logs[$queue_id]) || empty($queue_logs[$queue_id])) continue;
        ?>
        <div class="card mb-4">
            <div class="queue-header">
                <h3 class="mb-3">ข้อมูลคิว #<?php echo htmlspecialchars($queue_id); ?></h3>
                <!-- เพิ่มปุ่ม Export คิวนี้ -->
                <a href="export_queue_csv.php?queue_id=<?php echo htmlspecialchars($queue_id); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-csv me-2"></i>Export คิวนี้ (CSV)
                </a>
            </div>
            <div class="queue-details-grid">
                <div class="detail-item">
                    <label>คิว</label>
                    <span>#<?php echo htmlspecialchars($queue_id); ?></span>
                </div>
                <div class="detail-item">
                    <label>รถ</label>
                    <span><?php echo htmlspecialchars($queue['car_brand'] . " (" . $queue['car_license'] . ", " . $queue['car_color'] . ")"); ?></span>
                </div>
                <div class="detail-item">
                    <label>วันที่คิว</label>
                    <span><?php echo getFormattedThaiDate($queue['queue_date'], false); ?></span>
                </div>
                <div class="detail-item">
                    <label>รายละเอียด</label>
                    <span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#studentModal<?php echo $queue_id; ?>">
                            รายละเอียดนักเรียนในคิว
                        </button>
                    </span>
                </div>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>สถานะ</th>
                            <th>วันที่และเวลา</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $is_first_phase = true;
                        $is_second_phase = false;
                        $arrived_count = 0;
                        $first_phase_displayed = false;
                        $second_phase_displayed = false;

                        foreach ($queue_logs[$queue_id] as $log):
                            if ($log['status_car'] === 'ถึงที่หมาย') {
                                $arrived_count++;
                            }

                            $row_class = '';
                            if ($arrived_count === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                                $is_first_phase = false;
                            } elseif ($arrived_count === 1 && !$is_first_phase && $log['status_car'] === 'ถึงจุดรับ') {
                                $is_second_phase = true;
                            }

                            if ($is_first_phase && !$first_phase_displayed) {
                                echo '<tr class="phase-row"><td colspan="2" class="text-center">ขาไปมหาวิทยาลัย</td></tr>';
                                $first_phase_displayed = true;
                            } elseif ($is_second_phase && !$second_phase_displayed) {
                                echo '<tr class="phase-row"><td colspan="2" class="text-center">ขากลับจากมหาวิทยาลัย</td></tr>';
                                $second_phase_displayed = true;
                            }

                            $row_class = $is_first_phase ? 'row-first-phase' : 'row-second-phase';
                            if ($log['status_car'] === 'ปิดงาน') {
                                $row_class = 'row-closed';
                            }

                            $status_class = 'status-default';
                            if ($log['status_car'] === 'ปิดงาน') {
                                $status_class = 'status-closed';
                            } elseif ($log['status_car'] === 'ถึงที่หมาย') {
                                $status_class = 'status-arrived';
                            } elseif ($log['status_car'] === 'ออกเดินทาง') {
                                $status_class = 'status-departed';
                            } elseif ($log['status_car'] === 'ถึงจุดรับ') {
                                $status_class = 'status-pickup';
                            }
                        ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td>
                                    <span class="<?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($log['status_car']); ?>
                                    </span>
                                </td>
                                <td><?php echo getFormattedThaiDate($log['log_timestamp'], true); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal สำหรับแสดงรายละเอียดนักเรียน -->
        <div class="modal fade" id="studentModal<?php echo $queue_id; ?>" tabindex="-1" aria-labelledby="studentModalLabel<?php echo $queue_id; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="studentModalLabel<?php echo $queue_id; ?>">รายละเอียดนักเรียนในคิว #<?php echo htmlspecialchars($queue_id); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>จังหวัด:</strong> <?php echo htmlspecialchars($queue['PROVINCE_NAME']); ?></p>
                        <p><strong>อำเภอ:</strong> <?php echo htmlspecialchars($queue['AMPHUR_NAME']); ?></p>
                        <hr>
                        <?php if (isset($student_logs[$queue_id]) && !empty($student_logs[$queue_id])): ?>
                            <?php
                            $first_phase_students = [];
                            $second_phase_students = [];
                            $is_first_phase = true;
                            $is_second_phase = false;
                            $arrived_count = 0;

                            foreach ($queue_logs[$queue_id] as $log) {
                                if ($log['status_car'] === 'ถึงที่หมาย') {
                                    $arrived_count++;
                                }
                                if ($arrived_count === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                                    $is_first_phase = false;
                                } elseif ($arrived_count === 1 && !$is_first_phase && $log['status_car'] === 'ถึงจุดรับ') {
                                    $is_second_phase = true;
                                }
                            }

                            foreach ($student_logs[$queue_id] as $student_log) {
                                $student_timestamp = new DateTime($student_log['log_timestamp']);
                                $is_first_phase_for_student = true;
                                $is_second_phase_for_student = false;
                                $arrived_count_for_student = 0;

                                foreach ($queue_logs[$queue_id] as $log) {
                                    $log_timestamp = new DateTime($log['log_timestamp']);
                                    if ($log['status_car'] === 'ถึงที่หมาย') {
                                        $arrived_count_for_student++;
                                    }
                                    if ($arrived_count_for_student === 1 && $log['status_car'] === 'ถึงที่หมาย') {
                                        $is_first_phase_for_student = false;
                                    } elseif ($arrived_count_for_student === 1 && !$is_first_phase_for_student && $log['status_car'] === 'ถึงจุดรับ') {
                                        $is_second_phase_for_student = true;
                                    }

                                    if ($student_timestamp <= $log_timestamp) {
                                        break;
                                    }
                                }

                                if ($is_first_phase_for_student) {
                                    $first_phase_students[] = $student_log;
                                } else {
                                    $second_phase_students[] = $student_log;
                                }
                            }
                            ?>

                            <!-- ขาไปมหาวิทยาลัย -->
                            <h5>ขาไปมหาวิทยาลัย</h5>
                            <?php if (!empty($first_phase_students)): ?>
                                <div class="table-container">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ชื่อ-นามสกุล</th>
                                                <th>สถานะ</th>
                                                <th>วันที่และเวลา</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($first_phase_students as $student_log): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                                                            ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                                                            : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')'; 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student_log['stu_status']); ?></td>
                                                    <td><?php echo getFormattedThaiDate($student_log['log_timestamp'], true); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">ไม่มีข้อมูลนักเรียนในช่วงขาไป</p>
                            <?php endif; ?>

                            <!-- ขากลับจากมหาวิทยาลัย -->
                            <h5 class="mt-4">ขากลับจากมหาวิทยาลัย</h5>
                            <?php if (!empty($second_phase_students)): ?>
                                <div class="table-container">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ชื่อ</th>
                                                <th>สถานะ</th>
                                                <th>วันที่และเวลา</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($second_phase_students as $student_log): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        echo (isset($student_log['stu_name']) && isset($student_log['stu_lastname'])) 
                                                            ? htmlspecialchars($student_log['stu_name'] . ' ' . $student_log['stu_lastname']) 
                                                            : 'ไม่พบชื่อนักเรียน (ID: ' . htmlspecialchars($student_log['student_id']) . ')'; 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student_log['stu_status']); ?></td>
                                                    <td><?php echo getFormattedThaiDate($student_log['log_timestamp'], true); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">ไม่มีข้อมูลนักเรียนในช่วงขากลับ</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-center">ไม่มีข้อมูลนักเรียนในคิวนี้</p>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo "<div class='no-data'><i class='fas fa-bus'></i><br>ไม่มีข้อมูลคิวรถที่ปิดงานแล้วในขณะนี้</div>";
}

// เพิ่มส่วน Pagination
if ($total_queues > 0) {
    echo '<nav aria-label="Page navigation">';
    echo '<ul class="pagination justify-content-center">';
    
    // ปุ่ม Previous
    echo '<li class="page-item ' . ($page <= 1 ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="javascript:void(0)" onclick="fetchQueues(' . ($page - 1) . ')" aria-label="Previous">';
    echo '<span aria-hidden="true">«</span>';
    echo '</a>';
    echo '</li>';

    // ตัวเลขหน้า
    for ($i = 1; $i <= $total_pages; $i++) {
        echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '">';
        echo '<a class="page-link" href="javascript:void(0)" onclick="fetchQueues(' . $i . ')">' . $i . '</a>';
        echo '</li>';
    }

    // ปุ่ม Next
    echo '<li class="page-item ' . ($page >= $total_pages ? 'disabled' : '') . '">';
    echo '<a class="page-link" href="javascript:void(0)" onclick="fetchQueues(' . ($page + 1) . ')" aria-label="Next">';
    echo '<span aria-hidden="true">»</span>';
    echo '</a>';
    echo '</li>';

    echo '</ul>';
    echo '</nav>';
}
?>