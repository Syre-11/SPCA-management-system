<?php
// Start the session


include 'DatabaseConnection.php';

// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Simple login check (expand as needed)

// Handle application approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Get application details
        $stmt = $conn->prepare("SELECT Application_ID, First_Name, Last_Name, Email, Phone, Address, Animal_Name FROM adoptionapplication WHERE Application_ID = ?");

        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
            error_log("Approve prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("i", $app_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $animal_name = $row['Animal_Name'];
                $adopter_data = $row;

                $conn->begin_transaction();
                try {
                    // Get the Animal_ID from the animal name
                    $get_animal_id = $conn->prepare("SELECT Animal_ID FROM Animal WHERE Animal_Name = ?");
                    if ($get_animal_id === false) {
                        throw new Exception("Animal ID query failed: " . $conn->error);
                    }
                    $get_animal_id->bind_param("s", $animal_name);
                    $get_animal_id->execute();
                    $animal_result = $get_animal_id->get_result();

                    if ($animal_result->num_rows > 0) {
                        $animal_row = $animal_result->fetch_assoc();
                        $animal_id = $animal_row['Animal_ID'];
                    } else {
                        throw new Exception("Animal not found with name: $animal_name");
                    }
                    $get_animal_id->close();

                    // Insert into Adopter if not exists
                    $adopter_id = null;
                    $check_adopter = $conn->prepare("SELECT Adopter_ID FROM Adopter WHERE Email = ?");
                    if ($check_adopter === false) {
                        throw new Exception("Adopter check query failed: " . $conn->error);
                    }
                    $check_adopter->bind_param("s", $adopter_data['Email']);
                    $check_adopter->execute();
                    $check_result = $check_adopter->get_result();

                    if ($check_result->num_rows > 0) {
                        $adopter_id = $check_result->fetch_assoc()['Adopter_ID'];
                    } else {
                        $stmt_adopter = $conn->prepare("INSERT INTO Adopter (FirstName, Surname, CellNumber, Email, Address, ScreeningStatus) VALUES (?, ?, ?, ?, ?, 'Approved')");
                        if ($stmt_adopter === false) {
                            throw new Exception("Adopter insert query failed: " . $conn->error);
                        }
                        $stmt_adopter->bind_param("sssss", $adopter_data['First_Name'], $adopter_data['Last_Name'], $adopter_data['Phone'], $adopter_data['Email'], $adopter_data['Address']);
                        $stmt_adopter->execute();
                        $adopter_id = $conn->insert_id;
                        $stmt_adopter->close();
                    }
                    $check_adopter->close();

                    // Insert into Adoptions
                    $adoption_date = date('Y-m-d');
                    $follow_up_date = date('Y-m-d', strtotime('+30 days'));
                    $stmt_adoption = $conn->prepare("INSERT INTO Adoptions (AdoptionDate, FollowUpDate, FK_Animal_ID, FK_Adopter_ID, FK_SystemUser_ID) VALUES (?, ?, ?, ?, 1)");
                    if ($stmt_adoption === false) {
                        throw new Exception("Adoption insert query failed: " . $conn->error);
                    }
                    $stmt_adoption->bind_param("ssii", $adoption_date, $follow_up_date, $animal_id, $adopter_id);
                    $stmt_adoption->execute();
                    $stmt_adoption->close();

                    // Update Animal and Application status
                    $stmt_update = $conn->prepare("UPDATE Animal SET Animal_AdoptionStatus = 'Adopted' WHERE Animal_ID = ?");
                    if ($stmt_update === false) {
                        throw new Exception("Animal update query failed: " . $conn->error);
                    }
                    $stmt_update->bind_param("i", $animal_id);
                    $stmt_update->execute();
                    $stmt_update->close();

                    $stmt_status = $conn->prepare("UPDATE adoptionapplication SET Application_Status = 'Approved' WHERE Application_ID = ?");
                    if ($stmt_status === false) {
                        throw new Exception("Application status update failed: " . $conn->error);
                    }
                    $stmt_status->bind_param("i", $app_id);
                    $stmt_status->execute();
                    $stmt_status->close();

                    $success = "Application #$app_id approved successfully.";
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Transaction failed: " . $e->getMessage();
                    error_log("Approve transaction failed: " . $e->getMessage());
                }
            } else {
                $error = "Application not found.";
            }
            $stmt->close();
        }
    } elseif ($action === 'reject') {
        // Simple rejection - just update status
        $stmt_status = $conn->prepare("UPDATE adoptionapplication SET Application_Status = 'Rejected' WHERE Application_ID = ?");

        if ($stmt_status === false) {
            $error = "Rejection query failed: " . $conn->error;
            error_log("Reject prepare failed: " . $conn->error);
        } else {
            $stmt_status->bind_param("i", $app_id);
            if ($stmt_status->execute()) {
                $success = "Application #$app_id rejected successfully.";
            } else {
                $error = "Failed to reject application: " . $stmt_status->error;
                error_log("Reject execute failed: " . $stmt_status->error);
            }
            $stmt_status->close();
        }
    }
}

