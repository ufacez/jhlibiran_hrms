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

// Prefer user's uploaded profile image when available
try {
    if (!defined('UPLOADS_URL')) {
        require_once __DIR__ . '/../config/settings.php';
    }

    if (!isset($db)) {
        require_once __DIR__ . '/../config/database.php';
    }

    // Determine which table/column to check for profile image
    $profileImage = null;
    if ($user_level === USER_LEVEL_WORKER) {
        $worker_id = $_SESSION['worker_id'] ?? null;
        if ($worker_id) {
            $stmt = $db->prepare("SELECT profile_image FROM workers WHERE worker_id = ?");
            $stmt->execute([$worker_id]);
            $profileImage = $stmt->fetchColumn();
        }
    } elseif ($user_level === USER_LEVEL_SUPER_ADMIN) {
        $stmt = $db->prepare("SELECT profile_image FROM super_admin_profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profileImage = $stmt->fetchColumn();
    } else {
        $stmt = $db->prepare("SELECT profile_image FROM admin_profile WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profileImage = $stmt->fetchColumn();
    }

    if (!empty($profileImage) && file_exists(UPLOADS_PATH . '/' . $profileImage)) {
        // Add file modification time to force cache refresh when image changes
        $avatar_url = UPLOADS_URL . '/' . $profileImage . '?v=' . filemtime(UPLOADS_PATH . '/' . $profileImage);
    }
} catch (Exception $e) {
    // On any error, fall back to generated avatar (no-op)
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
                    <?php echo $user_level === 'super_admin' ? 'Super Admin' : 'Worker'; ?>
                </span>
            </div>
        </div>
    </div>
</div>