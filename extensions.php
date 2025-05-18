<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database and extension model
include_once 'config/database.php';
include_once 'models/extension.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate extension object
$extension = new Extension($db);

// Process status filter
$status_filter = '';
if(isset($_GET['status']) && !empty($_GET['status'])) {
    $status_filter = $_GET['status'];
    $stmt = $extension->readByStatus($status_filter);
} else {
    // Read all extensions
    $stmt = $extension->read();
}

$extensions = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $extensions[] = $row;
}

// Process message
$message = '';
$message_type = '';

if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if($action == 'approve') {
        $extension->id = $id;
        $extension->status = 'Approved';
        if($extension->updateStatus()) {
            $message = "Extension request approved successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to approve extension request.";
            $message_type = "danger";
        }
    } else if($action == 'deny') {
        $extension->id = $id;
        $extension->status = 'Denied';
        if($extension->updateStatus()) {
            $message = "Extension request denied successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to deny extension request.";
            $message_type = "danger";
        }
    } else if($action == 'delete') {
        $extension->id = $id;
        if($extension->delete()) {
            $message = "Extension request deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete extension request.";
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
    <title>Manage Extensions - Ceres Padala Admin</title>
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
                <h1>Manage Extensions</h1>
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

            <!-- Extensions Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2>Extension Requests</h2>
                    <div class="filter-buttons">
                        <a href="extensions.php" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                        <a href="extensions.php?status=Pending" class="btn <?php echo $status_filter == 'Pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
                        <a href="extensions.php?status=Approved" class="btn <?php echo $status_filter == 'Approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">Approved</a>
                        <a href="extensions.php?status=Denied" class="btn <?php echo $status_filter == 'Denied' ? 'btn-primary' : 'btn-outline-primary'; ?>">Denied</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Package No.</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($extensions) > 0): ?>
                                    <?php foreach($extensions as $ext): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($ext['sender_name']); ?></td>
                                            <td><?php echo htmlspecialchars($ext['tracking_number']); ?></td>
                                            <td><?php echo htmlspecialchars($ext['duration']); ?></td>
                                            <td>₱<?php echo htmlspecialchars($ext['price']); ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch($ext['status']) {
                                                    case 'Pending':
                                                        $status_class = 'status-pending';
                                                        break;
                                                    case 'Approved':
                                                        $status_class = 'status-approved';
                                                        break;
                                                    case 'Denied':
                                                        $status_class = 'status-denied';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($ext['status']); ?></span>
                                            </td>
                                            <td>
                                                <a href="view_extension.php?id=<?php echo $ext['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if($ext['status'] == 'Pending'): ?>
                                                    <a href="extensions.php?action=approve&id=<?php echo $ext['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>
                                                    <a href="extensions.php?action=deny&id=<?php echo $ext['id']; ?>" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-x-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="javascript:void(0);" onclick="confirmDelete('extensions.php?action=delete&id=<?php echo $ext['id']; ?>', 'extension request')" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No extension requests found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function confirmDelete(url, type) {
            if(confirm(`Are you sure you want to delete this ${type}?`)) {
                window.location.href = url;
            }
        }
    </script>
</body>
</html>
