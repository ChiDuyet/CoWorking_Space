<?php
require 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['fullname'] = $user['fullname'];

        // điều hướng theo role
        switch ($user['role']) {
            case 'admin':
                header("Location: admin_index.php");
                break;
            case 'receptionist':
                header("Location: receptionist_index.php");
                break;
            case 'customer':
                header("Location: customer_index.php");
                break;
        }
        exit;
    } else {
        echo "Sai thông tin đăng nhập!";
    }
}
?>
