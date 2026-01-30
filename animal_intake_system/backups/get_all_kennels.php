<?php
// get_all_kennels.php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set header to return JSON
header('Content-Type: application/json');

try {
    // Include database connection without output
    ob_start();
    include 'DatabaseConnection.php';
    $output = ob_get_clean();
    
    // Check if connection was successful
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    // Query to get all kennels
    $sql = "SELECT * FROM kennel ORDER BY Kennel_ID";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $kennels = array();
        while ($row = $result->fetch_assoc()) {
            $kennels[] = $row;
        }
        echo json_encode([
            'success' => true,
            'kennels' => $kennels
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No kennels found in database'
        ]);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>