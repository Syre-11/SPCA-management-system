.<?php
// Database connection and queries
$serverName = "";
$user = "";
$password = "";
$database = "";

// Create connection
$conn = new mysqli($serverName, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection to server and database failed: " . $conn->connect_error);
}

// Fetch data from database
$veterinarians = [];
$animals = [];
$records = [];

// Fetch veterinarians
$vet_query = "SELECT * FROM veterinarians LIMIT 3";
$vet_result = $conn->query($vet_query);
if ($vet_result && $vet_result->num_rows > 0) {
    while ($row = $vet_result->fetch_assoc()) {
        $veterinarians[] = $row;
    }
}

// Fetch animals
$animal_query = "SELECT * FROM animal ORDER BY Animal_Arrival_Date DESC LIMIT 3";
$animal_result = $conn->query($animal_query);
if ($animal_result && $animal_result->num_rows > 0) {
    while ($row = $animal_result->fetch_assoc()) {
        $animals[] = $row;
    }
}

// Fetch medical records
$record_query = "SELECT * FROM medicalrecords ORDER BY procedure_date DESC LIMIT 3";
$record_result = $conn->query($record_query);
if ($record_result && $record_result->num_rows > 0) {
    while ($row = $record_result->fetch_assoc()) {
        $records[] = $row;
    }
}

// Get counts
$animal_count_query = "SELECT COUNT(*) as count FROM animal";
$animal_count_result = $conn->query($animal_count_query);
$animal_count = $animal_count_result ? $animal_count_result->fetch_assoc()['count'] : 0;

$vet_count_query = "SELECT COUNT(*) as count FROM veterinarians";
$vet_count_result = $conn->query($vet_count_query);
$vet_count = $vet_count_result ? $vet_count_result->fetch_assoc()['count'] : 0;

// Pending alerts
$pending_alerts = [];
$alert_query = "
    SELECT m.Animal_ID, m.status, a.Animal_Name, a.Animal_Species, m.next_due_date AS Due_Date, m.notes
    FROM medicalrecords m
    JOIN animal a ON m.Animal_ID = a.Animal_ID
    WHERE m.status ='Pending'
    ORDER BY m.next_due_date ASC
    LIMIT 3";

$alert_result = $conn->query($alert_query);
if (!$alert_result) {
    die("Alert query failed: " . $conn->error);
}
if ($alert_result->num_rows > 0) {
    while ($row = $alert_result->fetch_assoc()) {
        $pending_alerts[] = $row;
    }
}

