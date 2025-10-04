<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = $_POST['price'] ?? 0;
        $duration = $_POST['duration'] ?? 0;
        $id = $_POST['id'] ?? null;

        if ($name && $price && $duration) {
            if ($id) {
                // Update existing service
                $stmt = $pdo->prepare("UPDATE services SET 
                    name = ?, 
                    description = ?, 
                    price = ?, 
                    duration = ? 
                    WHERE id = ?");
                $stmt->execute([$name, $description, $price, $duration, $id]);
            } else {
                // Add new service
                $stmt = $pdo->prepare("INSERT INTO services (name, description, price, duration) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $price, $duration]);
            }
            
            header('Location: services.php?success=1');
            exit();
        }
    } catch (PDOException $e) {
        $error = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Get services
try {
    $stmt = $pdo->prepare("SELECT * FROM services ORDER BY name");
    $stmt->execute();
    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Terjadi kesalahan: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layanan - Style Lab Barber</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles.css">
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="services.php">Layanan</a>
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
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Layanan</h4>
                        
                        <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success">Perubahan berhasil disimpan!</div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5>Tambah Layanan Baru</h5>
                            <button class="btn btn-primary" onclick="showAddForm()">Tambah Layanan</button>
                        </div>

                        <div id="addForm" style="display: none;">
                            <div class="card mb-4">
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Layanan</label>
                                            <input type="text" class="form-control" name="name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Deskripsi</label>
                                            <textarea class="form-control" name="description" rows="3"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Harga (Rp)</label>
                                            <input type="number" class="form-control" name="price" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Durasi (menit)</label>
                                            <input type="number" class="form-control" name="duration" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                        <button type="button" class="btn btn-secondary" onclick="hideAddForm()">Batal</button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Deskripsi</th>
                                        <th>Harga</th>
                                        <th>Durasi</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($services as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td><?php echo htmlspecialchars($service['description']); ?></td>
                                        <td>Rp <?php echo number_format($service['price']); ?></td>
                                        <td><?php echo $service['duration']; ?> menit</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning me-2" onclick="editService(<?php echo $service['id']; ?>)">Edit</button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteService(<?php echo $service['id']; ?>)">Hapus</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showAddForm() {
        document.getElementById('addForm').style.display = 'block';
    }

    function hideAddForm() {
        document.getElementById('addForm').style.display = 'none';
    }

    function editService(id) {
        // Implement edit functionality
        alert('Fitur edit akan ditambahkan segera');
    }

    function deleteService(id) {
        if (confirm('Apakah Anda yakin ingin menghapus layanan ini?')) {
            // Implement delete functionality
            alert('Fitur delete akan ditambahkan segera');
        }
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
