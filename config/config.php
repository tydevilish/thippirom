<?php
$host = 'localhost';  
$dbname = 'thippiro_village';  
$username = 'thippiro_diya';  
$password = '6gVcAPBKf4iG';  

try {

    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {

    echo "การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage();
    exit();
}
?>
