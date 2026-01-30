<?php
// get_kennel_data.php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 to prevent output breaking JSON

// Set header to return JSON
header('Content-Type: application/json');

// Check if the request method is POST and kennel_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kennel_id'])) {
    try {
        // Include database connection without output
        ob_start(); // Start output buffering
        include 'DatabaseConnection.php';
        $output = ob_get_clean(); // Capture and discard any output
        
        // Check if connection was successful
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        $kennelId = (int)$_POST['kennel_id'];
        
        // Query to check if kennel exists
        $sql = "SELECT * FROM kennel WHERE Kennel_ID = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $kennelId);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $kennel = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'kennel' => $kennel
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Kennel not found'
            ]);
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request. Please provide a kennel_id.'
    ]);
}
?>