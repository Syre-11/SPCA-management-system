<?php
session_start();
include 'DatabaseConnection.php';


// Check report ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No report ID specified.");
}
$report_id = intval($_GET['id']);

// Fetch report summary
$sql = "
    SELECT cr.Report_ID, cr.Status, cr.Urgent,
           a.Animal_Name, a.Animal_Breed
    FROM crueltyreport cr
    LEFT JOIN animal a ON cr.FK_Animal_ID = a.Animal_ID
    WHERE cr.Report_ID = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$report) die("Report not found.");

// Handle follow-up form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update report
    if (isset($_POST['updateReport'])) {
        $status = $_POST['status'];
        $urgent = isset($_POST['urgent']) ? 1 : 0;
        $staff_id = !empty($_POST['assignedStaff']) ? intval($_POST['assignedStaff']) : null;

        $update = $conn->prepare("UPDATE crueltyreport SET Status=?, Urgent=?, FK_SystemUser_ID=? WHERE Report_ID=?");
        if (!$update) die("Update prepare failed: " . $conn->error);
        $update->bind_param("siii", $status, $urgent, $staff_id, $report_id);
        $update->execute();
        $update->close();
    }

    // Add follow-up note
    if (isset($_POST['addNote'])) {
        $note = $_POST['note'];
        $user_id = $_SESSION['user_id'];

        $insert = $conn->prepare("INSERT INTO followup_notes (Report_ID, User_ID, Note, Created_At) VALUES (?, ?, ?, NOW())");
        if (!$insert) die("Insert prepare failed: " . $conn->error);
        $insert->bind_param("iis", $report_id, $user_id, $note);
        $insert->execute();
        $insert->close();
    }

    header("Location: Follow-up.php?id=$report_id");
    exit;
}

// Fetch staff list
$staffList = $conn->query("SELECT SystemUser_ID, firstName, surname FROM systemuser WHERE position = 'Administrative Staff'");

// Fetch follow-up notes
$notesStmt = $conn->prepare("
    SELECT fn.Note, fn.Created_At, u.firstName, u.surname
    FROM followup_notes fn
    JOIN systemuser u ON fn.User_ID = u.SystemUser_ID
    WHERE fn.Report_ID = ?
    ORDER BY fn.Created_At DESC
");
if (!$notesStmt) die("Prepare failed: " . $conn->error);
$notesStmt->bind_param("i", $report_id);
$notesStmt->execute();
$notes = $notesStmt->get_result();
$notesStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Follow-Up Report #<?php echo $report['Report_ID']; ?></title>
    <link rel="stylesheet" href="follow-up.css">
</head>
<body>
    <nav class="navbar">
    <a href="Homepage.html" class="logo">
        <img src="Paw prints logo.png" alt="Logo">
    </a>
    <div class="nav-links">
        <ul>
            <li><a href="admin_dashboard.php">Home</a></li>
            <li><a href="Viewallreports.php">All Reports</a></li>
            <li><a href="">Logout</a></li>
        </ul>
    </div>
</nav>
<div class="report-block">
    <h2>Report Details</h2>
    <div class="report-summary">
        <p><strong>Animal:</strong> <?php echo $report['Animal_Name'] . ' (' . $report['Animal_Breed'] . ')'; ?></p>
        <p><strong>Status:</strong> <?php echo $report['Status']; ?></p>
        <p><strong>Urgent:</strong> <?php echo $report['Urgent'] ? "Yes" : "No"; ?></p>
    </div>

    <h2>Update Report</h2>
    <form method="POST">
        <label>Status:</label>
        <select name="status">
            <option value="Open" <?php if ($report['Status']=="Open") echo "selected"; ?>>New</option>
            <option value="In Progress" <?php if ($report['Status']=="In Progress") echo "selected"; ?>>Active</option>
            <option value="Resolved" <?php if ($report['Status']=="Resolved") echo "selected"; ?>>Resolved</option>
        </select>

        <label>Urgent:</label>
        <input type="checkbox" name="urgent" <?php if ($report['Urgent']) echo "checked"; ?>>

        <label>Assign Staff:</label>
        <select name="assignedStaff">
            <option value="">-- Select Staff --</option>
            <?php while ($s = $staffList->fetch_assoc()): ?>
                <option value="<?php echo $s['SystemUser_ID']; ?>">
                    <?php echo $s['firstName'].' '.$s['surname']; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" name="updateReport">Save Changes</button>
    </form>

    <h2>Add Follow-Up Note</h2>
    <form method="POST">
        <textarea name="note" rows="3" required></textarea>
        <button type="submit" name="addNote">Add Note</button>
    </form>

    <h2>Follow-Up Timeline</h2>
    <div class="notes-section">
        <?php if ($notes->num_rows > 0): ?>
            <?php while ($n = $notes->fetch_assoc()): ?>
                <div class="note-entry">
                    <strong><?php echo $n['firstName'].' '.$n['surname']; ?></strong>
                    <em><?php echo $n['Created_At']; ?></em>
                    <p><?php echo htmlspecialchars($n['Note']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No follow-up notes yet.</p>
        <?php endif; ?>
    </div>

    <div class="links">
        <a href="viewReport.php?id=<?php echo $report['Report_ID']; ?>" class="back-btn">Back to Report</a>
        <a href="admin_dashboard.php" class="back-btn">Back to Dashboard</a>
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