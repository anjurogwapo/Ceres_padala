<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database and bus model
include_once 'config/database.php';
include_once 'models/bus.php';
include_once 'models/bus_schedule.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate bus object
$bus = new Bus($db);
$bus_schedule = new BusSchedule($db);

// Read all buses
$stmt = $bus->read();
$buses = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $buses[] = $row;
}

// Process message
$message = '';
$message_type = '';

if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if($action == 'delete') {
        $bus->id = $id;
        if($bus->delete()) {
            $message = "Bus deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete bus.";
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses - Ceres Padala Admin</title>
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
                <h1>Manage Buses</h1>
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

            <!-- Alert Container -->
            <div id="alert-container">
                <?php if(!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Buses Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Bus List</h2>
                    <a href="add_bus.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Bus
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Bus #</th>
                                    <th>Plate Number</th>
                                    <th>Route</th>
                                    <th>Current Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($buses) > 0): ?>
                                    <?php foreach($buses as $bus_item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bus_item['bus_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bus_item['plate_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bus_item['route']); ?></td>
                                            <td><?php echo htmlspecialchars($bus_item['current_location'] ?? 'Unknown'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch($bus_item['status']) {
                                                    case 'Active':
                                                        $status_class = 'status-active';
                                                        break;
                                                    case 'Inactive':
                                                        $status_class = 'status-inactive';
                                                        break;
                                                    case 'Maintenance':
                                                        $status_class = 'status-maintenance';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($bus_item['status']); ?></span>
                                            </td>
                                            <td>
                                                <a href="view_bus.php?id=<?php echo $bus_item['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_bus.php?id=<?php echo $bus_item['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmDelete('buses.php?action=delete&id=<?php echo $bus_item['id']; ?>', 'bus')" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <a href="bus_schedule.php?bus_id=<?php echo $bus_item['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-calendar"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No buses found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bus Schedule Section -->
            <div class="card">
                <div class="card-header">
                    <h2>Check Bus Schedule</h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="route-select" class="form-label">Select Route</label>
                                <select id="route-select" class="form-select">
                                    <option value="">Select a route</option>
                                    <?php
                                    $routes = [];
                                    foreach($buses as $bus_item) {
                                        if(!in_array($bus_item['route'], $routes)) {
                                            $routes[] = $bus_item['route'];
                                            echo '<option value="' . htmlspecialchars($bus_item['route']) . '">' . htmlspecialchars($bus_item['route']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button id="check-schedule" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Check Schedule
                                </button>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div id="schedule-results" class="mt-3">
                                <p class="text-center text-muted">Select a route to view schedules</p>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkScheduleBtn = document.getElementById('check-schedule');
            const routeSelect = document.getElementById('route-select');
            const scheduleResults = document.getElementById('schedule-results');

            checkScheduleBtn.addEventListener('click', function() {
                const route = routeSelect.value;
                if(!route) {
                    scheduleResults.innerHTML = '<p class="text-center text-danger">Please select a route</p>';
                    return;
                }

                // Add loading animation
                scheduleResults.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

                // In a real application, this would be an AJAX call to get the schedule
                // For now, we'll just show a mock schedule after a short delay
                setTimeout(() => {
                    scheduleResults.innerHTML = `
                        <h4>${route} Schedule</h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Bus #</th>
                                        <th>Departure</th>
                                        <th>Arrival</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>7183</td>
                                        <td>07:30 AM</td>
                                        <td>09:30 AM</td>
                                        <td><span class="status-badge status-active">On Time</span></td>
                                    </tr>
                                    <tr>
                                        <td>7183</td>
                                        <td>10:00 AM</td>
                                        <td>12:00 PM</td>
                                        <td><span class="status-badge status-active">On Time</span></td>
                                    </tr>
                                    <tr>
                                        <td>7183</td>
                                        <td>01:30 PM</td>
                                        <td>03:30 PM</td>
                                        <td><span class="status-badge status-active">On Time</span></td>
                                    </tr>
                                    <tr>
                                        <td>7183</td>
                                        <td>04:00 PM</td>
                                        <td>06:00 PM</td>
                                        <td><span class="status-badge status-active">On Time</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                }, 800);
            });
        });

        function confirmDelete(url, type) {
            if(confirm(`Are you sure you want to delete this ${type}?`)) {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>
