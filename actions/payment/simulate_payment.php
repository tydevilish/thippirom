<!-- http://localhost/diya-valley-master/actions/simulate_payment.php -->

<?php
require_once '../../config/config.php';

try {
    $conn->beginTransaction();
    
    // 1. สร้างรายการค่าส่วนกลางในตาราง payments
    $stmt = $conn->prepare("
        INSERT INTO payments (month, year, description, amount, created_by, status) 
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $currentYear = intval(date('Y')) + 543; // เก็บเป็น พ.ศ. ในฐานข้อมูล
    $description = 'ค่าส่วนกลางประจำเดือน ' . sprintf("%02d/%04d", intval(date('m')), $currentYear);
    $stmt->execute([
        intval(date('m')),
        $currentYear,
        $description,
        1000,
        1
    ]);
    
    // รับ payment_id ที่เพิ่งสร้าง
    $payment_id = $conn->lastInsertId();
    
    // 2. เพิ่มข้อมูลในตาราง payment_users สำหรับลูกบ้านทุกคน
    $stmt = $conn->prepare("
        INSERT INTO payment_users (payment_id, user_id)
        SELECT ?, user_id
        FROM users 
        WHERE role_id = 2
    ");
    $stmt->execute([$payment_id]);
    
    // 3. จำลองการส่งสลิปและชำระเงินสำหรับทุกคนที่ถูกกำหนด
    $stmt = $conn->prepare("
        INSERT INTO transactions 
        (payment_id, user_id, amount, slip_image, status, approved_at, approved_by, created_at)
        SELECT 
            pu.payment_id,
            pu.user_id,
            p.amount,
            'simulated_slip.jpg',
            'approved',
            NOW(),
            1,
            NOW()
        FROM payment_users pu
        JOIN payments p ON p.payment_id = pu.payment_id
        WHERE pu.payment_id = ?
    ");
    $stmt->execute([$payment_id]);
    
    $conn->commit();
    
    // ส่งค่ากลับเป็น JSON
    echo json_encode([
        'success' => true,
        'message' => 'จำลองข้อมูลการชำระเงินสำเร็จ',
        'payment_id' => $payment_id
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
    ]);
}
