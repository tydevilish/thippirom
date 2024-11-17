<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_id = $_POST['transaction_id'];
    $status = $_POST['status'];
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;
    $reason = isset($_POST['reason']) ? $_POST['reason'] : null;

    try {
        $conn->beginTransaction();

        // Get transaction details
        $stmt = $conn->prepare("
            SELECT t.*, p.amount as payment_amount, pu.penalty 
            FROM transactions t
            JOIN payments p ON t.payment_id = p.payment_id
            JOIN payment_users pu ON p.payment_id = pu.payment_id AND t.user_id = pu.user_id
            WHERE t.transaction_id = ?
        ");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_amount = $transaction['payment_amount'] + $transaction['penalty'];

        if ($status === 'partial') {
            // Record installment payment
            $stmt = $conn->prepare("
                INSERT INTO payment_installments (transaction_id, amount) 
                VALUES (?, ?)
            ");
            $stmt->execute([$transaction_id, $amount]);

            // Calculate total paid amount
            $stmt = $conn->prepare("
                SELECT SUM(amount) as total_paid 
                FROM payment_installments 
                WHERE transaction_id = ?
            ");
            $stmt->execute([$transaction_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_paid = $result['total_paid'];

            // Update transaction status
            $final_status = $total_paid >= $total_amount ? 'approved' : 'partial';
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = ?, 
                    approved_at = CASE WHEN ? = 'approved' THEN NOW() ELSE NULL END,
                    approved_by = CASE WHEN ? = 'approved' THEN ? ELSE NULL END
                WHERE transaction_id = ?
            ");
            $stmt->execute([$final_status, $final_status, $final_status, $_SESSION['user_id'], $transaction_id]);

        } else {
            // Handle full payment or rejection
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = ?,
                    reject_reason = ?,
                    approved_at = CASE WHEN status = 'approved' THEN NOW() ELSE NULL END,
                    approved_by = CASE WHEN status = 'approved' THEN ? ELSE NULL END
                WHERE transaction_id = ?
            ");
            $stmt->execute([$status, $reason, $_SESSION['user_id'], $transaction_id]);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'payment_id' => $transaction['payment_id']]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 