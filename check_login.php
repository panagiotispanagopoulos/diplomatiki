<?php
session_start();
require 'config.php';

if ($_SERVER && isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, name, role, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Απλή σύγκριση χωρίς hash
    if ($user && $password === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        switch ($user['role']) {
            case 'student':
                header("Location: student_dashboard.php");
                break;
            case 'professor':
                header("Location: professor_dashboard.php");
                break;
            case 'admin':
                header("Location: admin_dashboard.php");
                break;
            default:
                header("Location: login.php?error=1");
        }
        exit;
    } else {
        header("Location: login.php?error=1");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
