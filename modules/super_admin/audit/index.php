<?php
/**
 * Audit Trail Module - REDIRECT
 * TrackSite Construction Management System
 * 
 * This page has been consolidated into the unified audit trail
 * at Settings > Audit Trail. Redirecting automatically.
 */

define('TRACKSITE_INCLUDED', true);

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/settings.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Location: ' . BASE_URL . '/modules/super_admin/settings/activity_logs.php');
exit;