// Procedure Type Counts
$procedure_result = $conn->query("
    SELECT procedure_type, COUNT(*) AS count 
    FROM medicalrecords 
    GROUP BY procedure_type 
    ORDER BY count DESC
");

$procedure_labels = [];
$procedure_data = [];
if ($procedure_result && $procedure_result->num_rows > 0) {
    while ($row = $procedure_result->fetch_assoc()) {
        $procedure_labels[] = $row['procedure_type'];
        $procedure_data[] = (int) $row['count'];
    }
}

// Procedures per Vet
$vet_result = $conn->query("
    SELECT v.vet_id, v.vet_first_name, v.vet_last_name, COUNT(m.Record_ID) AS procedure_count
    FROM veterinarians v
    LEFT JOIN medicalrecords m ON v.vet_id = m.vet_id
    WHERE v.is_active = 1
    GROUP BY v.vet_id
    ORDER BY procedure_count DESC
");

$vet_names = [];
$vet_procedures = [];
if ($vet_result && $vet_result->num_rows > 0) {
    while ($row = $vet_result->fetch_assoc()) {
        $vet_names[] = 'Dr. ' . $row['vet_first_name'] . ' ' . $row['vet_last_name'];
        $vet_procedures[] = (int) $row['procedure_count'];
    }
}

// Total Cost Per Month
$cost_result = $conn->query("
    SELECT DATE_FORMAT(procedure_date, '%Y-%m') AS month, SUM(cost) AS total_cost
    FROM medicalrecords
    GROUP BY month
    ORDER BY month ASC
");

$months = [];
$cost_data = [];
if ($cost_result && $cost_result->num_rows > 0) {
    while ($row = $cost_result->fetch_assoc()) {
        $months[] = $row['month'];
        $cost_data[] = (float) $row['total_cost'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinary Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            padding-top: 80px;
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

        /* Dashboard Sections */
        .dashboard-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .dashboard-section h2 {
            margin-bottom: 15px;
            color: #444;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Sticky Notes Container */
        .sticky-notes-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .sticky-note {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            min-height: 300px;
        }

        .sticky-note.charts-sticky-note {
            min-width: 900px;
        }

        .sticky-note h2 {
            margin-bottom: 15px;
            color: #444;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        /* Alert Items */
        .alert-item {
            padding: 10px;
            margin-bottom: 10px;
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            border-radius: 4px;
        }

        /* Vet Cards */
        .vet-card {
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f1f8e9;
            border-radius: 8px;
            text-align: center;
        }

        .vet-card h3 {
            margin-bottom: 10px;
            color: #444;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .quick-actions button {
            padding: 12px;
            background-color: rgba(5, 111, 101, 0.7);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .quick-actions button:hover {
            background-color: rgba(5, 111, 101, 1);
        }

        /* Animal of the Month */
        .animal-of-month {
            text-align: center;
            padding: 15px;
        }

        .animal-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }

        /* Charts Container */
        .charts-container {
            display: flex;
            flex-direction: row;
            gap: 15px;
            justify-content: space-between;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        canvas {
            max-width: 300px;
            width: 100%;
            height: 250px;
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

        .nav-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-logo img {
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
            font-weight: 500;
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

        /* Footer Section */
        footer {
            background-color: rgba(5, 111, 101, 0.7);
            color: white;
            padding: 40px 20px;
            position: relative;
            margin-top: 60px;
        }

        .footerHeader {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
        }

        footer hr {
            margin: 10px 0;
            border: 0;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }

            .cards {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }

            .sticky-notes-container {
                grid-template-columns: 1fr;
            }

            .sticky-note.charts-sticky-note {
                min-width: 0;
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

            .charts-container {
                flex-direction: column;
                gap: 20px;
            }

            canvas {
                max-width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-logo">
            <a href="#"><img src="../Adopt and Volunteer/Paw prints logo.png" alt="Makhanda SPCA Logo"></a>
            <h2>SPCA Veterinary Portal</h2>
        </div>
        <div class="nav-links">
            <ul>
                <li><a href="medicalrecords.php">Create Medical Records</a></li>
                <li><a href="display.php">View Records</a></li>
                <li><a href="../registerUser/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <header class="header">
            <h1>Welcome, Veterinary Staff</h1>
            <div class="user-profile">
                <i class="fas fa-user-md"></i> Veterinary Dashboard
            </div>
        </header>

        <section>
            <div class="cards">
                <div class="card">
                    <h3>Total Animals</h3>
                    <p><?php echo $animal_count; ?></p>
                </div>
                <div class="card">
                    <h3>Total Veterinarians</h3>
                    <p><?php echo $vet_count; ?></p>
                </div>
                <div class="card">
                    <h3>Pending Procedures</h3>
                    <p><?php echo count($pending_alerts); ?></p>
                </div>
            </div>
        </section>

        <div class="sticky-notes-container">
            <div class="sticky-note">
                <h2><i class="fas fa-exclamation-triangle"></i> Alerts & Reminders</h2>
                <?php if (!empty($pending_alerts)): ?>
                    <?php foreach ($pending_alerts as $alert): ?>
                        <div class="alert-item">
                            <strong>💉 <?= htmlspecialchars($alert['Animal_Name']) ?></strong>
                            (<?= htmlspecialchars($alert['Animal_Species']) ?>)<br>
                            Status: <?= htmlspecialchars($alert['status']) ?><br>
                            Due: <?= htmlspecialchars($alert['Due_Date']) ?><br>
                            <strong>Notes:</strong> <?= htmlspecialchars($alert['notes']) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No pending alerts at this time.</p>
                <?php endif; ?>
            </div>

            <div class="sticky-note">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="quick-actions">
                    <a href="medicalrecords.php"><button>Add Medical Record</button></a>
                    <a href="display.php"><button>View And Update Records</button></a>
                </div>
            </div>

            <div class="sticky-note">
                <h2><i class="fas fa-user-md"></i> Our Veterinarians(Active)</h2>
                <?php if (!empty($veterinarians)): ?>
                    <?php foreach ($veterinarians as $vet): ?>
                        <div class="vet-card">
                            <h3><?= htmlspecialchars($vet['vet_first_name'] ?? 'Veterinarian Name'); ?></h3>
                            <p>📧 <?= htmlspecialchars($vet['email'] ?? 'email@vet.clinic.com'); ?></p>
                            <p>📱 <?= htmlspecialchars($vet['phone_number'] ?? '+27 XX XXX XXXX'); ?></p>
                            <p>🩺 Specialization: <?= htmlspecialchars($vet['specialization'] ?? 'General Practice'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="vet-card">
                        <div style="font-size: 3rem;">👨‍⚕️</div>
                        <h3>Bongane Mhlongo</h3>
                        <p>📧 bongane@vet.clinic.com</p>
                        <p>📱 +27 72 384 1952</p>
                        <p>🩺 Specialization: Small Animals</p>
                    </div>

                    <div class="vet-card">
                        <div style="font-size: 3rem;">👨‍⚕️</div>
                        <h3>Thabo Mabena</h3>
                        <p>📧 mabena@vetclinic.com</p>
                        <p>📱 +27 79 547 1865</p>
                        <p>🩺 Specialization: Exotic Pets</p>
                    </div>

                    <div class="vet-card">
                        <div style="font-size: 3rem;">👩‍⚕️</div>
                        <h3>Zanele Khumalo</h3>
                        <p>📧 khumalo@vetclinic.com</p>
                        <p>📱 +27 83 274 9617</p>
                        <p>🩺 Specialization: Vaccination</p>
                    </div>
                <?php endif; ?>
            </div>

         <div class="charts-container">
            <div style="text-align:center; max-width: 500px;">
                <h3>🩺 Types of Procedures Performed</h3>
                <canvas id="procedureChart"></canvas>
                <p><strong>Total Procedures:</strong> <span id="procedureTotal"></span></p>
            </div>

            
            <div style="text-align:center; max-width: 500px;">
                <h3>👨‍⚕️ Procedures Done by Veterinarians</h3>
                <canvas id="vetChart"></canvas>
                <p><strong>Total Veterinarian Procedures:</strong> <span id="vetTotal"></span></p>
            </div>

            <div style="text-align:center; max-width: 500px;">
                <h3>💰 Monthly Cost of Medical Procedures</h3>
                <canvas id="costChart"></canvas>
                <p><strong>Total Medical Cost:</strong> R<span id="costTotal"></span></p>
            </div>
        </div>


    
    </div>

    <script>
        // PHP arrays converted to JS
        const procedureLabels = <?php echo json_encode($procedure_labels); ?>;
        const procedureData = <?php echo json_encode($procedure_data); ?>;
        const vetNames = <?php echo json_encode($vet_names); ?>;
        const vetProcedures = <?php echo json_encode($vet_procedures); ?>;
        const months = <?php echo json_encode($months); ?>;
        const costData = <?php echo json_encode($cost_data); ?>;

        // Procedure Type Pie Chart
        new Chart(document.getElementById('procedureChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: procedureLabels,
                datasets: [{
                    data: procedureData,
                    backgroundColor: ['#FF9999','#66B3FF','#99FF99','#FFCC99','#FF6666'],
                    borderColor: '#fff',
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });



        // Veterinarian Bar Chart
        new Chart(document.getElementById('vetChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: vetNames,
                datasets: [{
                    label: 'Procedures per Vet',
                    data: vetProcedures,
                    backgroundColor: '#FF6347',
                    borderColor: '#FF6347',
                    borderWidth: 1
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });



        // Cost Line Chart
        new Chart(document.getElementById('costChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Total Cost of Procedures',
                    data: costData,
                    backgroundColor: 'rgba(75,192,192,0.2)',
                    borderColor: 'rgba(75,192,192,1)',
                    borderWidth: 1
                }]
            },
            options: { responsive: true }
        });



        // Display totals under charts
        const totalProcedures = procedureData.reduce((sum, val) => sum + val, 0);
        document.getElementById('procedureTotal').textContent = totalProcedures;

        const totalVetProcedures = vetProcedures.reduce((sum, val) => sum + val, 0);
        document.getElementById('vetTotal').textContent = totalVetProcedures;

        const totalCost = costData.reduce((sum, val) => sum + val, 0).toFixed(2);
        document.getElementById('costTotal').textContent = totalCost;
    </script>

     <footer>
            <h2 class="footerHeader">Makhanda SPCA</h2>
            <p class="foorerHeader">&copy; 2025 Makhanda SPCA. All rights reserved.</p>
        </footer>
</body>
</html>
