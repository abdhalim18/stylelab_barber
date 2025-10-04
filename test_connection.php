<?php
// Tampilkan informasi versi PHP
echo "<h2>Informasi Sistem</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Koneksi ke database
$host = '127.0.0.1';
$dbname = 'stylelab_db';
$username = 'root';
$password = '';  // Kosongkan jika tidak menggunakan password

// Coba koneksi dengan socket Unix terlebih dahulu
$dsn = "mysql:unix_socket=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock;dbname=$dbname";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    // Coba koneksi dengan socket
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p>Menggunakan koneksi: Unix Socket</p>";
} catch (PDOException $e) {
    // Jika gagal, coba dengan TCP/IP
    try {
        $dsn = "mysql:host=$host;dbname=$dbname";
        $pdo = new PDO($dsn, $username, $password, $options);
        echo "<p>Menggunakan koneksi: TCP/IP</p>";
    } catch (PDOException $e) {
        die("<p style='color:red'>Gagal terhubung ke database: " . $e->getMessage() . "</p>");
    }
}

// Tampilkan informasi koneksi
echo "<h2>Informasi Koneksi</h2>";
echo "<p>Host: $host</p>";
echo "<p>MySQL Client Version: " . mysqli_get_client_info() . "</p>";

// Cek ekstensi PDO
echo "<h3>Status Ekstensi PHP</h3>";
echo "<p>PDO: " . (extension_loaded('pdo') ? 'Aktif' : 'Tidak Aktif') . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Aktif' : 'Tidak Aktif') . "</p>";

echo "<p>Database: $dbname</p>";
echo "<p>Username: $username</p>";

// Coba koneksi ke MySQL server
try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✓ Berhasil terhubung ke MySQL server</p>";
    
    // Cek apakah database ada
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbname'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Database '$dbname' ditemukan</p>";
        
        // Jika database ditemukan, coba koneksi ke database tersebut
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Query untuk menampilkan daftar tabel
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h2>Daftar Tabel di Database $dbname</h2>";
            
            if (count($tables) > 0) {
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                }
                echo "</ul>";
                
                // Tampilkan isi tabel categories jika ada
                try {
                    $stmt = $pdo->query("SELECT * FROM categories");
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($categories) > 0) {
                        echo "<h3>Daftar Kategori:</h3>";
                        echo "<ul>";
                        foreach ($categories as $category) {
                            echo "<li>" . htmlspecialchars($category['name']) . " - " . 
                                 htmlspecialchars($category['description'] ?? 'Tidak ada deskripsi') . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p>Belum ada data kategori.</p>";
                    }
                } catch (PDOException $e) {
                    echo "<p>Tabel categories belum ada atau error: " . $e->getMessage() . "</p>";
                }
                
            } else {
                echo "<p>Belum ada tabel di database ini.</p>";
            }
            
            // Form untuk menambahkan kategori
            echo "
            <h3>Tambahkan Kategori Baru</h3>
            <form method='post' action='add_category.php'>
                <div>
                    <label>Nama Kategori:</label>
                    <input type='text' name='category_name' required>
                </div>
                <div>
                    <label>Deskripsi:</label>
                    <textarea name='description'></textarea>
                </div>
                <button type='submit'>Tambah Kategori</button>
            </form>";
            
        } catch(PDOException $e) {
            echo "<p style='color:red'>Tidak dapat terhubung ke database $dbname: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>✗ Database '$dbname' tidak ditemukan</p>";
        // Tawarkan untuk membuat database
        echo "<form method='post' action='create_database.php'>
                <input type='hidden' name='dbname' value='$dbname'>
                <button type='submit'>Buat Database $dbname</button>
              </form>";
    }
    
} catch(PDOException $e) {
    die("<p style='color:red'>Tidak dapat terhubung ke MySQL: " . $e->getMessage() . "</p>");
}
