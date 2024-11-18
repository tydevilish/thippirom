<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $transaction_id = $_POST['transaction_id'];
        $status = $_POST['status'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;
        
        $conn->beginTransaction();
        
        if ($status === 'approved') {
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'approved',
                    approved_at = CURRENT_TIMESTAMP,
                    approved_by = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $transaction_id]);
        } else if ($status === 'rejected') {
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'rejected',
                    rejected_at = CURRENT_TIMESTAMP,
                    rejected_by = ?,
                    reject_reason = ?
                WHERE transaction_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $reason, $transaction_id]);
        }
        
        $stmt = $conn->prepare("SELECT payment_id FROM transactions WHERE transaction_id = ?");
        $stmt->execute([$transaction_id]);
        $payment_id = $stmt->fetchColumn();
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'payment_id' => $payment_id]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} 