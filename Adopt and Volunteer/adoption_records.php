<?php
// adoption_records.php - View, update, soft delete all adoption application records

require_once("DatabaseConnection.php");


$error = null;
$success = null;

// Handle record updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['soft_delete'])) {
        $app_id = $_POST['app_id'];
        $stmt = $conn->prepare("UPDATE adoptionapplication SET is_deleted = 1 WHERE Application_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare the delete statement: " . $conn->error;
            error_log("Delete prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $app_id);
            if ($stmt->execute()) {
                $success = "Record archived successfully!";
            } else {
                $error = "Archive failed: " . $stmt->error;
                error_log("Delete execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    } elseif (isset($_POST['restore_record'])) {
        $app_id = $_POST['app_id'];
        $stmt = $conn->prepare("UPDATE adoptionapplication SET is_deleted = 0 WHERE Application_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare the restore statement: " . $conn->error;
            error_log("Restore prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $app_id);
            if ($stmt->execute()) {
                $success = "Record restored successfully!";
            } else {
                $error = "Restore failed: " . $stmt->error;
                error_log("Restore execute failed: " . $stmt->error);
            }
            $stmt->close();
        }
    }
}

// Handle filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$deleted_filter = isset($_GET['show_deleted']) ? $_GET['show_deleted'] : 'all';  // Default to 'all' to show everything
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters (removed a.Animal_Species)
$sql = "SELECT 
            aa.Application_ID,
            aa.First_Name,
            aa.Last_Name,
            aa.Email,
            aa.Phone,
            aa.Address,
            aa.Experience,
            aa.Animal_Name,
            aa.Application_Status,
            aa.Application_Date,
            aa.is_deleted,
            a.Animal_Breed,
            a.Animal_Gender
        FROM adoptionapplication aa
        LEFT JOIN Animal a ON aa.Animal_Name = a.Animal_Name
        WHERE 1=1";

$params = [];
$types = '';

if ($status_filter !== 'all') {
    $sql .= " AND aa.Application_Status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($deleted_filter === 'active') {
    $sql .= " AND aa.is_deleted = 0";
} elseif ($deleted_filter === 'deleted') {
    $sql .= " AND aa.is_deleted = 1";
}
// For 'all', no clause added

if (!empty($search_term)) {
    $sql .= " AND (aa.First_Name LIKE ? OR aa.Last_Name LIKE ? OR aa.Email LIKE ? OR aa.Animal_Name LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= str_repeat('s', 4);
}

$sql .= " ORDER BY aa.Application_Date DESC";

// Debug: Log/echo generated SQL (remove after testing)
error_log("Generated SQL: " . $sql);
error_log("Params: " . print_r($params, true));
echo "<!-- DEBUG SQL: $sql | Params: " . print_r($params, true) . " -->";  // View source in browser

// Prepare and execute query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = "Failed to prepare the records query: " . $conn->error;
    error_log("Query prepare failed: " . $conn->error);
    $records = [];  // Ensure empty
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Fetched " . count($records) . " records");
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Adoption Records</title>
    <style>
        /* Your existing styles remain unchanged - omitted for brevity */
        .status-filter {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }

        .status-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .status-btn.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .status-btn.all {
            background: linear-gradient(45deg, #6b7280, #9ca3af);
            color: white;
        }

        .status-btn.pending {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: white;
        }

        .status-btn.approved {
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }

        .status-btn.rejected {
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }

        /* Status badges */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #dc2626;
        }

        /* Table styling */
        .applications-table {
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

        .applications-table th {
            background:rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .applications-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .applications-table tr:hover {
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

        .btn-restore {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }

        .btn-restore:hover {
            background: white;
            color: #10b981;
            border: 2px solid #10b981;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }

        .notification {
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
        }

        .success {
            color: green;
            background: #f0fdf4;
        }

        .error {
            color: red;
            background: #fef2f2;
        }

        /* Additional styles for the records page */
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
            background:rgba(5, 111, 101, 0.7);
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
    </style>
</head>

<body>
    <a href="adoption.management.php" class="back-btn">← Back to Adoption Management</a>
    <h1>All Adoption Records</h1>

    <?php if (isset($success)): ?>
        <div class="notification success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="notification error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="filters">
        <form method="GET" action="">
            <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="show_deleted">Record Status:</label>
                    <select id="show_deleted" name="show_deleted">
                        <option value="all" <?php echo $deleted_filter === 'all' ? 'selected' : ''; ?>>All Records</option>
                        <option value="active" <?php echo $deleted_filter === 'active' ? 'selected' : ''; ?>>Active Records</option>
                        <option value="deleted" <?php echo $deleted_filter === 'deleted' ? 'selected' : ''; ?>>Archived Records</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search names, email, or animal..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="adoption_records.php" class="btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if (empty($records)): ?>
        <p class="empty-state">No records found. (Fetched: <?php echo count($records ?? []); ?> rows)</p>
    <?php else: ?>
        <table class="applications-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Applicant Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Animal Name</th>
                    <th>Breed</th>
                    <th>Gender</th>
                    <th>Status</th>
                    <th>Deleted</th>
                    <th>Application Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $rec):
                    $status_class = 'status-' . strtolower($rec['Application_Status'] ?? 'pending');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rec['Application_ID']); ?></td>
                        <td><?php echo htmlspecialchars($rec['First_Name'] . ' ' . $rec['Last_Name']); ?></td>
                        <td><?php echo htmlspecialchars($rec['Email']); ?></td>
                        <td><?php echo htmlspecialchars($rec['Phone']); ?></td>
                        <td><?php echo htmlspecialchars($rec['Address']); ?></td>
                        <td><?php echo htmlspecialchars($rec['Animal_Name']); ?></td>
                        <td><?php echo htmlspecialchars($rec['Animal_Breed'] ?? 'Unknown'); ?></td>
                        <td><?php echo htmlspecialchars($rec['Animal_Gender'] ?? 'Unknown'); ?></td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($rec['Application_Status'] ?? 'Pending'); ?>
                            </span>
                        </td>
                        <td><?php echo isset($rec['is_deleted']) ? ($rec['is_deleted'] ? 'Yes' : 'No') : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($rec['Application_Date']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if (!isset($rec['is_deleted']) || !$rec['is_deleted']): ?>
                                    <a href="adoption.management.php?id=<?php echo $rec['Application_ID']; ?>"
                                        class="btn-update">Update</a>
                                <?php endif; ?>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="app_id" value="<?php echo $rec['Application_ID']; ?>">
                                    <?php if (isset($rec['is_deleted']) && $rec['is_deleted']): ?>
                                        <button type="submit" name="restore_record" class="btn-restore">Restore</button>
                                    <?php else: ?>
                                        <button type="submit" name="soft_delete" class="btn-delete"
                                            onclick="return confirm('Are you sure you want to delete this record?');">Delete</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php $conn->close(); ?>
</body>

</html>