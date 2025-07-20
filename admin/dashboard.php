<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
try {
    // Total bookings today
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE booking_date = CURDATE() AND status != 'cancelled'");
    $stmt->execute();
    $bookingsToday = $stmt->fetch()['total'];

    // Total bookings this month
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE()) AND status != 'cancelled'");
    $stmt->execute();
    $bookingsMonth = $stmt->fetch()['total'];

    // Total revenue this month
    $stmt = $pdo->prepare("SELECT SUM(s.price) as total FROM bookings b 
                           JOIN services s ON b.service_id = s.id 
                           WHERE MONTH(b.booking_date) = MONTH(CURDATE()) AND YEAR(b.booking_date) = YEAR(CURDATE()) AND b.status = 'completed'");
    $stmt->execute();
    $revenueMonth = $stmt->fetch()['total'] ?? 0;

    // Top services this month
    $stmt = $pdo->prepare("SELECT s.name, COUNT(*) as total 
                           FROM bookings b 
                           JOIN services s ON b.service_id = s.id 
                           WHERE MONTH(b.booking_date) = MONTH(CURDATE()) AND YEAR(b.booking_date) = YEAR(CURDATE()) AND b.status = 'completed'
                           GROUP BY s.id 
                           ORDER BY total DESC 
                           LIMIT 3");
    $stmt->execute();
    $topServices = $stmt->fetchAll();

    // Recent bookings
    $stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, s.name as service_name 
                           FROM bookings b 
                           JOIN customers c ON b.customer_id = c.id 
                           JOIN services s ON b.service_id = s.id 
                           ORDER BY b.created_at DESC 
                           LIMIT 5");
    $stmt->execute();
    $recentBookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = 'Terjadi kesalahan: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Style Lab Barber</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #0d6efd, #3b82f6);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 24px;
        }
        .stat-card p {
            margin: 5px 0 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Style Lab Barber</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">Booking</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Layanan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="customers.php">Pelanggan</a>
                    </li>
                </ul>
                <div class="navbar-nav">
                    <span class="nav-link">Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a class="nav-link" href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Stats Cards -->
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $bookingsToday; ?></h3>
                    <p>Booking Hari Ini</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $bookingsMonth; ?></h3>
                    <p>Booking Bulan Ini</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3>Rp <?php echo number_format($revenueMonth); ?></h3>
                    <p>Pendapatan Bulan Ini</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo count($topServices); ?></h3>
                    <p>Layanan Populer</p>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="dashboard-card p-4">
                    <h4>Booking Terbaru</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Layanan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                    <td><?php echo $booking['booking_date'] . ' ' . $booking['booking_time']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'completed' ? 'info' : 'warning'); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Services -->
            <div class="col-md-6">
                <div class="dashboard-card p-4">
                    <h4>Layanan Populer</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Layanan</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topServices as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td><?php echo $service['total']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</body>
</html>
