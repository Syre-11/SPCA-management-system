<?php
// volunteer_dashboard.php - Dashboard for Volunteer Staff (SystemUser AccessLevel 'Volunteer')

include 'DatabaseConnection.php';


$error = null;

// New applications count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM VolunteerApplication WHERE Status = 'Pending'");
if ($stmt === false) {
    $error = "Failed to prepare the new applications count query: " . $conn->error;
} else {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $newApps = $row['count'];
}

// Total approved volunteers
if (!isset($error)) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM VolunteerApplication WHERE Status = 'Approved' AND IsHidden = 0");
    if ($stmt === false) {
        $error = "Failed to prepare the approved volunteers count query: " . $conn->error;
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalVolunteers = $row['count'];
    }
}

// Total verified hours this month
if (!isset($error)) {
    $stmt = $conn->prepare("SELECT SUM(Hours) as total FROM VolunteerHours WHERE Verified = 1 AND MONTH(DatePerformed) = MONTH(CURRENT_DATE()) AND YEAR(DatePerformed) = YEAR(CURRENT_DATE())");
    if ($stmt === false) {
        $error = "Failed to prepare the total hours query: " . $conn->error;
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalHours = $row['total'] ?? 0;
    }
}

// Data for chart: hours per activity this month
if (!isset($error)) {
    $stmt = $conn->prepare("SELECT ActivityType, SUM(Hours) as hours FROM VolunteerHours WHERE Verified = 1 AND MONTH(DatePerformed) = MONTH(CURRENT_DATE()) AND YEAR(DatePerformed) = YEAR(CURRENT_DATE()) GROUP BY ActivityType");
    if ($stmt === false) {
        $error = "Failed to prepare the chart data query: " . $conn->error;
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $chartData = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Recent pending applications for notifications
if (!isset($error)) {
    $stmt = $conn->prepare("SELECT * FROM VolunteerApplication WHERE Status = 'Pending' ORDER BY CreatedAt DESC LIMIT 5");
    if ($stmt === false) {
        $error = "Failed to prepare the recent applications query: " . $conn->error;
    } else {
        $stmt->execute();
        $result = $stmt->get_result();
        $recentApps = $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="adoption.css">
    <title>Volunteer Staff Dashboard</title>
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

        .user-profile {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
        }

        /* Summary Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s;
            border-top: 5px solid rgba(5, 111, 101, 0.7);
            /* Teal accent */
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            margin-bottom: 10px;
            color: #444;
        }

        .card p {
            font-size: 1.5rem;
            font-weight: bold;
            color: rgba(5, 111, 101, 0.7);
        }

        /* Recent Activity */
        .recent-activity {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .recent-activity h2 {
            margin-bottom: 15px;
            color: #444;
        }

        .recent-activity ul {
            list-style: none;
        }

        .recent-activity ul li {
            margin-bottom: 10px;
            font-size: 0.95rem;
            color: #444;
        }

        /* Charts */
        .charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .chart-placeholder {
            height: 200px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            color: #444;
            font-weight: bold;
            font-size: 1rem;
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

            .cards {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
</head>

<body>
    <nav>
        <div class="nav-logo">
            <a href="../frontPage.html"><img src="Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>Paw Prints</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="volunteer_management.php">Management</a></li>
                <li><a href="volunteer_records.php">Volunteer Records</a></li>
                <li><a href="../registerUser/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <header class="header">
            <h1>Welcome, Volunteer Staff</h1>
            <div class="user-profile">
                <i class="fas fa-hand-holding-heart"></i>Volunteer Dashboard
            </div>
        </header>

        <section>
            <div class="cards">
                <div class="card">
                    <h3>New Applications</h3>
                    <p><?php echo isset($error) ? 'N/A' : ($newApps ?? 0); ?></p>
                </div>
                <div class="card">
                    <h3>Total Volunteers</h3>
                    <p><?php echo isset($error) ? 'N/A' : ($totalVolunteers ?? 0); ?></p>
                </div>
                <div class="card">
                    <h3>Verified Hours This Month</h3>
                    <p><?php echo isset($error) ? 'N/A' : ($totalHours ?? 0); ?></p>
                </div>
            </div>
        </section>

        <section class="recent-activity">
            <h2>Notifications</h2>
            <?php if (isset($error)): ?>
                <p style="color: red;"><?php echo $error; ?></p>
            <?php elseif (isset($newApps)): ?>
                <p>New Volunteer Applications: <?php echo $newApps; ?></p>
                <?php if (!empty($recentApps)): ?>
                    <ul>
                        <?php foreach ($recentApps as $app): ?>
                            <li><?php echo htmlspecialchars($app['FirstName'] . ' ' . $app['LastName'] . ' (' . $app['Email'] . ') applied on ' . $app['CreatedAt']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent applications found.</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Unable to fetch application data.</p>
            <?php endif; ?>
        </section>

        <section class="charts">
            <?php if (isset($error)): ?>
                <div class="chart-placeholder"><?php echo $error; ?></div>
            <?php elseif (isset($chartData) && !empty($chartData)): ?>
                <canvas id="hoursChart" width="400" height="200"></canvas>
                <script>
                    const ctx = document.getElementById('hoursChart').getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode(array_column($chartData, 'ActivityType')); ?>,
                            datasets: [{
                                label: 'Verified Hours',
                                data: <?php echo json_encode(array_column($chartData, 'hours')); ?>,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                </script>
            <?php else: ?>
                <div class="chart-placeholder">No chart data available</div>
            <?php endif; ?>
        </section>

    </div>

    <footer>
        <div class="footerHeader">
            <h3>Paw Prints - Where every paw matters</h3>
        </div>
        <hr>
        <p>&copy; <?php echo date('Y'); ?> SPCA Makhanda</p>
    </footer>

    <script>
        // Simple clock implementation
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            const dateStr = now.toLocaleDateString();

            let clockElement = document.getElementById('clock');
            if (!clockElement) {
                clockElement = document.createElement('div');
                clockElement.id = 'clock';
                clockElement.style.position = 'fixed';
                clockElement.style.bottom = '20px';
                clockElement.style.right = '20px';
                clockElement.style.padding = '10px 15px';
                clockElement.style.background = 'rgba(5, 111, 101, 0.7)';
                clockElement.style.borderRadius = '8px';
                clockElement.style.color = '#ffffff';
                clockElement.style.zIndex = '1000';
                document.body.appendChild(clockElement);
            }
            clockElement.textContent = `${dateStr} | ${timeStr}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>

</html>