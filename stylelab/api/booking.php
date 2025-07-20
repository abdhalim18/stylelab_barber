<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert customer if not exists
        $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone), email = VALUES(email)");
        $stmt->execute([$data['name'], $data['phone'], $data['email'] ?? null]);
        $customerId = $pdo->lastInsertId();
        
        // Get service ID (case-insensitive)
        $stmt = $pdo->prepare("SELECT id FROM services WHERE LOWER(name) = LOWER(?)");
        $stmt->execute([$data['service']]);
        $service = $stmt->fetch();
        
        if (!$service) {
            // Try to get service ID by value
            $stmt = $pdo->prepare("SELECT id FROM services WHERE LOWER(name) LIKE LOWER(?)");
            $stmt->execute(['%' . $data['service'] . '%']);
            $service = $stmt->fetch();
            
            if (!$service) {
                throw new Exception('Layanan tidak ditemukan');
            }
        }
        
        // Insert booking
        $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, service_id, booking_date, booking_time, status, notes) 
                              VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$customerId, $service['id'], $data['date'], $data['time'], $data['notes'] ?? null]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Booking berhasil!']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
