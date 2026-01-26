<?php
/**
 * Admin Top Bar Component
 * TrackSite Construction Management System
 * 
 * Top navigation bar for Admin and Super Admin users
 */

// Get current user info
$user_id = getCurrentUserId();
$username = getCurrentUsername();
$full_name = $_SESSION['full_name'] ?? 'Administrator';
$user_level = getCurrentUserLevel();

// Get profile image if admin or super admin
$profile_image = null;
if ($user_level === 'admin' || $user_level === 'super_admin') {
    $profile_table = ($user_level === 'super_admin') ? 'super_admin_profile' : 'admin_profile';
    try {
        $stmt = $db->prepare("SELECT profile_image FROM {$profile_table} WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile_image = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Admin Topbar profile image error: " . $e->getMessage());
    }
}

// Generate avatar URL
if ($profile_image && file_exists(UPLOADS_PATH . '/' . $profile_image)) {
    $avatar_url = UPLOADS_URL . '/' . $profile_image;
} else {
    $avatar_url = "https://ui-avatars.com/api/?name=" . urlencode($full_name) . "&background=DAA520&color=1a1a1a";
}
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
        <a href="<?php echo BASE_URL; ?>/modules/admin/profile.php" 
           title="My Profile"
           aria-label="User profile">
            <img src="<?php echo $avatar_url; ?>" 
                 alt="<?php echo htmlspecialchars($full_name); ?>"
                 loading="lazy"
                 style="border: 2px solid #DAA520;">
        </a>
        <div class="user-info">
            <div>
                <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                <span class="user-role">
                    <?php 
                    if ($user_level === 'super_admin') {
                        echo 'Super Administrator';
                    } elseif ($user_level === 'admin') {
                        echo 'Administrator';
                    } else {
                        echo 'User';
                    }
                    ?>
                </span>
            </div>
        </div>
    </div>
</div>