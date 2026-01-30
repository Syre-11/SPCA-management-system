<?php
include 'DatabaseConnection.php';
// cruelty_management.php - Admin panel to manage cruelty reports (approve/reject)
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$error = null;
$success = null;

// Handle AdminStatus updates (Pending / Approved / Rejected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);
    $action = $_POST['action']; // Pending, Approved, Rejected

    $stmt = $conn->prepare("UPDATE crueltyreport SET AdminStatus = ? WHERE Report_ID = ?");
    if ($stmt) {
        $stmt->bind_param("si", $action, $report_id);
        if ($stmt->execute()) {
            $success = "Report #$report_id updated to '$action' successfully.";
        } else {
            $error = "Failed to update report: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// Handle status filter (all, Pending, Approved, Rejected)
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$where_clause = "WHERE cr.deleted = 0"; // Only show non-deleted reports

if ($status_filter !== 'all') {
    $status_filter_escaped = $conn->real_escape_string($status_filter);
    $where_clause .= " AND cr.AdminStatus = '$status_filter_escaped'";
}

// Fetch reports with reporter info
$sql = "SELECT cr.Report_ID, r.FirstName, r.Surname, r.CellNumber, r.Email,
               cr.ReportDate, cr.Description, cr.Location, cr.FK_Animal_ID,
               cr.evidence, cr.Status AS CaseStatus, cr.AdminStatus AS Status
        FROM crueltyreport cr
        JOIN reporter r ON cr.FK_Reporter_ID = r.Reporter_ID
        $where_clause
        ORDER BY cr.ReportDate DESC";

$result = $conn->query($sql);
$reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cruelty Management - Makhanda SPCA</title>
    <link rel="stylesheet" href="adoption.css">
    <style>
        /* Reset & Basic Styling */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
        body { display: flex; min-height: 100vh; flex-direction: column; padding-top: 80px; background-color: #f9f9f9; }

        /* Navigation */
/* TOP SECTION 1: Main Navigation Bar */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 5%;
    background-color: #1ae3ccb7;
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

/* Logo in navigation */
nav img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
}

/* Organization name next to logo */
.nav-logo h2 {
    color: white;
    font-size: 18px;
    font-weight: 600;
    margin: 0;
}

/* Navigation links container */
.nav-links {
    display: flex;
    align-items: center;
}

.nav-links ul {
    list-style-type: none;
    display: flex;
    gap: 35px;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-links ul li {
    position: relative;
}

.nav-links ul li a {
    color: white;
    text-decoration: none;
    font-size: 17px;
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

/* Dropdown */
nav .nav-links ul li ul {
 position: absolute;
    top: 100%;
    left: 0;
    background: rgba(4, 230, 196, 0.667);
    min-width: 180px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    border-radius: 6px;
    padding: 8px 0;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

nav .nav-links ul li:hover ul {
     opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

nav .nav-links ul li ul li {
    display: block;
    padding: 0;
}

nav .nav-links ul li ul li a {
     display: block;
    color: white;
    padding: 12px 20px;
    font-size: 14px;
    border-radius: 0;
}

        /* Main content */
        .main-content { flex:1; padding:20px; }
        .header h1 { font-size:1.6rem; color:#444; margin-bottom:20px; }

        /* Status filter buttons */
        .status-filter { display:flex; justify-content:center; margin-bottom:20px; gap:10px; }
        .status-btn { padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; text-decoration:none; display:inline-block; color:white; transition:all 0.3s ease;}
        .status-btn.all { background: linear-gradient(45deg, #6b7280, #9ca3af); }
        .status-btn.pending { background: linear-gradient(45deg, #f59e0b, #fbbf24); }
        .status-btn.approved { background: linear-gradient(45deg, #10b981, #34d399); }
        .status-btn.rejected { background: linear-gradient(45deg, #ef4444, #f87171); }
        .status-btn.active { transform:translateY(-2px); box-shadow:0 4px 8px rgba(0,0,0,0.2); }

        /* Status badges */
        .status-badge { padding:5px 12px; border-radius:20px; font-size:12px; font-weight:bold; }
        .status-pending { background:#fef3c7; color:#d97706; }
        .status-approved { background:#d1fae5; color:#059669; }
        .status-rejected { background:#fee2e2; color:#dc2626; }

        /* Table styling */
        .applications-table { width:95%; max-width:1400px; margin:20px auto; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 5px 15px rgba(0,0,0,0.1); }
        .applications-table th { background: rgba(5,111,101,0.7); color:white; font-size:15px; font-weight:600; padding:15px; text-align:left; }
        .applications-table td { padding:12px 15px; border-bottom:1px solid #e5e7eb; font-size:14px; vertical-align:middle; }
        .applications-table tr:hover { background:#f3f4f6; }

        .action-buttons { display:flex; gap:8px; }
        .btn-approve, .btn-reject { padding:8px 16px; border:none; border-radius:6px; font-size:13px; font-weight:bold; cursor:pointer; transition:all 0.3s ease; color:white; }
        .btn-approve { background:linear-gradient(45deg,#10b981,#34d399); }
        .btn-approve:hover { background:white; color:#10b981; border:2px solid #10b981; }
        .btn-reject { background:linear-gradient(45deg,#ef4444,#f87171); }
        .btn-reject:hover { background:white; color:#ef4444; border:2px solid #ef4444; }

        .empty-state { text-align:center; padding:40px; color:#6b7280; font-style:italic; }

        /* Notifications */
        .notification { text-align:center; font-size:1.1rem; margin-bottom:20px; padding:10px; border-radius:6px; }
        .success { color:green; background:#f0fdf4; }
        .error { color:red; background:#fef2f2; }

        /* Footer */
        /* Footer */
footer {
    background-color: rgba(5, 111, 101, 0.7);
    color: white;
    padding: 30px 10px;
    font-family: Arial, sans-serif;
}

.footerHeader {
    text-align: center;
    margin-bottom: 20px;
}

.footer-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
}

.footer-section {
    flex: 1;
    min-width: 200px;
}

.footer-section h3 {
    margin-bottom: 10px;
}

.footer-section p,
.footer-section a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 10px ;
}

.footer-section a:hover {
    text-decoration: underline;
}
    </style>
</head>
<body>
    <nav>
        <div class="nav-logo">
            <a href="#"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>SPCA Cruelty Portal</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="Viewallreports.php">Reports</a></li>
                <li><a href="../registerUser/Logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <header class="header">
            <h1>Cruelty Management</h1>
        </header>

<!-- Notifications -->
<?php if(isset($success)) echo "<div class='notification success'>$success</div>"; ?>
<?php if(isset($error)) echo "<div class='notification error'>$error</div>"; ?>

<!-- Status Filter Buttons -->
<div class="status-filter">
    <a href="?status=all" class="status-btn all <?php echo $status_filter==='all'?'active':''; ?>">All Reports</a>
    <a href="?status=Pending" class="status-btn pending <?php echo $status_filter==='Pending'?'active':''; ?>">Pending</a>
    <a href="?status=Approved" class="status-btn approved <?php echo $status_filter==='Approved'?'active':''; ?>">Approved</a>
    <a href="?status=Rejected" class="status-btn rejected <?php echo $status_filter==='Rejected'?'active':''; ?>">Rejected</a>
</div>

<!-- Reports Table -->
<table class="applications-table">
    <thead>
        <tr>
            <th>Report ID</th>
            <th>Reporter Name</th>
            <th>Contact Info</th>
            <th>Report Date</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(!empty($reports)): ?>
            <?php foreach($reports as $report):
                $status_class = '';
                if($report['Status']==='Pending') $status_class='status-pending';
                if($report['Status']==='Approved') $status_class='status-approved';
                if($report['Status']==='Rejected') $status_class='status-rejected';
            ?>
            <tr>
                <td><?php echo $report['Report_ID']; ?></td>
                <td><?php echo htmlspecialchars($report['FirstName'] . ' ' . $report['Surname']); ?></td>
                <td><?php echo htmlspecialchars($report['Email'] . ' / ' . $report['CellNumber']); ?></td>
                <td><?php echo date('M j, Y', strtotime($report['ReportDate'])); ?></td>
                <td><span class="status-badge <?php echo $status_class; ?>"><?php echo $report['Status']; ?></span></td>
                <td class="action-buttons">
                    <?php if($report['Status']==='Pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="report_id" value="<?php echo $report['Report_ID']; ?>">
                            <button type="submit" name="action" value="Approved" class="btn-approve">Approve</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="report_id" value="<?php echo $report['Report_ID']; ?>">
                            <button type="submit" name="action" value="Rejected" class="btn-reject">Reject</button>
                        </form>
                    <?php else: ?>
                        <em>Processed</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" class="empty-state">No reports found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<footer>
    <div class="footerHeader">
        <h3>Paw Prints - Where every paw matters</h3>
    </div>
    <hr>
    <p>© <?php echo date('Y'); ?> SPCA Makhanda</p>
</footer>

<?php $conn->close(); ?>
