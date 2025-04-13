<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'customer';

    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$fullname, $email, $password, $role]);
        header("Location: home.html?registered=1");
        exit;
    } catch (PDOException $e) {
        echo "Đăng ký thất bại: " . $e->getMessage();
    }
}
?>
