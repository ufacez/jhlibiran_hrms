<?php
/**
 * Top Bar Component
 * TrackSite Construction Management System
 * 
 * Reusable top navigation bar with notifications and search
 */


// Get current user info
$user_id = getCurrentUserId();
$username = getCurrentUsername();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$user_level = getCurrentUserLevel();


// Generate avatar URL
$avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=f39c12&color=fff";
?>
<div class="top-bar">
    <!-- Search Bar -->
    <div class="search">
        <input type="text" 
               name="search" 
               id="searchInput" 
               placeholder="Search workers, attendance, payroll..."
               autocomplete="off"
               aria-label="Search">
        <label for="searchInput">
            <i class="fas fa-search"></i>
        </label>
    </div>
    
   
    
    <!-- User Profile -->
    <div class="user">
        <a href="<?php echo BASE_URL; ?>/modules/super_admin/settings/index.php" 
           title="Manage Account"
           aria-label="User profile">
            <img src="<?php echo $avatar_url; ?>" 
                 alt="<?php echo htmlspecialchars($full_name); ?>"
                 loading="lazy">
        </a>
        <div class="user-info">
            <div>
                <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                <span class="user-role">
                    <?php echo $user_level === 'super_admin' ? 'Administrator' : 'Worker'; ?>
                </span>
            </div>
        </div>
    </div>
</div>