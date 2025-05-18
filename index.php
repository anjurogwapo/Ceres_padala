<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database and models
include_once 'config/database.php';
include_once 'models/user.php';
include_once 'models/bus.php';
include_once 'models/package.php';
include_once 'models/extension.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate objects
$user = new User($db);
$bus = new Bus($db);
$package = new Package($db);
$extension = new Extension($db);

// Get counts
$total_users = $user->countAll();
$total_buses = $bus->countAll();
$active_buses = $bus->countActive();
$total_packages = $package->countAll();
$pending_packages = $package->countByStatus('Pending');
$in_transit_packages = $package->countByStatus('In Transit');
$delivered_packages = $package->countByStatus('Delivered');
$pending_extensions = $extension->countByStatus('Pending');

// Get recent packages
$stmt = $package->read();
$recent_packages = [];
$count = 0;
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($count < 5) {
        $recent_packages[] = $row;
        $count++;
    } else {
        break;
    }
}

// Get active buses
$stmt = $bus->read();
$active_bus_list = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($row['status'] == 'Active') {
        $active_bus_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ceres Padala Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>

        <!-- Content -->
        <div class="content">
            <!-- Header -->
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <img src="img/admin-avatar.jpg" alt="Admin">
                    <div class="dropdown">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($_SESSION["admin_username"]); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-cards">
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: #d1ecf1;">
                        <i class="bi bi-box-seam" style="color: #0c5460;"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Total Packages</h3>
                        <p><?php echo $total_packages; ?></p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: #d4edda;">
                        <i class="bi bi-check-circle" style="color: #155724;"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Delivered</h3>
                        <p><?php echo $delivered_packages; ?></p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: #fff3cd;">
                        <i class="bi bi-truck" style="color: #856404;"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>In Transit</h3>
                        <p><?php echo $in_transit_packages; ?></p>
                    </div>
                </div>
                <div class="dashboard-card">
                    <div class="dashboard-card-icon" style="background-color: #f8d7da;">
                        <i class="bi bi-hourglass-split" style="color: #721c24;"></i>
                    </div>
                    <div class="dashboard-card-content">
                        <h3>Pending</h3>
                        <p><?php echo $pending_packages; ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Recent Packages -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Recent Packages</h2>
                            <a href="packages.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Tracking #</th>
                                            <th>Sender</th>
                                            <th>Receiver</th>
                                            <th>Route</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(count($recent_packages) > 0): ?>
                                            <?php foreach($recent_packages as $pkg): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($pkg['tracking_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($pkg['sender_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($pkg['receiver_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($pkg['origin_terminal']) . ' - ' . htmlspecialchars($pkg['destination_terminal']); ?></td>
                                                    <td>
                                                        <?php
                                                        $status_class = '';
                                                        switch($pkg['status']) {
                                                            case 'Pending':
                                                                $status_class = 'status-pending';
                                                                break;
                                                            case 'In Transit':
                                                                $status_class = 'status-transit';
                                                                break;
                                                            case 'Delivered':
                                                                $status_class = 'status-delivered';
                                                                break;
                                                            case 'Cancelled':
                                                                $status_class = 'status-cancelled';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($pkg['status']); ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="view_package.php?id=<?php echo $pkg['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No packages found</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- Active Buses -->
                    <div class="card">
                        <div class="card-header">
                            <h2>Active Buses</h2>
                            <a href="buses.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if(count($active_bus_list) > 0): ?>
                                <?php foreach($active_bus_list as $bus_item): ?>
                                    <div class="bus-card mb-3">
                                        <div class="bus-card-header">
                                            <h3>Bus #<?php echo htmlspecialchars($bus_item['bus_number']); ?></h3>
                                            <span class="status-badge status-active">Active</span>
                                        </div>
                                        <div class="bus-card-body">
                                            <p><strong>Plate:</strong> <?php echo htmlspecialchars($bus_item['plate_number']); ?></p>
                                            <p><strong>Route:</strong> <?php echo htmlspecialchars($bus_item['route']); ?></p>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($bus_item['current_location'] ?? 'Unknown'); ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center">No active buses found</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pending Extensions -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h2>Pending Extensions</h2>
                            <a href="extensions.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h3 class="fs-5 mb-0"><?php echo $pending_extensions; ?></h3>
                                    <p class="text-muted mb-0">Pending Requests</p>
                                </div>
                                <a href="extensions.php?status=Pending" class="btn btn-warning">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/effects.js"></script>
    <script src="js/theme-switcher.js"></script>
</body>
</html>
