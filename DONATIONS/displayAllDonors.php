<?php
// displayAllDonors.php

// 1. Include the database connection file
include 'DatabaseConnection.php';

// Initialize filter variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$amount_sort = isset($_GET['amount_sort']) ? $_GET['amount_sort'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build the SQL query with filters
$sql = "SELECT * FROM alldonations WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR surname LIKE '%$search%' OR email LIKE '%$search%' OR CellNumber LIKE '%$search%')";
}

if (!empty($payment_method) && $payment_method != 'all') {
    $sql .= " AND PaymentMethod = '$payment_method'";
}

if (!empty($start_date)) {
    $sql .= " AND DonationDate >= '$start_date'";
}

if (!empty($end_date)) {
    $sql .= " AND DonationDate <= '$end_date'";
}

if (!empty($amount_sort)) {
    if ($amount_sort == 'high_low') {
        $sql .= " ORDER BY Amount DESC";
    } elseif ($amount_sort == 'low_high') {
        $sql .= " ORDER BY Amount ASC";
    }
} else {
    $sql .= " ORDER BY DonationDate DESC";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Donations</title>
    <style>
        /* Styles from adoption_records.php */
        .donations-table {
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

        .donations-table th {
            background: rgba(5, 111, 101, 0.7);
            color: white;
            font-size: 15px;
            font-weight: 600;
            padding: 15px;
            text-align: left;
        }

        .donations-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
            vertical-align: middle;
        }

        .donations-table tr:hover {
            background-color: #f3f4f6;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
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
            text-decoration: none;
            display: inline-block;
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

        .filter-group select,
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
    </style>
</head>

<body>
    <a href="../Cruelty Reports/admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h1>All Donations</h1>

    <div class="filters">
        <form method="GET" action="">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search name, email, phone..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label for="payment_method">Payment Method:</label>
                    <select id="payment_method" name="payment_method">
                        <option value="all" <?php echo ($payment_method == 'all' || $payment_method == '') ? 'selected' : ''; ?>>All Methods</option>
                        <option value="EFT" <?php echo $payment_method == 'EFT' ? 'selected' : ''; ?>>EFT</option>
                        <option value="PayPal" <?php echo $payment_method == 'PayPal' ? 'selected' : ''; ?>>PayPal
                        </option>
                        <option value="Card" <?php echo $payment_method == 'Card' ? 'selected' : ''; ?>>Card</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="amount_sort">Sort by Amount:</label>
                    <select id="amount_sort" name="amount_sort">
                        <option value="" <?php echo $amount_sort == '' ? 'selected' : ''; ?>>Default</option>
                        <option value="high_low" <?php echo $amount_sort == 'high_low' ? 'selected' : ''; ?>>Highest to
                            Lowest</option>
                        <option value="low_high" <?php echo $amount_sort == 'low_high' ? 'selected' : ''; ?>>Lowest to
                            Highest</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="start_date">From Date:</label>
                    <input type="date" id="start_date" name="start_date"
                        value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                <div class="filter-group">
                    <label for="end_date">To Date:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
            </div>
            <div class="filter-buttons">
                <button type="submit" class="btn-primary">Apply Filters</button>
                <a href="displayAllDonors.php" class="btn-secondary">Clear Filters</a>
            </div>
        </form>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table class="donations-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Donation Date</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Donor ID</th>
                    <th>Donor CellNumber</th>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Surname</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['DonationDate']); ?></td>
                        <td><?php echo htmlspecialchars($row['Amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['PaymentMethod']); ?></td>
                        <td><?php echo htmlspecialchars($row['donor_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['CellNumber']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['surname']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a class="btn-delete" href="deleteDonation.php?id=<?php echo $row['donor_id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No donations found. (Fetched: <?php echo $result->num_rows; ?> rows)</p>
    <?php endif; ?>

    <?php
    $conn->close();
    ?>
</body>

</html>