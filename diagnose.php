<?php
/**
 * DIAGNOSTIC AND FIX SCRIPT
 * Run this file once to diagnose and fix permission issues
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/settings.php';

echo "<pre>";
echo "==============================================\n";
echo "TRACKSITE PERMISSION DIAGNOSTIC & FIX TOOL\n";
echo "==============================================\n\n";

// Step 1: Check all users
echo "STEP 1: Checking all users in the system...\n";
echo "----------------------------------------------\n";

try {
    $stmt = $db->query("SELECT user_id, username, email, user_level, is_active FROM users ORDER BY user_id");
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "User ID: {$user['user_id']}\n";
        echo "  Username: {$user['username']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Level: {$user['user_level']}\n";
        echo "  Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Step 2: Check admin_profile table
echo "\nSTEP 2: Checking admin_profile table...\n";
echo "----------------------------------------------\n";

try {
    $stmt = $db->query("
        SELECT ap.*, u.username, u.user_level 
        FROM admin_profile ap 
        JOIN users u ON ap.user_id = u.user_id
    ");
    $admin_profiles = $stmt->fetchAll();
    
    if (empty($admin_profiles)) {
        echo "⚠️  WARNING: No admin profiles found!\n\n";
    } else {
        foreach ($admin_profiles as $profile) {
            echo "Admin ID: {$profile['admin_id']}\n";
            echo "  User ID: {$profile['user_id']}\n";
            echo "  Username: {$profile['username']}\n";
            echo "  User Level: {$profile['user_level']}\n";
            echo "  Name: {$profile['first_name']} {$profile['last_name']}\n\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Step 3: Check admin_permissions table
echo "\nSTEP 3: Checking admin_permissions table...\n";
echo "----------------------------------------------\n";

try {
    $stmt = $db->query("
        SELECT ap.admin_id, prof.first_name, prof.last_name, u.username,
               ap.can_view_payroll, ap.can_generate_payroll, ap.can_edit_payroll, ap.can_delete_payroll
        FROM admin_permissions ap
        JOIN admin_profile prof ON ap.admin_id = prof.admin_id
        JOIN users u ON prof.user_id = u.user_id
    ");
    $permissions = $stmt->fetchAll();
    
    if (empty($permissions)) {
        echo "⚠️  WARNING: No admin permissions found!\n\n";
    } else {
        foreach ($permissions as $perm) {
            echo "Admin: {$perm['first_name']} {$perm['last_name']} ({$perm['username']})\n";
            echo "  can_view_payroll: " . ($perm['can_view_payroll'] ? 'YES' : 'NO') . "\n";
            echo "  can_generate_payroll: " . ($perm['can_generate_payroll'] ? 'YES' : 'NO') . "\n";
            echo "  can_edit_payroll: " . ($perm['can_edit_payroll'] ? 'YES' : 'NO') . "\n";
            echo "  can_delete_payroll: " . ($perm['can_delete_payroll'] ? 'YES' : 'NO') . "\n\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n\n";
}

// Step 4: FIX ISSUES
echo "\n==============================================\n";
echo "FIXING ISSUES...\n";
echo "==============================================\n\n";

// Fix 1: Grant full permissions to all admins
echo "FIX 1: Granting full payroll permissions to all admins...\n";

try {
    $stmt = $db->query("
        SELECT admin_id FROM admin_profile
    ");
    $admin_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($admin_ids as $admin_id) {
        // Check if permission exists
        $stmt = $db->prepare("SELECT permission_id FROM admin_permissions WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update
            $stmt = $db->prepare("
                UPDATE admin_permissions 
                SET can_view_payroll = 1,
                    can_generate_payroll = 1,
                    can_edit_payroll = 1,
                    can_delete_payroll = 1,
                    can_view_workers = 1,
                    can_add_workers = 1,
                    can_edit_workers = 1,
                    can_view_attendance = 1,
                    can_mark_attendance = 1,
                    can_edit_attendance = 1,
                    can_view_deductions = 1,
                    can_manage_deductions = 1
                WHERE admin_id = ?
            ");
            $stmt->execute([$admin_id]);
            echo "✓ Updated permissions for admin_id: $admin_id\n";
        } else {
            // Insert
            $stmt = $db->prepare("
                INSERT INTO admin_permissions (
                    admin_id, 
                    can_view_payroll, can_generate_payroll, can_edit_payroll, can_delete_payroll,
                    can_view_workers, can_add_workers, can_edit_workers,
                    can_view_attendance, can_mark_attendance, can_edit_attendance,
                    can_view_deductions, can_manage_deductions
                ) VALUES (?, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1)
            ");
            $stmt->execute([$admin_id]);
            echo "✓ Created permissions for admin_id: $admin_id\n";
        }
    }
    
    echo "\n✓ All admin permissions updated successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
}

// Step 5: Verify the fix
echo "\n==============================================\n";
echo "VERIFICATION\n";
echo "==============================================\n\n";

try {
    $stmt = $db->query("
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.user_level,
            ap.admin_id,
            ap.first_name,
            ap.last_name,
            perm.can_edit_payroll,
            perm.can_view_payroll
        FROM users u
        LEFT JOIN admin_profile ap ON u.user_id = ap.user_id
        LEFT JOIN admin_permissions perm ON ap.admin_id = perm.admin_id
        WHERE u.user_level IN ('admin', 'super_admin')
    ");
    $final_check = $stmt->fetchAll();
    
    foreach ($final_check as $user) {
        echo "User: {$user['username']} (Level: {$user['user_level']})\n";
        echo "  can_view_payroll: " . ($user['can_view_payroll'] ? '✓ YES' : '✗ NO') . "\n";
        echo "  can_edit_payroll: " . ($user['can_edit_payroll'] ? '✓ YES' : '✗ NO') . "\n\n";
    }
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n==============================================\n";
echo "COMPLETED!\n";
echo "==============================================\n\n";

echo "NEXT STEPS:\n";
echo "1. Make sure /includes/admin_functions.php is uploaded\n";
echo "2. Log out and log back in as admin\n";
echo "3. Try accessing payroll again\n";
echo "4. Delete this diagnostic file after fixing\n\n";

echo "</pre>";
?>