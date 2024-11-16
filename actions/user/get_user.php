<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
checkPageAccess(PAGE_MANAGE_USERS);

if (isset($_GET['id'])) {
    try {
        $stmt = $conn->prepare("SELECT u.*, GROUP_CONCAT(utr.tag_id) as tags 
                               FROM users u 
                               LEFT JOIN user_tag_relations utr ON u.user_id = utr.user_id 
                               WHERE u.user_id = ?
                               GROUP BY u.user_id");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['tags'] = $user['tags'] ? explode(',', $user['tags']) : [];
        }
        
        header('Content-Type: application/json');
        echo json_encode($user);
    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing user ID']);
} 