<?php
if (! defined('ABSPATH')) {
    exit;
}

require_once dirname(__FILE__) . '/includes/class-core.php';
require_once dirname(__FILE__) . '/includes/helpers.php';

global $learndash_buddypress_groups_report;
$learndash_buddypress_groups_report = LearnDash_BuddyPress_Groups_Reports::get_instance(__FILE__, '1.0.0');
