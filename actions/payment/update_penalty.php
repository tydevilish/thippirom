<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $payment_id = $_POST['payment_id'];
        $user_id = $_POST['user_id'];
        $penalty = floatval($_POST['penalty']);

        // ตรวจสอบว่ามีข้อมูลใน payment_users หรือไม่
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM payment_users 
            WHERE payment_id = :payment_id AND user_id = :user_id
        ");
        $stmt->execute([
            'payment_id' => $payment_id,
            'user_id' => $user_id
        ]);
        
        if ($stmt->fetchColumn() == 0) {
            // ถ้าไม่มีข้อมูล ให้เพิ่มใหม่
            $stmt = $conn->prepare("
                INSERT INTO payment_users (payment_id, user_id, penalty)
                VALUES (:payment_id, :user_id, :penalty)
            ");
        } else {
            // ถ้ามีข้อมูลแล้ว ให้อัพเดท
            $stmt = $conn->prepare("
                UPDATE payment_users 
                SET penalty = :penalty 
                WHERE payment_id = :payment_id AND user_id = :user_id
            ");
        }

        $stmt->execute([
            'payment_id' => $payment_id,
            'user_id' => $user_id,
            'penalty' => $penalty
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'อัพเดทเบี้ยปรับเรียบร้อยแล้ว'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ]);
    }
}