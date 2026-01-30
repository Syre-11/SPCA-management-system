<?php
include 'DatabaseConnection.php';

// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch adoptable animals with medical completeness check
$sql = "SELECT a.Animal_ID, a.Animal_Name, a.Animal_Breed, a.Animal_Health, a.picture
        FROM Animal a
        WHERE a.Animal_AdoptionStatus = 'Available' 
        AND a.Animal_Health IN ('Excellent', 'Good')";
$result = $conn->query($sql);

$animals = []; // Initialize outside to avoid undefined variable
if ($result === false) {
    error_log("Query failed at " . date('h:i A T, F d, Y') . ": " . $conn->error);
} else {
    $animals = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="animal-grid">
    <?php foreach ($animals as $animal): ?>
        <div class="animal-card">
            <?php if (!empty($animal['picture'])): ?>
                <!-- Assuming picture contains the image filename -->
                <img src="uploads/<?php echo htmlspecialchars($animal['picture']); ?>"
                    alt="<?php echo htmlspecialchars($animal['Animal_Name']); ?>"
                    onerror="this.src='default-image.jpg'; this.onerror=null;">
            <?php else: ?>

                <img src="placeholder.jpg" alt="<?php echo htmlspecialchars($animal['Animal_Name']); ?>">
            <?php endif; ?>

            <h4><?php echo htmlspecialchars($animal['Animal_Name']); ?></h4>
            <p><strong>Breed:</strong> <?php echo htmlspecialchars($animal['Animal_Breed'] ?? 'Unknown'); ?></p>
            <p><strong>Health:</strong> <?php echo htmlspecialchars($animal['Animal_Health']); ?></p>
            <p class="bio">A loving companion waiting for a forever home. Contact us to learn more!</p>
        </div>
    <?php endforeach; ?>
    <?php if (empty($animals)): ?>
        <p>No adoptable animals available at this time.</p>
    <?php endif; ?>
</div>

<?php
$conn->close();
?>