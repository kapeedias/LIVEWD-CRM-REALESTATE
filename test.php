<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

try {
    $pdo = Database::getInstance();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$userId = 46;
$message = '';


    // Fetch hashed password from DB
    $stmt = $pdo->prepare("SELECT pwd FROM general_info_users WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo $message = "User with ID $userId not found.";
    } else {
        $hashedPassword = $user['pwd'];

        if (password_verify('@_aPTE=I_Qx)2m21Ojstl', $hashedPassword)) {
             echo $message = "✅ Password is CORRECT!";
        } else {
            echo $message = "❌ Password is INCORRECT.";
        }
    }


    
?>