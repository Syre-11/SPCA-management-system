<?php
require_once("DatabaseConnection.php");

$sql    = "SELECT * FROM animal";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Animal Records</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }
        h2 {
            text-align: center;
            margin: 20px 0;
            color: #333;
        }
        table {
            width: 90%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:nth-child(even) { background: #f2f2f2; }
        a {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 4px;
            margin: 0 3px;
        }
        .update-btn {
            background: #2196F3;
            color: white;
        }
        .delete-btn {
            background: #f44336;
            color: white;
        }
        .health-status {
            font-weight: bold;
        }
        .health-excellent { color: #2ecc71; }
        .health-good { color: #3498db; }
        .health-fair { color: #f39c12; }
        .health-poor { color: #e74c3c; }
    </style>
    <a href="animal_intake.html" class="back-btn">← Back to Animal Intake</a>
</head>
<body>
    <h2>All Animal Records</h2>
    <table>
        <tr>
            
            <th>ID</th>
            <th>Name</th>
            <th>Species</th>
            <th>Breed</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Health Status</th>
            <th>Arrival Date</th>
            <th>Adoption Status</th>
            <th>Actions</th>
        </tr>

        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                // Determine health status class for styling
                $health_class = 'health-' . strtolower($row['Animal_Health'] ?? 'Unknown');
                $health_status = $row['Animal_Health'] ?? 'Not Set';
                
                echo "<tr>
               
                <td>{$row['Animal_ID']}</td>
                <td>{$row['Animal_Name']}</td>
                <td>{$row['Animal_Species']}</td>
                <td>{$row['Animal_Breed']}</td>
                <td>{$row['Animal_Age']}</td>
                <td>{$row['Animal_Gender']}</td>
                <td><span class='health-status $health_class'>$health_status</span></td>
                <td>{$row['Animal_Arrival_Date']}</td>
                <td>{$row['Animal_AdoptionStatus']}</td>
                <td>
                    <a class='update-btn' href='update_animal.php?id={$row['Animal_ID']}'>Update</a>
                    <a class='delete-btn' href='delete_animal.php?id={$row['Animal_ID']}' onclick=\"return confirm('Are you sure you want to delete this record?');\">Delete</a>
                </td>
            </tr>";
            }
        } else {
            echo "<tr><td colspan='11'>No animal records found</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php $conn->close(); ?>