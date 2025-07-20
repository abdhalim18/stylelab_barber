<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Log function for debugging
function logError($message) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    require_once '../config/database.php';
    
    // Test database connection
    $pdo->query('SELECT 1');
} catch (PDOException $e) {
    $errorMsg = 'Database connection failed: ' . $e->getMessage();
    logError($errorMsg);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection error',
        'message' => 'Gagal terhubung ke database',
        'debug' => $errorMsg
    ]);
    exit();
}

// Jam kerja
$workingHours = [
    'start' => '09:00',
    'end' => '17:00'
];

// Durasi break (dalam menit)
$breakDuration = 30;

// Get parameters
$date = isset($_GET['date']) ? trim($_GET['date']) : null;
$time = isset($_GET['time']) ? trim($_GET['time']) : null;

// Log request for debugging
logError("Request received - Date: " . ($date ?? 'null') . ", Time: " . ($time ?? 'null'));

if ($date) {
    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errorMsg = 'Invalid date format. Expected YYYY-MM-DD, got: ' . $date;
        logError($errorMsg);
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid date format',
            'message' => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD',
            'debug' => $errorMsg
        ]);
        exit();
    }
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
        
        if (empty($services)) {
            logError('No services found in database');
            throw new Exception('Tidak ada layanan yang tersedia');
        }

        // Generate all possible time slots
        $availableTimes = [];
        $startTime = strtotime($workingHours['start']);
        $endTime = strtotime($workingHours['end']);
        
        if ($startTime === false || $endTime === false) {
            $errorMsg = 'Invalid working hours format';
            logError($errorMsg);
            throw new Exception('Format jam kerja tidak valid');
        }

        $currentTime = $startTime;
        while ($currentTime < $endTime) {
            $slotTime = date('H:i', $currentTime);
            if ($slotTime === false) {
                logError('Failed to format time slot: ' . $currentTime);
                continue;
            }
            $availableTimes[] = $slotTime;
            $currentTime += ($breakDuration * 60); // Add break duration
        }

        // Remove booked times with their durations
        foreach ($bookings as $booking) {
            try {
                if (!isset($booking['booking_time']) || !isset($booking['service_id'])) {
                    logError('Invalid booking data: ' . json_encode($booking));
                    continue;
                }

                $bookingTime = strtotime($booking['booking_time']);
                if ($bookingTime === false) {
                    logError('Invalid booking time format: ' . $booking['booking_time']);
                    continue;
                }

                $serviceId = $booking['service_id'];
                if (!isset($services[$serviceId])) {
                    logError("Service ID $serviceId not found in services");
                    continue;
                }

                $serviceDuration = (int)$services[$serviceId];
                $slotsToBlock = ceil($serviceDuration / $breakDuration);

                // Remove time slots that overlap with this booking
                for ($i = 0; $i < $slotsToBlock; $i++) {
                    $slotTime = date('H:i', $bookingTime + ($i * $breakDuration * 60));
                    if ($slotTime === false) {
                        logError('Failed to format slot time for booking: ' . $bookingTime);
                        continue;
                    }
                    
                    $key = array_search($slotTime, $availableTimes);
                    if ($key !== false) {
                        unset($availableTimes[$key]);
                    }
                }
            } catch (Exception $e) {
                logError('Error processing booking: ' . $e->getMessage());
                continue;
            }
        }

        // Reindex array and ensure times are sorted
        $availableTimes = array_values(array_unique($availableTimes));
        sort($availableTimes);

        try {
            if ($time) {
                // Validate time format (HH:MM)
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
                    throw new Exception('Format waktu tidak valid. Gunakan format HH:MM');
                }

                // Check single time slot availability
                $isAvailable = in_array($time, $availableTimes);
                $response = [
                    'success' => true,
                    'available' => $isAvailable,
                    'message' => $isAvailable ? 'Waktu tersedia' : 'Waktu sudah dibooking',
                    'time' => $time,
                    'date' => $date
                ];
            } else {
                // Return all available times
                $response = [
                    'success' => true,
                    'available_times' => $availableTimes,
                    'message' => !empty($availableTimes) ? 'Berikut waktu yang tersedia untuk booking' : 'Tidak ada waktu tersedia untuk tanggal ini',
                    'date' => $date,
                    'total_available' => count($availableTimes)
                ];
            }

            // Log the response (without sensitive data)
            logError('Response: ' . json_encode(array_merge($response, ['available_times_count' => count($availableTimes)])));
            
            // Send response
            echo json_encode($response);
            
        } catch (Exception $e) {
            $errorMsg = 'Error generating response: ' . $e->getMessage();
            logError($errorMsg);
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Terjadi kesalahan saat memproses permintaan',
                'debug' => $errorMsg
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
