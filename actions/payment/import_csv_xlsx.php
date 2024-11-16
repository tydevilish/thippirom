<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_PAYMENT);

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['csv_file']) || isset($_FILES['xlsx_file']))) {
    try {
        $file = isset($_FILES['csv_file']) ? $_FILES['csv_file'] : $_FILES['xlsx_file'];
        $file_path = $file['tmp_name'];
        
        if (!file_exists($file_path)) {
            throw new Exception('ไม่พบไฟล์ที่อัปโหลด');
        }

        // ตรวจสอบนามสกุลไฟล์
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file_extension === 'xlsx') {
            // โหลดไฟล์ Excel
            $spreadsheet = IOFactory::load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();

            // เริ่มทำ Transaction
            $conn->beginTransaction();

            // อ่านข้อมูลจากแต่ละแถว
            foreach ($worksheet->getRowIterator(3) as $row) { // เริ่มจากแถวที่ 3 (ข้ามส่วนหัว)
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                
                foreach ($cellIterator as $cell) {
                    $rowData[] = trim($cell->getValue());
                }

                // ตรวจสอบว่าแถวมีข้อมูลหรือไม่
                if (empty($rowData[0])) continue;

                $user_id = $rowData[0]; // ลำดับ (user_id)
                $username = $rowData[1]; // บ้านเลขที่ (username)

                // วนลูปผ่านคอลัมน์ payment (95-96)
                for ($i = 3; $i <= 12; $i++) { // คอลัมน์ D ถึง M
                    // คำนวณ payment_id ตามลำดับใหม่
                    $payment_id = match($i) {
                        3 => 1,  // คอลัมน์ D
                        4 => 2,  // คอลัมน์ E
                        5 => 3,  // คอลัมน์ F
                        6 => 4,  // คอลัมน์ G
                        7 => 5,  // คอลัมน์ H
                        8 => 6,  // คอลัมน์ I
                        9 => 7,  // คอลัมน์ J
                        10 => 8, // คอลัมน์ K
                        11 => 9, // คอลัมน์ L
                        12 => 10, // คอลัมน์ M
                        default => null
                    };

                    // ข้ามถ้าไม่มี payment_id
                    if (!$payment_id) continue;

                    $amount = $rowData[$i];

                    // ข้ามถ้าช่องว่าง
                    if (empty($amount)) continue;

                    // ตรวจสอบว่าเป็น 300 หรือไม่
                    if ($amount == 300) {
                        // เช็คว่ามี transaction อยู่แล้วหรือไม่
                        $stmt = $conn->prepare("
                            SELECT transaction_id 
                            FROM transactions 
                            WHERE payment_id = ? AND user_id = ?
                        ");
                        $stmt->execute([$payment_id, $user_id]);
                        $existing = $stmt->fetch();

                        if ($existing) {
                            // อัพเดทสถานะเป็น approved
                            $stmt = $conn->prepare("
                                UPDATE transactions 
                                SET status = 'approved',
                                    approved_by = ?,
                                    approved_at = CURRENT_TIMESTAMP
                                WHERE payment_id = ? AND user_id = ?
                            ");
                            $stmt->execute([$_SESSION['user_id'], $payment_id, $user_id]);
                        } else {
                            // สร้าง transaction ใหม่
                            $stmt = $conn->prepare("
                                INSERT INTO transactions (
                                    payment_id, user_id, amount, status,
                                    approved_by, approved_at, created_at
                                ) VALUES (
                                    ?, ?, ?, 'approved',
                                    ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                                )
                            ");
                            $stmt->execute([
                                $payment_id,
                                $user_id,
                                300,
                                $_SESSION['user_id']
                            ]);
                        }

                        // เพิ่มหรืออัพเดท payment_users
                        $stmt = $conn->prepare("
                            INSERT INTO payment_users (payment_id, user_id)
                            VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE payment_id = VALUES(payment_id)
                        ");
                        $stmt->execute([$payment_id, $user_id]);
                    }
                }
            }

            // Commit transaction
            $conn->commit();
            
            $_SESSION['success'] = "นำเข้าข้อมูลสำเร็จ";
        } else {
            throw new Exception('รองรับเฉพาะไฟล์ .xlsx เท่านั้น');
        }

        if (isset($spreadsheet)) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        header('Location: ../../pages/payment/manage_payment.php');
        exit();

    } catch (Exception $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        header('Location: ../../pages/payment/manage_payment.php');
        exit();
    }
}

header('Location: ../../pages/payment/manage_payment.php');
exit();