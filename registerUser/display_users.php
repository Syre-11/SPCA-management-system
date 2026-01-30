<?php
include 'DatabaseConnection.php';

// Check if search parameter is provided
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';

if (!empty($search_term)) {
    $search_term_like = '%' . $search_term . '%';
    $where_clause = " WHERE username LIKE ?";
}

$sql = "SELECT * FROM systemuser" . $where_clause . " ORDER BY username";
$stmt = null;

if (!empty($search_term)) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $search_term_like);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("Error preparing statement: " . $conn->error);
    }
} else {
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Users</title>
    <style>
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

        .users-table {
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

        .users-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .users-table tr:hover {
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
    </style>
</head>
<body>
    <a href="Logout.php" class="back-btn">← Logout</a>
    <h1>System Users</h1>

    <div class="filters">
        <form method="GET" action="display_users.php">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search by username..."
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Search</button>
                <a href="display_users.php" class="btn-secondary">Clear Search</a>
            </div>
        </form>
    </div>

    <?php if (!empty($search_term)): ?>
        <div style="text-align: center; margin-bottom: 20px;">
            <strong>Search Results for:</strong> "<?php echo htmlspecialchars($search_term); ?>"
        </div>
    <?php endif; ?>

    <table class="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>First Name</th>
                <th>Surname</th>
                <th>Date of Birth</th>
                <th>Email</th>
                <th>Password</th>
                <th>Cell</th>
                <th>Gender</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['SystemUser_ID']) . "</td>
                        <td>" . htmlspecialchars($row['username']) . "</td>
                        <td>" . htmlspecialchars($row['firstname']) . "</td>
                        <td>" . htmlspecialchars($row['surname']) . "</td>
                        <td>" . htmlspecialchars($row['dateOfBirth']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['password']) . "</td>
                        <td>" . htmlspecialchars($row['phone']) . "</td>
                        <td>" . htmlspecialchars($row['gender']) . "</td>
                        <td>" . htmlspecialchars($row['position']) . "</td>
                        <td class='action-buttons'>
                            <a class='btn-update' href='update_user.php?id=" . $row['SystemUser_ID'] . "'>Update</a>
                            <a class='btn-delete' href='delete_user.php?id=" . $row['SystemUser_ID'] . "' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='11' class='empty-state'>";
                if (!empty($search_term)) {
                    echo "No matching users found for <strong>\"" . htmlspecialchars($search_term) . "\"</strong>. ";
                    echo "<a href='display_users.php' class='btn-secondary'>View all users</a>";
                } else {
                    echo "No users found";
                }
                echo "</td></tr>";
            }

            if ($stmt) { $stmt->close(); }
            ?>
        </tbody>
    </table>

    <?php $conn->close(); ?>
</body>
</html>