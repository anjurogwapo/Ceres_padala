<?php
// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Include database and user model
include_once 'config/database.php';
include_once 'models/user.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Instantiate user object
$user = new User($db);

// Process search
$search_term = '';
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $stmt = $user->search($search_term);
} else {
    // Read all users
    $stmt = $user->read();
}

$users = [];
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $users[] = $row;
}

// Process message
$message = '';
$message_type = '';

if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    if($action == 'delete') {
        $user->id = $id;
        if($user->delete()) {
            $message = "User deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Unable to delete user.";
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
    <title>Manage Users - Ceres Padala Admin</title>
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
                <h1>Manage Users</h1>
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

            <!-- Users Content -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2>User Search</h2>
                </div>
                <div class="card-body">
                    <form action="users.php" method="GET" class="user-search">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by username or phone number" value="<?php echo htmlspecialchars($search_term); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2>User List</h2>
                    <a href="add_user.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add User
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Phone Number</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($users) > 0): ?>
                                    <?php foreach($users as $user_item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo !empty($user_item['profile_image']) ? 'uploads/' . htmlspecialchars($user_item['profile_image']) : 'img/default-avatar.jpg'; ?>" alt="<?php echo htmlspecialchars($user_item['username']); ?>" class="user-avatar-sm me-2">
                                                    <?php echo htmlspecialchars($user_item['username']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user_item['phone_number']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($user_item['created_at'])); ?></td>
                                            <td>
                                                <a href="view_user.php?id=<?php echo $user_item['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit_user.php?id=<?php echo $user_item['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmDelete('users.php?action=delete&id=<?php echo $user_item['id']; ?>', 'user')" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No users found</td>
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
