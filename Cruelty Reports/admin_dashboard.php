<?php
include 'DatabaseConnection.php'; // DB connection
// --- Summary counts ---
// Total animals
$totalAnimals = $conn->query("SELECT COUNT(*) AS count FROM animal")->fetch_assoc()['count'];

// Open cruelty reports (status = Active)
$openReports = $conn->query("
    SELECT COUNT(*) AS count 
    FROM crueltyreport 
    WHERE Status IN ('New', 'Open', 'In Progress')
    AND deleted = 0
")->fetch_assoc()['count'];

// Pending adoptions
$pendingAdoptions = $conn->query("SELECT COUNT(*) AS count FROM adoptionapplication WHERE Application_Status='Pending'")->fetch_assoc()['count'];

// Monthly donation total
$donations = $conn->query("
    SELECT IFNULL(SUM(Amount),0) AS total 
    FROM alldonations 
    WHERE MONTH(DonationDate) = MONTH(CURDATE()) 
      AND YEAR(DonationDate) = YEAR(CURDATE())
")->fetch_assoc()['total'];

// --- Recent activity ---
$recentActivity = [];

$sqlRecent = "
    (SELECT 'Cruelty Report' AS type, Report_ID AS ref, ReportDate AS date FROM crueltyreport ORDER BY ReportDate DESC LIMIT 2)
    UNION ALL
    (SELECT 'Animal Intake' AS type, Animal_ID AS ref, Animal_Arrival_Date AS date FROM animal ORDER BY Animal_Arrival_Date DESC LIMIT 2)
    UNION ALL
    (SELECT 'Donation' AS type, donor_id AS ref, DonationDate AS date FROM alldonations ORDER BY DonationDate DESC LIMIT 2)
    UNION ALL
    (SELECT 'Adoption Application' AS type, Application_ID AS ref, Application_Date AS date FROM adoptionapplication ORDER BY Application_Date DESC LIMIT 2)
    ORDER BY date DESC
    LIMIT 5
";

$result = $conn->query($sqlRecent);

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Staff Dashboard</title>
  <link rel="stylesheet" href="admin_dashboards.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
  <!-- Navigation Bar -->
  <nav class="navbar">
    <a href="../frontPage.html" class="logo">
      <img src="Paw prints logo.png" alt="Logo">
    </a>
    <div class="nav-links">
      <ul>
        <li><a href="Cruelty Manage.php" class="active">Reports</a></li>
        <li><a href="../animal_intake_system/animal_intakeSite.php">Animals</a></li>
        <li><a href="../Adopt and Volunteer/adoption.management.php">Adoptions</a></li>
        <li><a href="../DONATIONS/displayAllDonors.php">Donations</a></li>
        <li><a href="../Adopt and Volunteer/volunteer_management.php">Volunteers</a></li>
        <li><a href="../registerUser/display_users.php">User</a></li>
        <li><a href="../registerUser/logout.php">Logout</a></li>
      </ul>
    </div>
  </nav>
  <!-- Main Content -->
  <div class="main-content">
    <div class="header">
      <h1>Welcome, Admin Staff</h1>
      <div class="user-profile">
        <i class="fas fa-user-cog"></i>Admin Dashboard
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="cards">
      <div class="card">
        <h3>Animals in Shelter</h3>
        <p><?php echo $totalAnimals; ?></p>
      </div>
      <div class="card">
        <h3>Pending Cruelty Reports</h3>
        <p><?php echo $openReports; ?></p>
      </div>
      <div class="card">
        <h3>Pending Adoptions</h3>
        <p><?php echo $pendingAdoptions; ?></p>
      </div>
      <div class="card">
        <h3>Donations (This Month)</h3>
        <p>R <?php echo number_format($donations, 2); ?></p>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="recent-activity">
      <h2>Recent Activity</h2>
      <ul>
        <?php if (!empty($recentActivity)): ?>
          <?php foreach ($recentActivity as $activity): ?>
            <li>
              <?php
              echo "🔹 {$activity['type']} – Ref #{$activity['ref']} (" . date("d M Y", strtotime($activity['date'])) . ")";
              ?>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li>No recent activity found.</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Charts (placeholders for now) -->
    <div class="charts">
      <div class="chart-container">
        <h3 class="chart-header">Adoptions Trend</h3>
        <canvas id="adoptionsChart"></canvas>
      </div>

      <div class="chart-container">
        <h3 class="chart-header">Donations Breakdown</h3>
        <canvas id="donationsChart"></canvas>
      </div>

      <div class="chart-container">
        <h3 class="chart-header">Kennel Occupancy</h3>
        <canvas id="kennelChart"></canvas>
      </div>
    </div>
    <script>
      // Adoptions Trend Chart
      const ctxAdoptions = document.getElementById('adoptionsChart').getContext('2d');
      const adoptionsChart = new Chart(ctxAdoptions, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'], // example months
          datasets: [{
            label: 'Number of Adoptions',
            data: [12, 19, 7, 15, 20, 25], // sample data, replace with real data
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true }
          }
        }
      });

      // Donations Breakdown Chart (Pie Chart)
      const ctxDonations = document.getElementById('donationsChart').getContext('2d');
      const donationsChart = new Chart(ctxDonations, {
        type: 'pie',
        data: {
          labels: ['Online', 'In-Person', 'Events', 'Others'],
          datasets: [{
            label: 'Donations Breakdown',
            data: [55, 25, 15, 5], // sample data, replace with real data
            backgroundColor: [
              'rgba(255, 99, 132, 0.7)',
              'rgba(255, 206, 86, 0.7)',
              'rgba(75, 192, 192, 0.7)',
              'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: 'white',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true
        }
      });

      // Kennel Occupancy Chart (Bar Chart)
      const ctxKennel = document.getElementById('kennelChart').getContext('2d');
      const kennelChart = new Chart(ctxKennel, {
        type: 'bar',
        data: {
          labels: ['Kennel A', 'Kennel B', 'Kennel C', 'Kennel D'],
          datasets: [{
            label: 'Occupied Kennels',
            data: [8, 12, 5, 10], // sample data, replace with real data
            backgroundColor: 'rgba(255, 159, 64, 0.7)',
            borderColor: 'rgba(255, 159, 64, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    </script>
</body>
<footer>
  <div class="footerHeader">
    <h3>Paw Prints - Where every paw matters</h3>
  </div>
  <hr>
  <p>&copy; <?php echo date('Y'); ?> SPCA Makhanda</p>
</footer>

</html>