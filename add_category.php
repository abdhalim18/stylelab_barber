<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = 'localhost';
    $username = 'root';
    $password = '';  // Kosongkan jika tidak menggunakan password
    $dbname = 'stylelab_db';  // Menggunakan database yang sudah ada

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $categoryName = $_POST['category_name'] ?? '';
        $description = $_POST['description'] ?? '';
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->execute([$categoryName, $description]);
        
        // Redirect kembali ke halaman test_db.php dengan pesan sukses
        header('Location: test_db.php?success=1');
        exit();
        
    } catch(PDOException $e) {
        $error = "Error: " . $e->getMessage();
        header('Location: test_db.php?error=' . urlencode($error));
        exit();
    }
} else {
    // Jika bukan metode POST, redirect ke halaman utama
    header('Location: test_db.php');
    exit();
}
?>
