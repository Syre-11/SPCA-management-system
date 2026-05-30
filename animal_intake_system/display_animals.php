<?php
require_once("DatabaseConnection.php");

// Check if search parameter is provided
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get filter values from URL
$filters = [
    'Animal_ID' => isset($_GET['Animal_ID']) ? trim($_GET['Animal_ID']) : '',
    'Animal_Species' => isset($_GET['Animal_Species']) ? trim($_GET['Animal_Species']) : '',
    'Animal_Breed' => isset($_GET['Animal_Breed']) ? trim($_GET['Animal_Breed']) : '',
    'Animal_Age' => isset($_GET['Animal_Age']) ? trim($_GET['Animal_Age']) : '',
    'Animal_Gender' => isset($_GET['Animal_Gender']) ? trim($_GET['Animal_Gender']) : '',
    'Animal_Size' => isset($_GET['Animal_Size']) ? trim($_GET['Animal_Size']) : '',
    'Animal_Health' => isset($_GET['Animal_Health']) ? trim($_GET['Animal_Health']) : '',
    'Animal_Arrival_Date' => isset($_GET['Animal_Arrival_Date']) ? trim($_GET['Animal_Arrival_Date']) : '',
    'Animal_AdoptionStatus' => isset($_GET['Animal_AdoptionStatus']) ? trim($_GET['Animal_AdoptionStatus']) : '',
    'Kennel_ID' => isset($_GET['Kennel_ID']) ? trim($_GET['Kennel_ID']) : ''
];

// Build SQL query with filters
$where_clauses = [];
$params = [];
$types = '';

// Add search term filter
if (!empty($search_term)) {
    $where_clauses[] = "Animal_Name LIKE ?";
    $params[] = '%' . $search_term . '%';
    $types .= 's';
}

// Add other filters
foreach ($filters as $field => $value) {
    if (!empty($value)) {
        $where_clauses[] = "$field = ?";
        $params[] = $value;
        $types .= 's';
    }
}

// Build the WHERE clause
$where_clause = '';
if (!empty($where_clauses)) {
    $where_clause = " WHERE " . implode(" AND ", $where_clauses);
}

// Build SQL query
$sql = "SELECT * FROM animal" . $where_clause . " ORDER BY Animal_Name";
$stmt = null;

// Execute query with parameters if needed
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
} else {
    // Regular query without filters
    $result = $conn->query($sql);
}

