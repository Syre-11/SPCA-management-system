<html>
    <button onclick="window.location.href='homepage.html'">Go back to home page</button>
    <button onclick="window.location.href='donation.html'">Go back to Donations page</button>
</html>    
<?php
// deleteDonation.php
require 'DatabaseConnection.php';

// Accept id from either ?id= or ?donor_id=
$id = null;
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int)$_GET['id'];
} elseif (isset($_GET['donor_id']) && ctype_digit($_GET['donor_id'])) {
    $id = (int)$_GET['donor_id'];
}

if (!$id) {
    http_response_code(400);
    exit('Invalid or missing ID.');
}

/*
 * delete a specific donation by `Donations-ID`.

 */

$sql = "DELETE FROM `alldonations` WHERE `donor_id` = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    exit("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: displayAllDonors.php?msg=deleted");
    exit;
} else {
    header("Location: displayAllDonors.php?msg=notfound");
    exit;
}

