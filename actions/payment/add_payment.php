<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $month = $_POST['month'];
        $year = $_POST['year'] + 543;
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $selected_users = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
        
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // เพิ่มข้อมูลในตาราง payments
        $stmt = $conn->prepare("
            INSERT INTO payments (month, year, description, amount, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$month, $year, $description, $amount, $_SESSION['user_id']]);
        $payment_id = $conn->lastInsertId();
        
        // เพิ่มข้อมูลการกำหนดผู้ใช้
        if (!empty($selected_users)) {
            $stmt = $conn->prepare("
                INSERT INTO payment_users (payment_id, user_id) 
                VALUES (?, ?)
            ");
            foreach ($selected_users as $user_id) {
                $stmt->execute([$payment_id, $user_id]);
            }
        }
        
        $conn->commit();
        header('Location: ../../pages/payment/manage_payment.php?success=1');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        header('Location: ../../pages/payment/manage_payment.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} 