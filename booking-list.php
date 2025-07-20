<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get bookings
$stmt = $pdo->prepare("SELECT b.*, c.name as customer_name, s.name as service_name 
                       FROM bookings b
                       JOIN customers c ON b.customer_id = c.id
                       JOIN services s ON b.service_id = s.id
                       WHERE b.status != 'cancelled'
                       ORDER BY b.booking_date DESC, b.booking_time DESC");
$stmt->execute();
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Booking - Style Lab Barber</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.html">Style Lab Barber</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="booking-list.php">Daftar Booking</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <h2 class="mb-4">Daftar Booking</h2>
        
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>Nama Pelanggan</th>
                        <th>Layanan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= $booking['booking_date'] ?></td>
                        <td><?= $booking['booking_time'] ?></td>
                        <td><?= $booking['customer_name'] ?></td>
                        <td><?= $booking['service_name'] ?></td>
                        <td>
                            <span class="badge bg-<?= $booking['status'] === 'confirmed' ? 'success' : ($booking['status'] === 'completed' ? 'info' : 'warning') ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <?php if ($booking['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="confirmBooking(<?= $booking['id'] ?>)">
                                    Konfirmasi
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="cancelBooking(<?= $booking['id'] ?>)">
                                    Batalkan
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    async function confirmBooking(id) {
        if (confirm('Apakah Anda yakin ingin mengonfirmasi booking ini?')) {
            try {
                const response = await fetch('api/update-booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        status: 'confirmed'
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Booking berhasil dikonfirmasi!');
                    location.reload();
                } else {
                    alert('Gagal mengonfirmasi booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        }
    }

    async function cancelBooking(id) {
        if (confirm('Apakah Anda yakin ingin membatalkan booking ini?')) {
            try {
                const response = await fetch('api/update-booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: id,
                        status: 'cancelled'
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    alert('Booking berhasil dibatalkan!');
                    location.reload();
                } else {
                    alert('Gagal membatalkan booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan');
            }
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
