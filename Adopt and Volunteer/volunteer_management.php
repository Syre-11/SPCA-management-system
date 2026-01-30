<?php
// volunteer_management.php - Manage applications, assignments, profiles

include 'DatabaseConnection.php';


// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle application approval/rejection
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['app_id'])) {
    $app_id = $_POST['app_id'];
    $action = $_POST['action'];

    if ($action === 'approve') {
        // Get application details
        $stmt = $conn->prepare("SELECT Application_ID, FirstName, LastName, Email, Phone, Address FROM VolunteerApplication WHERE Application_ID = ?");

        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("i", $app_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $volunteer_data = $row;

                $conn->begin_transaction();
                try {
                    // Check if volunteer already exists using Email
                    $volunteer_id = null;
                    $check_volunteer = $conn->prepare("SELECT Volunteer_ID FROM Volunteer WHERE Email = ?");
                    if ($check_volunteer === false) {
                        throw new Exception("Volunteer check query failed: " . $conn->error);
                    }
                    $check_volunteer->bind_param("s", $volunteer_data['Email']);
                    $check_volunteer->execute();
                    $check_result = $check_volunteer->get_result();

                    if ($check_result->num_rows > 0) {
                        $volunteer_id = $check_result->fetch_assoc()['Volunteer_ID'];
                    } else {
                        // Insert new volunteer
                        $stmt_volunteer = $conn->prepare("INSERT INTO Volunteer (Volunteer_FirstName, Volunteer_LastName, Volunteer_CellNumber, Email, Phone, Status) VALUES (?, ?, ?, ?, ?, 'Active')");
                        if ($stmt_volunteer === false) {
                            throw new Exception("Volunteer insert query failed: " . $conn->error);
                        }
                        $stmt_volunteer->bind_param("sssss", $volunteer_data['FirstName'], $volunteer_data['LastName'], $volunteer_data['Phone'], $volunteer_data['Email'], $volunteer_data['Phone']);
                        $stmt_volunteer->execute();
                        $volunteer_id = $conn->insert_id;
                        $stmt_volunteer->close();
                    }
                    $check_volunteer->close();

                    // Update VolunteerApplication status
                    $stmt_status = $conn->prepare("UPDATE VolunteerApplication SET Status = 'Approved' WHERE Application_ID = ?");
                    if ($stmt_status === false) {
                        throw new Exception("Application status update query failed: " . $conn->error);
                    }
                    $stmt_status->bind_param("i", $app_id);
                    $stmt_status->execute();
                    $stmt_status->close();

                    $success = "Application #$app_id approved successfully. Volunteer ID: $volunteer_id";
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Transaction failed: " . $e->getMessage();
                }
            } else {
                $error = "Application not found.";
            }
            $stmt->close();
        }
    } elseif ($action === 'reject') {
        $stmt_status = $conn->prepare("UPDATE VolunteerApplication SET Status = 'Rejected' WHERE Application_ID = ?");
        if ($stmt_status === false) {
            $error = "Rejection query failed: " . $conn->error;
        } else {
            $stmt_status->bind_param("i", $app_id);
            if ($stmt_status->execute()) {
                $success = "Application #$app_id rejected successfully.";
            } else {
                $error = "Failed to reject application: " . $stmt_status->error;
            }
            $stmt_status->close();
        }
    }
}

