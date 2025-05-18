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
include_once 'models/bus.php';
include_once 'models/bus_schedule.php';
include_once 'models/terminal.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate objects
$bus = new Bus($db);
$bus_schedule = new BusSchedule($db);
$terminal = new Terminal($db);

// Read all buses
$stmt = $bus->read();
$buses = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if($row['status'] == 'Active') {
        $buses[] = $row;
    }
}

// Read all terminals
$stmt = $terminal->read();
$terminals = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $terminals[] = $row;
}

// Process form submission
$message = '';
$message_type = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['update_eta'])) {
        $schedule_id = $_POST['schedule_id'];
        $new_eta = $_POST['new_eta'];
        
        $bus_schedule->id = $schedule_id;
        $bus_schedule->arrival_time = $new_eta;
        
        if($bus_schedule->updateETA()) {
            $message = "ETA updated successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to update ETA.";
            $message_type = "danger";
        }
    }
}

// Get selected route
$selected_route = '';
$schedules = [];

if(isset($_GET['route']) && !empty($_GET['route'])) {
    $selected_route = $_GET['route'];
    
    // Get origin and destination terminal IDs
    $route_parts = explode('-', $selected_route);
    $origin = trim($route_parts[0]);
    $destination = trim($route_parts[1]);
    
    $origin_id = 0;
    $destination_id = 0;
    
    foreach($terminals as $term) {
        if(strpos($term['name'], $origin) !== false) {
            $origin_id = $term['id'];
        }
        if(strpos($term['name'], $destination) !== false) {
            $destination_id = $term['id'];
        }
    }
    
    if($origin_id > 0 && $destination_id > 0) {
        $stmt = $bus_schedule->readByRoute($origin_id, $destination_id);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $schedules[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set ETA - Ceres Padala Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
                <h1>Set ETA</h1>
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

            <!-- Set ETA Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Select Route</h2>
                </div>
                <div class="card-body">
                    <form action="set_eta.php" method="GET" class="route-select-form">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="route" class="form-label">Route</label>
                                    <select name="route" id="route" class="form-select">
                                        <option value="">Select a route</option>
                                        <?php
                                        $routes = [];
                                        foreach($buses as $bus_item) {
                                            if(!in_array($bus_item['route'], $routes)) {
                                                $routes[] = $bus_item['route'];
                                                $selected = ($selected_route == $bus_item['route']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($bus_item['route']) . '" ' . $selected . '>' . htmlspecialchars($bus_item['route']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">
                                        <i class="bi bi-search"></i> View Schedules
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if(!empty($selected_route)): ?>
                <div class="card">
                    <div class="card-header">
                        <h2><?php echo htmlspecialchars($selected_route); ?> Schedules</h2>
                    </div>
                    <div class="card-body">
                        <?php if(count($schedules) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Bus #</th>
                                            <th>Departure</th>
                                            <th>Current ETA</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($schedules as $schedule): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($schedule['bus_number']); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></td>
                                                <td><?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch($schedule['status']) {
                                                        case 'On Time':
                                                            $status_class = 'status-active';
                                                            break;
                                                        case 'Delayed':
                                                            $status_class = 'status-pending';
                                                            break;
                                                        case 'Cancelled':
                                                            $status_class = 'status-cancelled';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($schedule['status']); ?></span>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateEtaModal<?php echo $schedule['id']; ?>">
                                                        <i class="bi bi-clock"></i> Update ETA
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Update ETA Modal -->
                                            <div class="modal fade" id="updateEtaModal<?php echo $schedule['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update ETA for Bus #<?php echo htmlspecialchars($schedule['bus_number']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="set_eta.php?route=<?php echo urlencode($selected_route); ?>" method="POST">
                                                            <div class="modal-body">
                                                                <div class="form-group mb-3">
                                                                    <label for="current_eta<?php echo $schedule['id']; ?>" class="form-label">Current ETA</label>
                                                                    <input type="text" id="current_eta<?php echo $schedule['id']; ?>" class="form-control" value="<?php echo date('h:i A', strtotime($schedule['arrival_time'])); ?>" disabled>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="new_eta<?php echo $schedule['id']; ?>" class="form-label">New ETA</label>
                                                                    <input type="time" id="new_eta<?php echo $schedule['id']; ?>" name="new_eta" class="form-control" required>
                                                                </div>
                                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="update_eta" class="btn btn-primary">Update ETA</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No schedules found for this route.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