// Fetch all applications with status and deleted filters (using prepared statement)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$deleted_filter = isset($_GET['show_deleted']) ? $_GET['show_deleted'] : 'active';  // Default: active only

$sql = "SELECT aa.Application_ID, aa.First_Name, aa.Last_Name, aa.Email, aa.Phone, 
               aa.Application_Status, aa.Application_Date, aa.Animal_Name, a.Animal_Breed
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
    $sql .= " AND (aa.is_deleted = 0 OR aa.is_deleted IS NULL)";
} elseif ($deleted_filter === 'deleted') {
    $sql .= " AND aa.is_deleted = 1";
}
// For 'all', no clause added

$sql .= " ORDER BY aa.Application_Date DESC";

// Debug: Log generated SQL (remove after testing)
error_log("Management SQL: " . $sql);
error_log("Params: " . print_r($params, true));

// Prepare and execute query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $error = "Failed to prepare applications query: " . $conn->error;
    error_log("Management prepare failed: " . $conn->error);
    $applications = [];
} else {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $applications = $result->fetch_all(MYSQLI_ASSOC);
    error_log("Fetched " . count($applications) . " applications");
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoption Management - Makhanda SPCA</title>
    <link rel="stylesheet" href="adoption.css">
    <style>
      /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #f9f9f9;
            flex-direction: column;
            /* Ensure navbar + main content stack */
            padding-top: 80px;
            /* Offset fixed nav */
        }


        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 1.6rem;
            color: #444;
        }

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
            background: rgba(5, 111, 101, 0.7);
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

        .btn-approve,
        .btn-reject {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve {
            background: linear-gradient(45deg, #10b981, #34d399);
            color: white;
        }

        .btn-approve:hover {
            background: white;
            color: #10b981;
            border: 2px solid #10b981;
        }

        .btn-reject {
            background: linear-gradient(45deg, #ef4444, #f87171);
            color: white;
        }

        .btn-reject:hover {
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

        .adopter-link {
            color: #444;
            text-decoration: none;
            font-weight: bold;
        }

        .adopter-link:hover {
            text-decoration: underline;
        }

        /* Deleted filter (new) */
        .deleted-filter {
            display: flex;
            justify-content: center;
            margin-bottom: 10px;
            gap: 10px;
        }

        .deleted-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .deleted-btn.active {
            background: #10b981;
            color: white;
        }

        .deleted-btn.all-deleted {
            background: linear-gradient(45deg, #6b7280, #9ca3af);
            color: white;
        }

        /* Navigation Bar */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 5%;
            background: rgba(5, 111, 101, 0.7);
            backdrop-filter: blur(10px);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        /* Logo container */
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Logo image */
        .navbar .logo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .nav-logo h2 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        /* Navigation links */
        .nav-links {
            display: flex;
            align-items: center;
        }

        .nav-links ul {
            list-style: none;
            display: flex;
            gap: 35px;
            margin: 0;
            padding: 0;
        }

        .nav-links ul li {
            position: relative;
        }

        .nav-links ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-links ul li a:hover {
            background-color: transparent;
        }

        .nav-links ul li a::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: white;
            transition: width 0.3s ease;
        }

        .nav-links ul li a:hover::after {
            width: 100%;
        }

        /* Dropdown */
        .nav-links ul li ul {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            background: #34495e;
            padding: 10px;
            border-radius: 5px;
            min-width: 150px;
            z-index: 500;
        }

        .nav-links ul li:hover ul {
            display: block;
        }

        .nav-links ul li ul li {
            margin: 5px 0;
        }

        .nav-links ul li ul li a {
            color: white;
            font-size: 14px;
            padding: 6px 10px;
            display: block;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .nav-links ul {
                gap: 15px;
            }

            nav {
                flex-direction: column;
                align-items: flex-start;
                padding: 10px 20px;
            }

            .nav-logo {
                margin-bottom: 10px;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-logo">
            <a href="../images/Logo.png"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="../Cruelty Reports/admin_dashboard.php">Dashboard</a></li>
                <li><a href="adoption_records.php">Adoption Records</a></li>
                <li><a href="../registerUser/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <header class="header">
            <h1>Adoption Management</h1>
        </header>

        <section>
            <?php
            if (isset($success)) {
                echo "<div class='notification success'>$success</div>";
            }
            if (isset($error)) {
                echo "<div class='notification error'>$error</div>";
            }
            ?>

            <div class="status-filter">
                <a href="?status=all&show_deleted=<?php echo $deleted_filter; ?>"
                    class="status-btn all <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All Applications</a>
                <a href="?status=Pending&show_deleted=<?php echo $deleted_filter; ?>"
                    class="status-btn pending <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=Approved&show_deleted=<?php echo $deleted_filter; ?>"
                    class="status-btn approved <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?status=Rejected&show_deleted=<?php echo $deleted_filter; ?>"
                    class="status-btn rejected <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
            </div>

            <?php if (empty($applications)): ?>
                <p class="empty-state">No applications found. (Fetched: <?php echo count($applications); ?>)</p>
            <?php else: ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Applicant Name</th>
                            <th>Contact Info</th>
                            <th>Animal</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $row):
                            $status_class = '';
                            if ($row['Application_Status'] === 'Pending')
                                $status_class = 'status-pending';
                            if ($row['Application_Status'] === 'Approved')
                                $status_class = 'status-approved';
                            if ($row['Application_Status'] === 'Rejected')
                                $status_class = 'status-rejected';
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Application_ID']); ?></td>
                                <td><a href='adopter_profile.php?email=<?php echo urlencode($row['Email']); ?>'
                                        class='adopter-link'>
                                        <?php echo htmlspecialchars($row['First_Name'] . " " . $row['Last_Name']); ?></a></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?><br><?php echo htmlspecialchars($row['Phone']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['Animal_Name'] . " (" . ($row['Animal_Breed'] ?? 'Unknown') . ")"); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['Application_Date']); ?></td>
                                <td><span class='status-badge <?php echo $status_class; ?>'>
                                        <?php echo htmlspecialchars($row['Application_Status']); ?></span></td>

                                <?php if ($row['Application_Status'] === 'Pending'): ?>
                                    <td class='action-buttons'>
                                        <form method='POST' style='display: inline;'>
                                            <input type='hidden' name='app_id'
                                                value='<?php echo htmlspecialchars($row['Application_ID']); ?>'>
                                            <button type='submit' name='action' value='approve' class='btn-approve'>Approve</button>
                                        </form>
                                        <form method='POST' style='display: inline;'>
                                            <input type='hidden' name='app_id'
                                                value='<?php echo htmlspecialchars($row['Application_ID']); ?>'>
                                            <button type='submit' name='action' value='reject' class='btn-reject'>Reject</button>
                                        </form>
                                    </td>
                                <?php else: ?>
                                    <td><em>Processed</em></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="action-card">
                <a href="adoption_records.php" class="action-btn">Display All Records</a>
            </div>
        </section>

        <footer>
            <div class="footerHeader">
                <h3>Paw Prints - Where every paw matters</h3>
            </div>
            <hr>
            <p>&copy; <?php echo date('Y'); ?> SPCA Makhanda</p>
        </footer>
    </div>
</body>

</html>
<?php
$conn->close();
?>