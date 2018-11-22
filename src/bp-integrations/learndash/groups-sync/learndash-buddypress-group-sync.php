<?php
/**
 * Plugin Name:  LearnDash BuddyPress Groups Sync
 * Plugin URI:   https://www.buddyboss.com/
 * Description:  Automatically create BuddyPress group and sync users when create or edit a LearnDash group.
 * Version:      1.0.0
 * Author:       BuddyBoss
 * Author URI:   https://www.buddyboss.com/
 * License:      GPL3
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 */

if (! defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/includes/class-core.php';
require_once dirname(__FILE__) . '/includes/helpers.php';

global $learndash_buddypress_groups_sync;
$learndash_buddypress_groups_sync = LearnDash_BuddyPress_Groups_Sync::instance(__FILE__, '1.0.0');
