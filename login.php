<?php
// Start session
session_start();

// Check if user is already logged in
if(isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

// Include database and user model
include_once 'config/database.php';
include_once 'models/user.php';

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is empty
    if(empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)) {
        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Instantiate user object
        $user = new User($db);
        
        // Set username property
        $user->username = $username;
        
        // Check if username exists
        if($user->usernameExists()) {
            // Check if user is admin
            if($user->user_type == 'admin') {
                // Verify password
                if(password_verify($password, $user->password)) {
                    // Password is correct, start a new session
                    session_start();
                    
                    // Store data in session variables
                    $_SESSION["loggedin"] = true;
                    $_SESSION["admin_id"] = $user->id;
                    $_SESSION["admin_username"] = $user->username;
                    
                    // Redirect user to welcome page
                    header("location: index.php");
                    exit;
                } else {
                    // Password is not valid
                    $login_err = "Invalid username or password.";
                }
            } else {
                // User is not an admin
                $login_err = "Access denied. Admin privileges required.";
            }
        } else {
            // Username doesn't exist
            $login_err = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ceres Padala Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-form-container">
            <div class="login-circle">
                <img src="img/ceres-logo.png" alt="Ceres Padala Logo" class="login-logo">
                <h2>Ceres Padala</h2>
                <h3>Admin</h3>
                
                <?php 
                if(!empty($login_err)){
                    echo '<div class="alert alert-danger">' . $login_err . '</div>';
                }        
                ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Admin Username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                        <span class="invalid-feedback"><?php echo $username_err; ?></span>
                    </div>    
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                        <span class="invalid-feedback"><?php echo $password_err; ?></span>
                    </div>
                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="Log In">
                    </div>
                </form>
                
                <div style="margin-top: 20px;">
                    <div id="theme-switcher" class="theme-switcher mx-auto">
                        <i class="bi bi-sun"></i>
                        <i class="bi bi-moon"></i>
                    </div>
                    <p style="font-size: 12px; margin-top: 8px; color: white;">Toggle Theme</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme-switcher.js"></script>
</body>
</html>
