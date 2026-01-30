<?php
// volunteer_records.php - View, update, soft delete all records

include 'DatabaseConnection.php';


$error = null;
$success = null;

// Handle update and delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $appId = $_POST['appId'];
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE VolunteerApplication SET FirstName = ?, LastName = ?, Email = ? WHERE Application_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare the update statement: " . $conn->error;
        } else {
            $stmt->bind_param("sssi", $firstName, $lastName, $email, $appId);
            $result = $stmt->execute();
            if ($result === false) {
                $error = "Failed to execute the update: " . $conn->error;
            } else {
                $success = "Record updated successfully!";
            }
        }
    } elseif (isset($_POST['delete'])) {
        $appId = $_POST['appId'];
        $stmt = $conn->prepare("UPDATE VolunteerApplication SET IsHidden = 1 WHERE Application_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare the delete statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $appId);
            $result = $stmt->execute();
            if ($result === false) {
                $error = "Failed to execute the delete: " . $conn->error;
            } else {
                $success = "Record archived successfully!";
            }
        }
    } elseif (isset($_POST['restore'])) {
        $appId = $_POST['appId'];
        $stmt = $conn->prepare("UPDATE VolunteerApplication SET IsHidden = 0 WHERE Application_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare the restore statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $appId);
            $result = $stmt->execute();
            if ($result === false) {
                $error = "Failed to execute the restore: " . $conn->error;
            } else {
                $success = "Record restored successfully!";
            }
        }
    }
}

// Handle filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$hidden_filter = isset($_GET['show_hidden']) ? $_GET['show_hidden'] : 'active';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$sql = "SELECT * FROM VolunteerApplication WHERE 1=1";

$params = [];
$types = '';

if ($status_filter !== 'all') {
    $sql .= " AND Status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($hidden_filter === 'active') {
    $sql .= " AND IsHidden = 0";
} elseif ($hidden_filter === 'hidden') {
    $sql .= " AND IsHidden = 1";
}

if (!empty($search_term)) {
    $sql .= " AND (FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= str_repeat('s', 3);
}

$sql .= " ORDER BY CreatedAt DESC";

// Prepare and execute query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = "Failed to prepare the records query: " . $conn->error;
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $records = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Volunteer Records</title>
    <style>
        /* Status filter styling */
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

        .volunteer-link {
            color:rgba(5, 111, 101, 0.7);
            text-decoration: none;
            font-weight: bold;
        }

        .volunteer-link:hover {
            text-decoration: underline;
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
    <a href="volunteer_management.php" class="back-btn">← Back to Volunteer Management</a>
    <h1>All Volunteer Records</h1>

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
                    <label for="show_hidden">Record Status:</label>
                    <select id="show_hidden" name="show_hidden">
                        <option value="active" <?php echo $hidden_filter === 'active' ? 'selected' : ''; ?>>Active Records
                        </option>
                        <option value="hidden" <?php echo $hidden_filter === 'hidden' ? 'selected' : ''; ?>>Archived
                            Records</option>
                        <option value="all" <?php echo $hidden_filter === 'all' ? 'selected' : ''; ?>>All Records</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search names or email..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="volunteer_records.php" class="btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if (empty($records)): ?>
        <p class="empty-state">No records found.</p>
    <?php else: ?>
        <table class="applications-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Hidden</th>
                    <th>Application Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $rec):
                    $status_class = 'status-' . strtolower($rec['Status'] ?? 'pending');
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rec['Application_ID']); ?></td>
                        <td><a href="volunteer_profile.php?email=<?php echo urlencode($rec['Email']); ?>&status=<?php echo urlencode($status_filter); ?>&show_hidden=<?php echo urlencode($hidden_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="volunteer-link"><?php echo htmlspecialchars($rec['FirstName'] . ' ' . $rec['LastName']); ?></a></td>
                        <td><?php echo htmlspecialchars($rec['Email']); ?></td>
                        <td>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($rec['Status']); ?>
                            </span>
                        </td>
                        <td><?php echo $rec['IsHidden'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($rec['CreatedAt']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="volunteer_management.php?id=<?php echo $rec['Application_ID']; ?>"
                                    class="btn-update">Update</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="appId" value="<?php echo $rec['Application_ID']; ?>">
                                    <?php if ($rec['IsHidden']): ?>
                                        <button type="submit" name="restore" class="btn-restore">Restore</button>
                                    <?php else: ?>
                                        <button type="submit" name="delete" class="btn-delete"
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
</body>
</html>