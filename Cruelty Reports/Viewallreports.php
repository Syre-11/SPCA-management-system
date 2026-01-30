<?php
include 'DatabaseConnection.php';

// Initialize filters
$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : 'all';
$deleted_filter = isset($_GET['show_deleted']) && $_GET['show_deleted'] !== '' ? $_GET['show_deleted'] : 'active';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

$like_search = "%$search_term%";

// Map dropdown to DB values
if ($deleted_filter === 'active') {
    $deleted_value = 0;
} elseif ($deleted_filter === 'deleted') {
    $deleted_value = 1;
} else {
    $deleted_value = 'all';
}

// Prepare SQL
$stmt = $conn->prepare("
    SELECT cr.Report_ID, cr.Status, cr.Urgent, cr.ReportDate, cr.deleted,
           a.Animal_Name, a.Animal_Breed
    FROM crueltyreport cr
    LEFT JOIN animal a ON cr.FK_Animal_ID = a.Animal_ID
    WHERE (? = 'all' OR cr.Status = ?)
      AND (? = 'all' OR cr.deleted = ?)
      AND (? = '' OR a.Animal_Name LIKE ? OR a.Animal_Breed LIKE ?)
    ORDER BY cr.ReportDate DESC
");
if (!$stmt) {
    die("SQL error: " . $conn->error);
}

// Bind params
$stmt->bind_param(
    "sssssss",
    $status_filter,
    $status_filter,
    $deleted_value,
    $deleted_value,
    $search_term,
    $like_search,
    $like_search
);

$stmt->execute();
$reports = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Reports</title>
    <style>
        /* Styles from adoption_records.php */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-new {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-active {
            background-color: #d1fae5;
            color: #059669;
        }

        .status-resolved {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .urgent {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .reports-table {
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

        .reports-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .reports-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .reports-table tr:hover {
            background-color: #f3f4f6;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-view,
        .btn-follow-up,
        .btn-manage,
        .btn-delete,
        .btn-restore {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view {
            background: linear-gradient(45deg, #2196F3, #21bef3);
            color: white;
        }

        .btn-view:hover {
            background: white;
            color: #2196F3;
            border: 2px solid #2196F3;
        }

        .btn-follow-up {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: white;
        }

        .btn-follow-up:hover {
            background: white;
            color: #f59e0b;
            border: 2px solid #f59e0b;
        }

        .btn-manage {
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }

        .btn-manage:hover {
            background: white;
            color: #10b981;
            border: 2px solid #10b981;
        }

        .btn-delete {
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }

        .btn-delete:hover {
            background: white;
            color: #ef4444;
            border: 2px solid #ef4444;
        }

        .btn-restore {
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
    </style>
</head>

<body>
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h1>All Reports</h1>

    <div class="filters">
        <form method="GET" action="">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="status">Report Status:</label>
                    <select id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses
                        </option>
                        <option value="New" <?php echo $status_filter === 'New' ? 'selected' : ''; ?>>New</option>
                        <option value="Active" <?php echo $status_filter === 'Active' ? 'selected' : ''; ?>>Active
                        </option>
                        <option value="Resolved" <?php echo $status_filter === 'Resolved' ? 'selected' : ''; ?>>Resolved
                        </option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="show_deleted">Record Status:</label>
                    <select id="show_deleted" name="show_deleted">
                        <option value="active" <?php echo $deleted_filter === 'active' ? 'selected' : ''; ?>>Active
                            Records</option>
                        <option value="deleted" <?php echo $deleted_filter === 'deleted' ? 'selected' : ''; ?>>Archived
                            Records</option>
                        <option value="all" <?php echo $deleted_filter === 'all' ? 'selected' : ''; ?>>All Records
                        </option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search names or breed..."
                        value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="Viewallreports.php" class="btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if ($reports->num_rows > 0): ?>
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Animal Name</th>
                    <th>Breed</th>
                    <th>Status</th>
                    <th>Urgent</th>
                    <th>Report Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($report = $reports->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['Report_ID']); ?></td>
                        <td><?php echo htmlspecialchars($report['Animal_Name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($report['Animal_Breed'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower($report['Status']); ?>">
                                <?php echo htmlspecialchars(ucfirst($report['Status'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="<?php echo $report['Urgent'] ? 'urgent' : ''; ?>">
                                <?php echo $report['Urgent'] ? 'Yes' : 'No'; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($report['ReportDate']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="ViewReport.php?id=<?php echo $report['Report_ID']; ?>" class="btn-view">View</a>
                                <a href="Follow-up.php?id=<?php echo $report['Report_ID']; ?>"
                                    class="btn-follow-up">Follow-up</a>
                                <a href="Cruelty Manage.php?id=<?php echo $report['Report_ID']; ?>"
                                    class="btn-manage">Manage</a>
                                <?php if ($report['deleted'] == 0): ?>
                                    <a href="deleteReport.php?id=<?php echo $report['Report_ID']; ?>" class="btn-delete"
                                        onclick="return confirm('Delete this report?');">Delete</a>
                                <?php else: ?>
                                    <a href="restoreReport.php?id=<?php echo $report['Report_ID']; ?>" class="btn-restore"
                                        onclick="return confirm('Restore this report?');">Restore</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No reports found. (Fetched: <?php echo $reports->num_rows; ?> rows)</p>
    <?php endif; ?>

    <?php $conn->close(); ?>
</body>

</html>