<?php
/**
 * Login Page
 * TrackSite Construction Management System
 */

// Define constant to allow includes
define('TRACKSITE_INCLUDED', true);

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/functions.php';


// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        redirect(BASE_URL . '/modules/super_admin/dashboard.php');
    } else {
        redirect(BASE_URL . '/modules/worker/dashboard.php');
    }
}

// Get redirect URL if set
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Get flash message
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" 
          integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" 
          crossorigin="anonymous" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <!-- Logo and Title -->
            <div class="logo">
                <img src="<?php echo IMAGES_URL; ?>/logo.png" alt="<?php echo SYSTEM_NAME; ?> Logo" class="logo-img">
                <span><?php echo SYSTEM_NAME; ?></span>
            </div>
            
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to continue to your dashboard</p>
            
            <!-- Flash Message -->
            <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form id="loginForm" method="POST">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_url); ?>">
                
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           placeholder="Company Email" 
                           required 
                           autocomplete="email"
                           autofocus>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" 
                           name="password" 
                           id="password" 
                           placeholder="Password" 
                           required
                           autocomplete="current-password">
                    <span class="toggle-password" onclick="togglePassword()">
                        <i class="far fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                </div>
                
                <button type="submit" class="btn-login" id="btnLogin">
                    <span class="btn-text">Sign In</span>
                    <span class="btn-loader" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Signing in...
                    </span>
                </button>
                
                <p class="note">
                    <i class="fas fa-shield-alt"></i> Authorized Personnel Only
                </p>
            </form>
            
            <!-- Footer -->
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo COMPANY_NAME; ?>. All rights reserved.</p>
                <p class="version">Version <?php echo SYSTEM_VERSION; ?></p>
            </div>
        </div>
        
        <!-- Background Animation -->
        <div class="background-animation">
            <div class="circle circle-1"></div>
            <div class="circle circle-2"></div>
            <div class="circle circle-3"></div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo JS_URL; ?>/login.js"></script>
</body>
</html>