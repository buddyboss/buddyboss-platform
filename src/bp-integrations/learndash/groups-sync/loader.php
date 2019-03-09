<?php
/**
 * @todo add description
 * 
 * @package BuddyBoss\LearnDash
 * @since BuddyBoss 1.0.0
 */ 

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once dirname(__FILE__) . '/includes/class-core.php';
require_once dirname(__FILE__) . '/includes/helpers.php';

global $learndash_buddypress_groups_sync;
$learndash_buddypress_groups_sync = LearnDash_BuddyPress_Groups_Sync::instance(__FILE__, '1.0.0');
