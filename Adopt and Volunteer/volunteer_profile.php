<?php
// volunteer_profile.php - View volunteer profile details

include 'DatabaseConnection.php';


// Verify connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



$error = null;
$volunteer = null;
$assignments = [];
$hours = [];

if (isset($_GET['email']) && !empty($_GET['email'])) {
    $email = $conn->real_escape_string($_GET['email']);

    // Preserve filter parameters
    $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
    $hidden_filter = isset($_GET['show_hidden']) ? $_GET['show_hidden'] : 'active';
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';

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
            $stmt = $conn->prepare("SELECT DatePerformed, Hours, ActivityType, Description, Verified 
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
        } else {
            $error = "No volunteer found with email: $email";
        }
    }
} else {
    $error = "No email parameter provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Profile - Makhanda SPCA</title>
    <style>
        /* General styling */
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            padding-top: 80px; /* Offset fixed nav */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 1.6rem;
            color:#444;
        }

        .notification {
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 6px;
        }

        .error {
            color: red;
            background: #fef2f2;
        }

        /* Profile card */
        .profile-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-card h2 {
            color:#444;
            margin-bottom: 15px;
        }

        .profile-details {
            margin-bottom: 20px;
        }

        .profile-details p {
            margin: 5px 0;
            color: #444;
        }

        .profile-details strong {
            color:#444;
        }

        /* Sections */
        .section {
            margin-top: 20px;
        }

        .section h3 {
            color:#444;
            margin-bottom: 10px;
        }

        .assignments-table,
        .hours-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .assignments-table th,
        .hours-table th {
            background:rgba(5, 111, 101, 0.7);
            color: white;
            padding: 10px;
            text-align: left;
        }

        .assignments-table td,
        .hours-table td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
        }

        .assignments-table tr:hover,
        .hours-table tr:hover {
            background-color: #f3f4f6;
        }

        .empty-state {
            text-align: center;
            padding: 20px;
            color: #6b7280;
            font-style: italic;
        }

        /* Navigation */
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

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-logo h2 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }

        .nav-links ul {
            list-style: none;
            display: flex;
            gap: 35px;
            margin: 0;
            padding: 0;
        }

        .nav-links ul li a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .nav-links ul li a:hover {
            background-color: rgba(132, 224, 175, 0.809);
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

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            background: #fff;
            border-top: 1px solid #e5e7eb;
        }

        .footerHeader h3 {
            color: #1a237e;
            margin-bottom: 10px;
        }

        footer p {
            color: #6b7280;
            margin: 0;
        }
    </style>
</head>
<body>
    <a href="volunteer_records.php?status=<?php echo urlencode($status_filter); ?>&show_hidden=<?php echo urlencode($hidden_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="back-btn">← Back to Volunteer Records</a>

    <div class="header">
        <h1>Volunteer Profile</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="notification error"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif ($volunteer): ?>
        <div class="profile-card">
            <h2>Volunteer Details</h2>
            <div class="profile-details">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($volunteer['FirstName'] . ' ' . $volunteer['LastName']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($volunteer['Email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($volunteer['Phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($volunteer['Address'] ?? 'N/A'); ?></p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($volunteer['Experience'] ?? 'N/A'); ?></p>
                <p><strong>Interests:</strong> <?php echo htmlspecialchars($volunteer['Interests'] ?? 'N/A'); ?></p>
                <p><strong>Availability:</strong> <?php echo htmlspecialchars($volunteer['Availability'] ?? 'N/A'); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($volunteer['Status']); ?></p>
                <p><strong>Application Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($volunteer['CreatedAt']))); ?></p>
            </div>

            <div class="section">
                <h3>Scheduled Activities</h3>
                <?php if (!empty($assignments)): ?>
                    <table class="assignments-table">
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
                                    <td><?php echo htmlspecialchars(date('M j, Y', strtotime($assignment['AssignmentDate']))); ?></td>
                                    <td>
                                        <?php 
                                        if ($assignment['StartTime'] && $assignment['EndTime']) {
                                            echo htmlspecialchars(date('g:i A', strtotime($assignment['StartTime'])) . ' - ' . date('g:i A', strtotime($assignment['EndTime'])));
                                        } else {
                                            echo 'All Day';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignment['ActivityType']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['Description'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No scheduled activities found.</div>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>Volunteered Hours</h3>
                <?php if (!empty($hours)): ?>
                    <table class="hours-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Hours</th>
                                <th>Activity</th>
                                <th>Description</th>
                                <th>Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hours as $hour): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('M j, Y', strtotime($hour['DatePerformed']))); ?></td>
                                    <td><?php echo htmlspecialchars($hour['Hours']); ?> hours</td>
                                    <td><?php echo htmlspecialchars($hour['ActivityType']); ?></td>
                                    <td><?php echo htmlspecialchars($hour['Description'] ?? 'N/A'); ?></td>
                                    <td><?php echo $hour['Verified'] ? 'Yes' : 'No'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">No volunteered hours recorded.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="notification error">No volunteer data available.</div>
    <?php endif; ?>
</body>
<?php
$conn->close();
?>