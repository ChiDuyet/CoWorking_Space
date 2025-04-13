<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.html');
    exit;
}

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $floorCount = isset($_POST['floor_count']) ? (int)$_POST['floor_count'] : 0;

    // Xoá dữ liệu cũ nếu cần làm lại sơ đồ
    $pdo->exec("DELETE FROM rooms");
    $pdo->exec("DELETE FROM floors");

    for ($i = 1; $i <= $floorCount; $i++) {
        // Xử lý ảnh sơ đồ tầng
        if (!isset($_FILES["floor_image_$i"]) || $_FILES["floor_image_$i"]['error'] !== UPLOAD_ERR_OK) {
            continue;
        }

        $imageName = time() . "_floor_$i_" . basename($_FILES["floor_image_$i"]["name"]);
        $targetDir = "uploads/";
        $targetFile = $targetDir . $imageName;
        move_uploaded_file($_FILES["floor_image_$i"]["tmp_name"], $targetFile);

        // Thêm tầng vào database
        $stmt = $pdo->prepare("INSERT INTO floors (floor_number, image_path) VALUES (?, ?)");
        $stmt->execute([$i, $imageName]);
        $floorId = $pdo->lastInsertId();

        // Xử lý thêm phòng
        $roomCount = isset($_POST["room_count_$i"]) ? (int)$_POST["room_count_$i"] : 0;

        for ($j = 1; $j <= $roomCount; $j++) {
            $roomNameKey = "room_name_{$i}_{$j}";
            $roomSeatsKey = "room_seats_{$i}_{$j}";

            if (!isset($_POST[$roomNameKey]) || !isset($_POST[$roomSeatsKey])) continue;

            $roomName = $_POST[$roomNameKey];
            $roomSeats = (int)$_POST[$roomSeatsKey];

            $stmt = $pdo->prepare("INSERT INTO rooms (name, floor_id, seats) VALUES (?, ?, ?)");
            $stmt->execute([$roomName, $floorId, $roomSeats]);
        }
    }

    header("Location: admin_index.php");
    exit;
}
?>