// Handle scheduling and hours recording
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Schedule an activity
    if (isset($_POST['schedule_activity'])) {
        $application_id = $_POST['application_id'];
        $assignment_date = $_POST['assignment_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $activity_type = $_POST['activity_type'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("INSERT INTO VolunteerAssignment (FK_Application_ID, AssignmentDate, StartTime, EndTime, ActivityType, Description) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = "Failed to prepare schedule statement: " . $conn->error;
        } else {
            $stmt->bind_param("isssss", $application_id, $assignment_date, $start_time, $end_time, $activity_type, $description);
            if ($stmt->execute()) {
                $success = "Activity scheduled successfully!";
            } else {
                $error = "Failed to schedule activity: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Record volunteer hours
    if (isset($_POST['record_hours'])) {
        $application_id = $_POST['application_id'];
        $date_performed = $_POST['date_performed'];
        $hours = $_POST['hours'];
        $activity_type = $_POST['activity_type'];
        $description = $_POST['description'];

        $stmt = $conn->prepare("INSERT INTO VolunteerHours (FK_Application_ID, DatePerformed, Hours, ActivityType, Description) VALUES (?, ?, ?, ?, ?)");
        if ($stmt === false) {
            $error = "Failed to prepare hours statement: " . $conn->error;
        } else {
            $stmt->bind_param("isdss", $application_id, $date_performed, $hours, $activity_type, $description);
            if ($stmt->execute()) {
                $success = "Hours recorded successfully!";
            } else {
                $error = "Failed to record hours: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Handle verifying hours
    if (isset($_POST['verify_hours'])) {
        $hours_id = $_POST['hours_id'];
        $email = $_POST['email']; // Preserve email for redirection

        // Toggle the Verified status (0 to 1, or 1 to 0)
        $stmt = $conn->prepare("UPDATE VolunteerHours SET Verified = NOT Verified WHERE Hours_ID = ?");
        if ($stmt === false) {
            $error = "Failed to prepare verify hours statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $hours_id);
            if ($stmt->execute()) {
                $success = "Hours verification status updated successfully!";
                // Redirect to preserve email parameter
                header("Location: volunteer_management.php?email=" . urlencode($email));
                exit();
            } else {
                $error = "Failed to update hours verification: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Fetch all applications with status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause = "WHERE IsHidden = 0";

if ($status_filter !== 'all') {
    $where_clause .= " AND Status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Query to get applications
$sql = "SELECT Application_ID, FirstName, LastName, Email, Phone, Status, CreatedAt 
        FROM VolunteerApplication 
        $where_clause 
        ORDER BY CreatedAt DESC";

$result = $conn->query($sql);

if ($result === false) {
    error_log("Main query failed: " . $conn->error);
    $applications = [];
} else {
    $applications = $result->fetch_all(MYSQLI_ASSOC);
}

// Get volunteer details if email parameter is provided
$volunteer = null;
$assignments = [];
$hours = [];
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = $conn->real_escape_string($_GET['email']);

    // Fetch volunteer application details
    $stmt = $conn->prepare("SELECT Application_ID, FirstName, LastName, Email, Phone, Address, Experience, Interests, Availability, Status, CreatedAt 
                           FROM VolunteerApplication 
                           WHERE Email = ? AND IsHidden = 0");
    if ($stmt === false) {
        $error = "Failed to prepare volunteer query: " . $conn->error;
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $volunteer = $result->fetch_assoc();
        $stmt->close();

        if ($volunteer) {
            // Fetch assignments
            $stmt = $conn->prepare("SELECT AssignmentDate, StartTime, EndTime, ActivityType, Description 
                                  FROM VolunteerAssignment 
                                  WHERE FK_Application_ID = ? 
                                  ORDER BY AssignmentDate, StartTime");
            if ($stmt === false) {
                $error = "Failed to prepare assignments query: " . $conn->error;
            } else {
                $stmt->bind_param("i", $volunteer['Application_ID']);
                $stmt->execute();
                $result = $stmt->get_result();
                $assignments = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }

            // Fetch hours
            $stmt = $conn->prepare("SELECT Hours_ID, DatePerformed, Hours, ActivityType, Description, Verified 
                                  FROM VolunteerHours 
                                  WHERE FK_Application_ID = ? 
                                  ORDER BY DatePerformed DESC");
            if ($stmt === false) {
                $error = "Failed to prepare hours query: " . $conn->error;
            } else {
                $stmt->bind_param("i", $volunteer['Application_ID']);
                $stmt->execute();
                $result = $stmt->get_result();
                $hours = $result->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adoption.css">
    <title>Volunteer Management - Makhanda SPCA</title>
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

        .volunteer-link {
            color: #444;
            text-decoration: none;
            font-weight: bold;
        }

        .volunteer-link:hover {
            text-decoration: underline;
        }

        /* Scheduling section styling */
        .scheduling-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .scheduling-section h3 {
            color: #444;
            margin-bottom: 15px;
        }

        .scheduling-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .scheduling-section input,
        .scheduling-section select,
        .scheduling-section textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .scheduling-section button {
            padding: 10px 20px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .scheduling-section button:hover {
            background: linear-gradient(45deg, #0d966b, #2aa67a);
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 14px;
        }

        .schedule-table th {
            background-color: #f2f2f2;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }

        .schedule-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .schedule-table tr:hover {
            background-color: #f9f9f9;
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

        .btn-verify {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            background: linear-gradient(45deg, #3b82f6, #60a5fa);
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-verify:hover {
            background: white;
            color: #3b82f6;
            border: 2px solid #3b82f6;
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-logo">
            <a href="../frontPage.html"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="volunteer_dashboard.php">Dashboard</a></li>
                <li><a href="../Cruelty Reports/admin_dashboard.php">Admin</a></li>
                <li><a href="volunteer_records.php">Volunteer Records</a></li>
                <li><a href="../registerUser/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <header class="header">
            <h1>Volunteer Management</h1>
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
                <a href="?status=all" class="status-btn all <?php echo $status_filter === 'all' ? 'active' : ''; ?>">All
                    Applications</a>
                <a href="?status=Pending"
                    class="status-btn pending <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=Approved"
                    class="status-btn approved <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?status=Rejected"
                    class="status-btn rejected <?php echo $status_filter === 'Rejected' ? 'active' : ''; ?>">Rejected</a>
            </div>

            <table class="applications-table">
                <thead>
                    <tr>
                        <th>Application ID</th>
                        <th>Applicant Name</th>
                        <th>Contact Info</th>
                        <th>Application Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($applications)) {
                        foreach ($applications as $row) {
                            $status_class = '';
                            if ($row['Status'] === 'Pending')
                                $status_class = 'status-pending';
                            if ($row['Status'] === 'Approved')
                                $status_class = 'status-approved';
                            if ($row['Status'] === 'Rejected')
                                $status_class = 'status-rejected';

                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['Application_ID']) . "</td>";
                            echo "<td><a href='volunteer_management.php?email=" . urlencode($row['Email']) . "' class='volunteer-link'>" . htmlspecialchars($row['FirstName'] . " " . $row['LastName']) . "</a></td>";
                            echo "<td>" . htmlspecialchars($row['Email']) . "<br>" . htmlspecialchars($row['Phone']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['CreatedAt']) . "</td>";
                            echo "<td><span class='status-badge $status_class'>" . htmlspecialchars($row['Status']) . "</span></td>";

                            if ($row['Status'] === 'Pending') {
                                echo "<td class='action-buttons'>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='app_id' value='" . htmlspecialchars($row['Application_ID']) . "'>
                                        <button type='submit' name='action' value='approve' class='btn-approve'>Approve</button>
                                    </form>
                                    <form method='POST' style='display: inline;'>
                                        <input type='hidden' name='app_id' value='" . htmlspecialchars($row['Application_ID']) . "'>
                                        <button type='submit' name='action' value='reject' class='btn-reject'>Reject</button>
                                    </form>
                                </td>";
                            } else {
                                echo "<td><em>Processed</em></td>";
                            }

                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='empty-state'>No applications found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <div class="action-card">
                <a href="volunteer_records.php" class="action-btn">Display All Records</a>
            </div>
        </section>

        <?php if (isset($_GET['email']) && $volunteer): ?>
            <section class="scheduling-section">
                <h3>Manage Volunteer:
                    <?php echo htmlspecialchars($volunteer['FirstName'] . ' ' . $volunteer['LastName']); ?>
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <div>
                        <h4>Schedule Activity</h4>
                        <form method="POST">
                            <input type="hidden" name="application_id" value="<?php echo $volunteer['Application_ID']; ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($volunteer['Email']); ?>">
                            <div>
                                <label>Date:</label>
                                <input type="date" name="assignment_date" required>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div>
                                    <label>Start Time:</label>
                                    <input type="time" name="start_time">
                                </div>
                                <div>
                                    <label>End Time:</label>
                                    <input type="time" name="end_time">
                                </div>
                            </div>

                            <div>
                                <label>Activity Type:</label>
                                <select name="activity_type" required>
                                    <option value="">Select Activity</option>
                                    <option value="Dog Walking">Dog Walking</option>
                                    <option value="Cat Socializing">Cat Socializing</option>
                                    <option value="Facility Cleaning">Facility Cleaning</option>
                                    <option value="Fostering">Fostering</option>
                                    <option value="Events">Events & Fundraising</option>
                                    <option value="Administration">Administrative Support</option>
                                </select>
                            </div>

                            <div>
                                <label>Description:</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>

                            <button type="submit" name="schedule_activity">Schedule Activity</button>
                        </form>
                    </div>

                    <div>
                        <h4>Record Volunteer Hours</h4>
                        <form method="POST">
                            <input type="hidden" name="application_id" value="<?php echo $volunteer['Application_ID']; ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($volunteer['Email']); ?>">
                            <div>
                                <label>Date Performed:</label>
                                <input type="date" name="date_performed" required>
                            </div>

                            <div>
                                <label>Hours:</label>
                                <input type="number" name="hours" step="0.5" min="0.5" required>
                            </div>

                            <div>
                                <label>Activity Type:</label>
                                <select name="activity_type" required>
                                    <option value="">Select Activity</option>
                                    <option value="Dog Walking">Dog Walking</option>
                                    <option value="Cat Socializing">Cat Socializing</option>
                                    <option value="Facility Cleaning">Facility Cleaning</option>
                                    <option value="Fostering">Fostering</option>
                                    <option value="Events">Events & Fundraising</option>
                                    <option value="Administration">Administrative Support</option>
                                </select>
                            </div>

                            <div>
                                <label>Description:</label>
                                <textarea name="description" rows="3"></textarea>
                            </div>

                            <button type="submit" name="record_hours">Record Hours</button>
                        </form>
                    </div>
                </div>

                <div style="margin-top: 40px;">
                    <h4>Scheduled Activities</h4>
                    <?php if (!empty($assignments)): ?>
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Activity</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($assignment['AssignmentDate'])); ?></td>
                                        <td>
                                            <?php if ($assignment['StartTime']): ?>
                                                <?php echo date('g:i A', strtotime($assignment['StartTime'])) . ' - ' . date('g:i A', strtotime($assignment['EndTime'])); ?>
                                            <?php else: ?>
                                                All Day
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['ActivityType']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['Description'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No activities scheduled yet.</p>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 40px;">
                    <h4>Recorded Hours</h4>
                    <?php if (!empty($hours)): ?>
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Activity</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hours as $hour): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($hour['DatePerformed'])); ?></td>
                                        <td><?php echo htmlspecialchars($hour['Hours']); ?> hours</td>
                                        <td><?php echo htmlspecialchars($hour['ActivityType']); ?></td>
                                        <td><?php echo htmlspecialchars($hour['Description'] ?? 'N/A'); ?></td>
                                        <td><?php echo $hour['Verified'] ? 'Verified' : 'Pending Verification'; ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="hours_id"
                                                    value="<?php echo htmlspecialchars($hour['Hours_ID']); ?>">
                                                <input type="hidden" name="email"
                                                    value="<?php echo htmlspecialchars($volunteer['Email']); ?>">
                                                <button type="submit" name="verify_hours" class="btn-verify"
                                                    style="padding: 8px 16px; border: none; border-radius: 6px; background: linear-gradient(45deg, #3b82f6, #60a5fa); color: white; cursor: pointer;">
                                                    <?php echo $hour['Verified'] ? 'Unverify' : 'Verify'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hours recorded yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <footer>
            <div class="footerHeader">
                <h3>Paw Prints - Where every paw matters</h3>
            </div>
            <hr>
            <p>© <?php echo date('Y'); ?> SPCA Makhanda</p>
        </footer>

        <script>
            document.querySelectorAll('.btn-verify').forEach(button => {
                button.addEventListener('click', function (e) {
                    if (!confirm('Are you sure you want to change the verification status?')) {
                        e.preventDefault();
                    }
                });
            });
        </script>
    </div>
</body>
<?php
$conn->close();
?>