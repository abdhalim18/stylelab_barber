<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID dan status diperlukan']);
    exit();
}

try {
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $success = $stmt->execute([$data['status'], $data['id']]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Gagal memperbarui status booking']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
