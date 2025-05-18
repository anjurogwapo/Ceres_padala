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
$user->id = $_SESSION["admin_id"];

// Get user data
$user->readOne();

// Process form submission
$message = '';
$message_type = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['update_profile'])) {
        // Update profile
        $user->username = $_POST['username'];
        $user->phone_number = $_POST['phone_number'];
        
        // Handle profile image upload
        if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = array("jpg" => "image/jpg", "jpeg" => "image/jpeg", "gif" => "image/gif", "png" => "image/png");
            $filename = $_FILES['profile_image']['name'];
            $filetype = $_FILES['profile_image']['type'];
            $filesize = $_FILES['profile_image']['size'];
            
            // Verify file extension
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(!array_key_exists($ext, $allowed)) {
                $message = "Error: Please select a valid file format.";
                $message_type = "danger";
            } else {
                // Verify file size - 5MB maximum
                $maxsize = 5 * 1024 * 1024;
                if($filesize > $maxsize) {
                    $message = "Error: File size is larger than the allowed limit.";
                    $message_type = "danger";
                } else {
                    // Verify MIME type of the file
                    if(in_array($filetype, $allowed)) {
                        // Check if file exists before uploading
                        $new_filename = uniqid() . "." . $ext;
                        $target_file = "uploads/" . $new_filename;
                        
                        if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            $user->profile_image = $new_filename;
                        } else {
                            $message = "Error: There was a problem uploading your file. Please try again.";
                            $message_type = "danger";
                        }
                    } else {
                        $message = "Error: There was a problem with the file type. Please try again.";
                        $message_type = "danger";
                    }
                }
            }
        }
        
        if(empty($message)) {
            if($user->update()) {
                $message = "Profile updated successfully.";
                $message_type = "success";
            } else {
                $message = "Unable to update profile.";
                $message_type = "danger";
            }
        }
    } else if(isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if(!password_verify($current_password, $user->password)) {
            $message = "Current password is incorrect.";
            $message_type = "danger";
        } else if($new_password != $confirm_password) {
            $message = "New password and confirm password do not match.";
            $message_type = "danger";
        } else {
            $user->password = $new_password;
            if($user->updatePassword()) {
                $message = "Password changed successfully.";
                $message_type = "success";
            } else {
                $message = "Unable to change password.";
                $message_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Ceres Padala Admin</title>
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
                <h1>Profile</h1>
                <div class="user-info">
                    <img src="<?php echo !empty($user->profile_image) ? 'uploads/' . htmlspecialchars($user->profile_image) : 'img/admin-avatar.jpg'; ?>" alt="Admin">
                    <div class="dropdown">
                        <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <?php echo htmlspecialchars($user->username); ?>
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

            <!-- Profile Content -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h2>Personal Information</h2>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="POST" enctype="multipart/form-data">
                                <div class="profile-image-container mb-4">
                                    <img src="<?php echo !empty($user->profile_image) ? 'uploads/' . htmlspecialchars($user->profile_image) : 'img/admin-avatar.jpg'; ?>" alt="Profile Image" class="profile-image">
                                    <div class="profile-image-overlay">
                                        <label for="profile_image" class="profile-image-label">
                                            <i class="bi bi-camera"></i>
                                        </label>
                                        <input type="file" id="profile_image" name="profile_image" class="profile-image-input">
                                    </div>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user->username); ?>" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="phone_number" class="form-label">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user->phone_number); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h2>Change Password</h2>
                        </div>
                        <div class="card-body">
                            <form action="profile.php" method="POST">
                                <div class="form-group mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="bi bi-lock"></i> Change Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview profile image before upload
            const profileImageInput = document.getElementById('profile_image');
            const profileImage = document.querySelector('.profile-image');
            
            profileImageInput.addEventListener('change', function() {
                const file = this.files[0];
                if(file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profileImage.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
    </script>
</body>
</html>
