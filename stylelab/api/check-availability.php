<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Jam kerja
$workingHours = [
    'start' => '09:00',
    'end' => '17:00'
];

// Durasi break (dalam menit)
$breakDuration = 30;

// Get parameters
$date = isset($_GET['date']) ? $_GET['date'] : null;
$time = isset($_GET['time']) ? $_GET['time'] : null;

if ($date) {
    try {
        // Get existing bookings for the date
        $stmt = $pdo->prepare("SELECT booking_time, service_id FROM bookings 
                               WHERE booking_date = ? AND status != 'cancelled'");
        $stmt->execute([$date]);
        $bookings = $stmt->fetchAll();
        
        // Get service durations
        $stmt = $pdo->prepare("SELECT id, duration FROM services");
        $stmt->execute();
        $services = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Generate all possible time slots
        $availableTimes = [];
        $startTime = strtotime($workingHours['start']);
        $endTime = strtotime($workingHours['end']);

        while ($startTime < $endTime) {
            $slotTime = date('H:i', $startTime);
            $availableTimes[] = $slotTime;
            $startTime += ($breakDuration * 60); // Add break duration
        }

        // Remove booked times with their durations
        foreach ($bookings as $booking) {
            $bookingTime = strtotime($booking['booking_time']);
            $serviceDuration = $services[$booking['service_id']];
            
            // Remove time slots that overlap with this booking
            for ($i = 0; $i < $serviceDuration / $breakDuration; $i++) {
                $slotTime = date('H:i', $bookingTime + ($i * $breakDuration * 60));
                if (($key = array_search($slotTime, $availableTimes)) !== false) {
                    unset($availableTimes[$key]);
                }
            }
        }

        // Reindex array
        $availableTimes = array_values($availableTimes);

        if ($time) {
            // Check single time slot availability
            $isAvailable = in_array($time, $availableTimes);
            echo json_encode([
                'available' => $isAvailable,
                'message' => $isAvailable ? 'Waktu tersedia' : 'Waktu sudah dibooking'
            ]);
        } else {
            // Return all available times
            echo json_encode([
                'success' => true,
                'available_times' => $availableTimes,
                'message' => 'Berikut waktu yang tersedia untuk booking'
            ]);
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error: ' . $e->getMessage(),
            'message' => 'Terjadi kesalahan saat memeriksa ketersediaan waktu'
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'error' => 'Date parameter is required',
        'message' => 'Silakan pilih tanggal terlebih dahulu'
    ]);
}
?>