// Get unique values for dropdowns
$species_result = $conn->query("SELECT DISTINCT Animal_Species FROM animal WHERE Animal_Species IS NOT NULL ORDER BY Animal_Species");
$breed_result = $conn->query("SELECT DISTINCT Animal_Breed FROM animal WHERE Animal_Breed IS NOT NULL ORDER BY Animal_Breed");
$gender_result = $conn->query("SELECT DISTINCT Animal_Gender FROM animal WHERE Animal_Gender IS NOT NULL ORDER BY Animal_Gender");
$size_result = $conn->query("SELECT DISTINCT Animal_Size FROM animal WHERE Animal_Size IS NOT NULL ORDER BY Animal_Size");
$health_result = $conn->query("SELECT DISTINCT Animal_Health FROM animal WHERE Animal_Health IS NOT NULL ORDER BY Animal_Health");
$status_result = $conn->query("SELECT DISTINCT Animal_AdoptionStatus FROM animal WHERE Animal_AdoptionStatus IS NOT NULL ORDER BY Animal_AdoptionStatus");
$kennel_result = $conn->query("SELECT DISTINCT Kennel_ID FROM animal WHERE Kennel_ID IS NOT NULL ORDER BY Kennel_ID");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Animal Records</title>
    <link rel="stylesheet" href="animal_records_theme.css">
    <style>
        /* Styles from adoption_records.php */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .health-good {
            background-color: #d1fae5;
            color: #059669;
        }

        .health-fair {
            background-color: #fef3c7;
            color: #d97706;
        }

        .health-poor,
        .health-unknown {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .size-small {
            background-color: #d1fae5;
            color: #059669;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .size-medium {
            background-color: #fef3c7;
            color: #d97706;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .size-large,
        .size-unknown {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .animal-records-table {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            font-family: Arial, Helvetica, sans-serif;
        }

        .animal-records-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .animal-records-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .animal-records-table tr:hover {
            background-color: #f3f4f6;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-update {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #2196F3, #21bef3);
            color: white;
            text-decoration: none;
            display: inline-block;
        }

        .btn-update:hover {
            background: white;
            color: #2196F3;
            border: 2px solid #2196F3;
        }

        .btn-delete {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }

        .btn-delete:hover {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            margin: 20px 0;
            color: #2c3e50;
        }

        .back-btn {
            display: inline-block;
            margin: 20px;
            padding: 10px 15px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }

        .filters {
            width: 90%;
            margin: 20px auto;
            padding: 15px;
            background: white;
            border-radius: 4px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .filter-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }

        .filter-group label {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
        }

        .btn-primary {
            background: #2980b9;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .animal-image {
            max-width: 50px;
            max-height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <a href="animal_intakeSite.php" class="back-btn">← Back to Animal Intake</a>
    <h1>All Animal Records</h1>

    <div class="filters">
        <form method="GET" action="display_animals.php">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search by animal name..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
                <div class="filter-group">
                    <label for="Animal_ID">Animal ID:</label>
                    <input type="text" id="Animal_ID" name="Animal_ID"
                        value="<?php echo htmlspecialchars($filters['Animal_ID']); ?>" placeholder="Enter ID">
                </div>
                <div class="filter-group">
                    <label for="Animal_Species">Species:</label>
                    <select id="Animal_Species" name="Animal_Species">
                        <option value="">All Species</option>
                        <?php while ($row = $species_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_Species']; ?>" <?php echo $filters['Animal_Species'] == $row['Animal_Species'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_Species']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Animal_Breed">Breed:</label>
                    <select id="Animal_Breed" name="Animal_Breed">
                        <option value="">All Breeds</option>
                        <?php while ($row = $breed_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_Breed']; ?>" <?php echo $filters['Animal_Breed'] == $row['Animal_Breed'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_Breed']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Animal_Age">Age:</label>
                    <input type="number" id="Animal_Age" name="Animal_Age"
                        value="<?php echo htmlspecialchars($filters['Animal_Age']); ?>" placeholder="Enter age" min="0">
                </div>
                <div class="filter-group">
                    <label for="Animal_Gender">Gender:</label>
                    <select id="Animal_Gender" name="Animal_Gender">
                        <option value="">All Genders</option>
                        <?php while ($row = $gender_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_Gender']; ?>" <?php echo $filters['Animal_Gender'] == $row['Animal_Gender'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_Gender']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Animal_Size">Size:</label>
                    <select id="Animal_Size" name="Animal_Size">
                        <option value="">All Sizes</option>
                        <?php while ($row = $size_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_Size']; ?>" <?php echo $filters['Animal_Size'] == $row['Animal_Size'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_Size']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Animal_Health">Health Status:</label>
                    <select id="Animal_Health" name="Animal_Health">
                        <option value="">All Health Statuses</option>
                        <?php while ($row = $health_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_Health']; ?>" <?php echo $filters['Animal_Health'] == $row['Animal_Health'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_Health']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Animal_Arrival_Date">Arrival Date:</label>
                    <input type="date" id="Animal_Arrival_Date" name="Animal_Arrival_Date"
                        value="<?php echo htmlspecialchars($filters['Animal_Arrival_Date']); ?>">
                </div>
                <div class="filter-group">
                    <label for="Animal_AdoptionStatus">Adoption Status:</label>
                    <select id="Animal_AdoptionStatus" name="Animal_AdoptionStatus">
                        <option value="">All Statuses</option>
                        <?php while ($row = $status_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Animal_AdoptionStatus']; ?>" <?php echo $filters['Animal_AdoptionStatus'] == $row['Animal_AdoptionStatus'] ? 'selected' : ''; ?>>
                                <?php echo $row['Animal_AdoptionStatus']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="Kennel_ID">Kennel ID:</label>
                    <select id="Kennel_ID" name="Kennel_ID">
                        <option value="">All Kennels</option>
                        <?php while ($row = $kennel_result->fetch_assoc()): ?>
                            <option value="<?php echo $row['Kennel_ID']; ?>" <?php echo $filters['Kennel_ID'] == $row['Kennel_ID'] ? 'selected' : ''; ?>>
                                <?php echo $row['Kennel_ID']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="display_animals.php" class="btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php
    $active_filters = array_filter($filters, function ($value) {
        return !empty($value);
    });
    ?>

    <table class="animal-records-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Size</th>
                    <th>Health Status</th>
                    <th>Arrival Date</th>
                    <th>Adoption Status</th>
                    <th>Kennel ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $health_class = 'health-' . strtolower($row['Animal_Health'] ?? 'unknown');
                    $health_status = htmlspecialchars($row['Animal_Health'] ?? 'Not Set');
                    $size_class = 'size-' . strtolower($row['Animal_Size'] ?? 'unknown');
                    $size_display = htmlspecialchars($row['Animal_Size'] ?? 'Not Set');
                    $image_path = !empty($row['picture']) ? "Uploads/{$row['picture']}" : "../images/Logo.png";
                    $id = (int) $row['Animal_ID'];
                    echo "<tr>
                        <td><img class=\"animal-image\" src=\"" . htmlspecialchars($image_path) . "\" alt=\"\"></td>
                        <td>" . htmlspecialchars($row['Animal_ID']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_Name']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_Species']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_Breed']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_Age']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_Gender']) . "</td>
                        <td><span class=\"{$size_class}\">{$size_display}</span></td>
                        <td><span class=\"status-badge {$health_class}\">{$health_status}</span></td>
                        <td>" . htmlspecialchars($row['Animal_Arrival_Date']) . "</td>
                        <td>" . htmlspecialchars($row['Animal_AdoptionStatus']) . "</td>
                        <td>" . htmlspecialchars($row['Kennel_ID']) . "</td>
                        <td class=\"action-buttons\">
                            <a class=\"btn-update\" href=\"update_animal.php?id={$id}\">Update</a>
                            <a class=\"btn-delete\" href=\"#\" data-spca-delete-animal=\"{$id}\">Delete</a>
                        </td>
                    </tr>";
                }
            }
            ?>
            </tbody>
        </table>

    <?php
    if ($stmt) {
        $stmt->close();
    }
    $conn->close();
    ?>
</body>

</html>