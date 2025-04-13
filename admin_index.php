<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: home.html');
    exit;
}
require 'config.php';

// Lấy thống kê theo năm và tháng từ bảng invoices
$yearStats = $pdo->query("SELECT YEAR(b.date) as year, COUNT(*) as total_bookings, SUM(i.amount) as total_revenue FROM bookings b LEFT JOIN invoices i ON b.id = i.booking_id GROUP BY YEAR(b.date)")->fetchAll();

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : null;
$monthStats = [];
if ($selectedYear) {
  $stmt = $pdo->prepare("SELECT MONTH(b.date) as month, COUNT(*) as monthly_bookings, SUM(i.amount) as monthly_revenue FROM bookings b LEFT JOIN invoices i ON b.id = i.booking_id WHERE YEAR(b.date) = ? GROUP BY MONTH(b.date)");
  $stmt->execute([$selectedYear]);
  $monthStats = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Admin - CoWorking Space</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; background: #f4f4f4; }
    .header-admin {
      display: flex; justify-content: space-between; align-items: center;
      background: #4CAF50; padding: 1rem; color: white;
    }
    .dropdown { position: relative; display: inline-block; }
    .dropdown-content {
      display: none; position: absolute; background-color: #f9f9f9;
      min-width: 160px; right: 0; box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
    }
    .dropdown-content a {
      color: black; padding: 10px 16px; text-decoration: none; display: block;
    }
    .dropdown-content a:hover { background-color: #f1f1f1; }
    .dropdown:hover .dropdown-content { display: block; }

    .main { padding: 2rem; max-width: 1200px; margin: auto; }
    .room-buttons {
      display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem;
    }
    .room-buttons button { padding: 6px 12px; font-size: 14px; cursor: pointer; }
    .room-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1rem;
    }
    .room-card {
      background: white; padding: 1rem; border-radius: 10px;
      box-shadow: 0 0 8px rgba(0,0,0,0.1);
    }
    .container { background: white; padding: 2rem; border-radius: 10px; margin-top: 2rem; }
    canvas { margin-top: 2rem; }
    .year-btn { margin: 5px; padding: 6px 12px; background: #eee; border: none; border-radius: 5px; cursor: pointer; }
    .year-btn:hover { background: #ddd; }
  </style>
</head>
<body>
  <header class="header-admin">
    <div><strong>CoWorking Space</strong></div>
    <div>
      <span style="margin-right: 15px;">🔔</span>
      <a href="manage_receptionists.php">👤 Quản lý lễ tân</a>
      <span style="margin-right: 15px;">Chào mừng QTV</span>
      <div class="dropdown">
        <span>⬇️</span>
        <div class="dropdown-content">
          <a href="create_receptionist.php">Tạo tài khoản lễ tân</a>
          <a href="logout.php">Đăng xuất</a>
        </div>
      </div>
    </div>
  </header>

  <main class="main">
    <section>
      <h2>Sơ đồ tổng quát</h2>
      <?php
      $stmt = $pdo->query("SELECT * FROM floors");
      $floors = $stmt->fetchAll();

      if (count($floors) === 0 || isset($_GET['change'])): ?>
        <form id="floorCountForm" method="POST" action="upload_floor_rooms.php" enctype="multipart/form-data">
          <label>Nhập số tầng:</label>
          <input type="number" id="floorCount" name="floor_count" min="1" required>
          <button type="button" onclick="generateFloors()">Xác nhận</button>

          <div id="floorsContainer" style="margin-top: 20px;"></div>
          <button type="submit" style="margin-top: 20px;">Lưu toàn bộ</button>
        </form>

        <script>
          function generateFloors() {
            const count = parseInt(document.getElementById('floorCount').value);
            const container = document.getElementById('floorsContainer');
            container.innerHTML = '';
            window.roomCounter = {};

            for (let i = 1; i <= count; i++) {
              const floorDiv = document.createElement('div');
              floorDiv.innerHTML = `
                <h3>Tầng ${i}</h3>
                <label>Ảnh sơ đồ tầng:</label>
                <input type="file" name="floor_image_${i}" accept="image/*" required><br>
                <div id="rooms_floor_${i}"></div>
                <input type="hidden" name="room_count_${i}" id="room_count_${i}" value="0">
                <button type="button" onclick="addRoom(${i})">➕ Thêm phòng</button>
                <hr>
              `;
              container.appendChild(floorDiv);
            }
          }

          function addRoom(floor) {
            if (!window.roomCounter[floor]) window.roomCounter[floor] = 1;
            const roomId = window.roomCounter[floor]++;
            const roomDiv = document.createElement('div');
            roomDiv.id = `room_${floor}_${roomId}`;
            roomDiv.innerHTML = `
              <p>Phòng ${roomId}:</p>
              <input name="room_name_${floor}_${roomId}" placeholder="Tên phòng" required>
              <input name="room_seats_${floor}_${roomId}" type="number" placeholder="Số chỗ" required>
              <button type="button" onclick="removeRoom(${floor}, ${roomId})">🗑️ Hủy</button>
            `;
            document.getElementById(`rooms_floor_${floor}`).appendChild(roomDiv);

            document.getElementById(`room_count_${floor}`).value = roomId;
          }

          function removeRoom(floor, roomId) {
            const el = document.getElementById(`room_${floor}_${roomId}`);
            if (el) el.remove();
          }
        </script>
      <?php else: ?>
        <?php foreach ($floors as $floor): ?>
          <div style="margin-bottom: 1rem;">
            <img src="uploads/<?= $floor['image_path'] ?>" style="max-width: 300px; border: 1px solid #ccc;">
            <p><strong>Tầng:</strong> <?= $floor['floor_number'] ?></p>
          </div>
        <?php endforeach; ?>
        <a href="?change=true"><button onclick="return confirm('Bạn chắc chắn muốn thay đổi sơ đồ không?')">Thay đổi sơ đồ</button></a>
      <?php endif; ?>
    </section>

    <input type="text" id="roomSearch" placeholder="Tìm phòng..." onkeyup="filterRooms()">
    <script>
    function filterRooms() {
      const input = document.getElementById('roomSearch').value.toLowerCase();
      const cards = document.querySelectorAll('.room-card');
      cards.forEach(card => {
        const roomName = card.querySelector('h3').textContent.toLowerCase();
        card.style.display = roomName.includes(input) ? '' : 'none';
      });
    }
    </script>

    <section>
      <h2>Danh sách phòng</h2>
      <div class="room-buttons">
        <?php
        $stmt = $pdo->query("SELECT * FROM rooms");
        while ($room = $stmt->fetch()):
        ?>
          <button onclick="document.getElementById('room-<?= $room['id'] ?>').scrollIntoView();">
            <?= $room['name'] ?>
          </button>
        <?php endwhile; ?>
      </div>
      <div class="room-grid">
        <?php
        $stmt = $pdo->query("SELECT * FROM rooms");
        while ($room = $stmt->fetch()):
        ?>
          <div class="room-card" id="room-<?= $room['id'] ?>">
            <h3><?= $room['name'] ?></h3>
            <p>
              Số chỗ: <?= $room['seats'] ?> <br>
              <?= $room['projector'] ? '🎥 Máy chiếu' : '' ?>
              <?= $room['tv'] ? '📺 TV' : '' ?>
              <?= $room['mic'] ? '🎤 Mic' : '' ?>
            </p>
            <a href="room_detail.php?id=<?= $room['id'] ?>"><button>Xem</button></a>
          </div>
        <?php endwhile; ?>
      </div>
    </section>

    <section class="container">
      <h2>Thống kê tổng doanh thu và lượt thuê</h2>
      <canvas id="yearChart"></canvas>
      <div>
        <h3>Chi tiết theo năm:</h3>
        <?php foreach ($yearStats as $row): ?>
          <a href="?year=<?= $row['year'] ?>">
            <button class="year-btn"><?= $row['year'] ?></button>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($selectedYear): ?>
        <h3>Chi tiết năm <?= $selectedYear ?></h3>
        <canvas id="monthChart"></canvas>
      <?php endif; ?>

      <br><a href="index.php">← Quay lại</a>
    </section>
  </main>

  <script>
    const yearLabels = <?= json_encode(array_column($yearStats, 'year')) ?>;
    const yearBookings = <?= json_encode(array_column($yearStats, 'total_bookings')) ?>;
    const yearRevenue = <?= json_encode(array_column($yearStats, 'total_revenue')) ?>;

    new Chart(document.getElementById('yearChart'), {
      type: 'bar',
      data: {
        labels: yearLabels,
        datasets: [
          { label: 'Lượt thuê', data: yearBookings, backgroundColor: '#4caf50' },
          { label: 'Tổng thu (VNĐ)', data: yearRevenue, backgroundColor: '#2196f3' }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Thống kê theo năm' }
        }
      }
    });

    <?php if ($selectedYear): ?>
    const monthLabels = <?= json_encode(array_map(fn($m) => "Tháng " . $m['month'], $monthStats)) ?>;
    const monthBookings = <?= json_encode(array_column($monthStats, 'monthly_bookings')) ?>;
    const monthRevenue = <?= json_encode(array_column($monthStats, 'monthly_revenue')) ?>;

    new Chart(document.getElementById('monthChart'), {
      type: 'bar',
      data: {
        labels: monthLabels,
        datasets: [
          { label: 'Lượt thuê', data: monthBookings, backgroundColor: '#ff9800' },
          { label: 'Tổng thu (VNĐ)', data: monthRevenue, backgroundColor: '#9c27b0' }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Chi tiết theo tháng - Năm <?= $selectedYear ?>' }
        }
      }
    });
    <?php endif; ?>
  </script>
</body>
</html>
