<?php
session_start();
include 'DatabaseConnection.php';

// TEMP: simulate a logged-in admin for testing
// $_SESSION['role'] = 'Admin';
// $_SESSION['user_id'] = 1;

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No report ID specified.");
}
$report_id = intval($_GET['id']);

// ✅ Fetch report with animal + reporter info
$sql = "
    SELECT cr.Report_ID, cr.ReportDate, cr.Description, cr.Location, cr.Status, cr.Urgent, cr.Evidence,
           a.Animal_Name, a.Animal_Breed,
           r.FirstName, r.Surname, r.CellNumber, r.Email,
           s.FirstName AS StaffFirst, s.Surname AS StaffSurname
    FROM crueltyreport cr
    LEFT JOIN animal a ON cr.FK_Animal_ID = a.Animal_ID
    LEFT JOIN reporter r ON cr.FK_Reporter_ID = r.Reporter_ID
    LEFT JOIN systemuser s ON cr.FK_SystemUser_ID = s.SystemUser_ID
    WHERE cr.Report_ID = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Prepare Failed: " . $conn->error);
}
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$report = $result->fetch_assoc();

if (!$report) {
    die("Report not found.");
}

$isStaff = isset($_SESSION['role']) && in_array($_SESSION['role'], ['Admin', 'Vet', 'Volunteer']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report #<?php echo $report['Report_ID']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            padding-top: 80px;
            background-color: #f4f4f9;
            min-height: 100vh;
        }

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

        nav img {
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
        }

        .nav-links {
            display: flex;
            align-items: center;
        }

        .nav-links ul {
            list-style: none;
            display: flex;
            gap: 35px;
        }

        .nav-links ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            font-weight: 600;
            padding: 8px 12px;
            transition: all 0.3s ease;
            border-radius: 4px;
        }

        .nav-links ul li a:hover {
            background-color: rgba(5, 111, 101, 0.7);
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

        h2 {
            text-align: center;
            margin: 20px 0;
            color: #444;
            font-size: 1.6rem;
        }

        .report-block {
            width: 95%;
            max-width: 1400px;
            margin: 20px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .reports-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
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

        .profile-section {
            margin-top: 25px;
        }

        .back-btn, .follow-up-btn {
            display: inline-block;
            margin: 20px 10px 0 0;
            padding: 10px 20px;
            background: rgba(5, 111, 101, 0.7);
            color: white;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: 0.3s;
        }

        .back-btn:hover, .follow-up-btn:hover {
            background: white;
            color: rgba(5, 111, 101, 0.7);
            border: 2px solid rgba(5, 111, 101, 0.7);
        }

        footer {
            background-color: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 40px 20px 20px;
            text-align: center;
            margin-top: 60px;
        }

        .footerHeader {
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        footer hr {
            border: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
            margin: 20px 0;
        }

        footer p {
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <nav>
        <div class="nav-logo">
            <a href="home.php"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>Makhanda SPCA</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="admin_dashboard.php">Home</a></li>
                <li><a href="Viewallreprts.php">All Reports</a></li>
                <li><a href="#">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="report-block">
        <h2>Report Details</h2>
        <table class="reports-table">
            <thead>
                <tr>
                    <th>Animal</th>
                    <th>Location</th>
                    <th>Description</th>
                    <th>Report Date</th>
                    <th>Status</th>
                    <th>Urgent</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $report['Animal_Name']; ?> (<?php echo $report['Animal_Breed']; ?>)</td>
                    <td><?php echo $report['Location']; ?></td>
                    <td><?php echo $report['Description']; ?></td>
                    <td><?php echo $report['ReportDate']; ?></td>
                    <td><?php echo $report['Status']; ?></td>
                    <td><?php echo $report['Urgent'] ? "Yes" : "No"; ?></td>
                </tr>
            </tbody>
        </table>

        <div class="profile-section">
            <h2>Personal Information</h2>
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $report['FirstName'] ? htmlspecialchars($report['FirstName'].' '.$report['Surname']) : 'Anonymous'; ?></td>
                        <td><?php echo $report['Email'] ? htmlspecialchars($report['Email']) : 'Not provided'; ?></td>
                        <td><?php echo $report['CellNumber'] ? htmlspecialchars($report['CellNumber']) : 'Not provided'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="links">
            <a href="follow-up.php?id=<?php echo $report['Report_ID']; ?>" class="follow-up-btn">Manage</a>
            <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
            <a href="Viewallreports.php" class="back-btn">All Reports</a>
        </div>
    </div>

</body>
<footer>
    <div class="footerHeader">
        <h3>Paw Prints - Where every paw matters</h3>
    </div>
    <hr>
    <p>© <?php echo date('Y'); ?> SPCA Makhanda</p>
</footer>
</html>

