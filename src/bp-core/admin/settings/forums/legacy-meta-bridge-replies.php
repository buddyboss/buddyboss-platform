<?php
/**
 * Legacy Meta-Box Bridge for the Reply edit modal.
 *
 * Auto-detects third-party plugins that register `add_meta_box()` calls
 * against the legacy `reply` CPT edit screen and surfaces their fields
 * as native React fields in the Settings 2.0 Reply edit modal.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

bb_legacy_register_cpt_meta_bridge(
	array(
		'component'    => 'replies',
		'post_type'    => 'reply',
		'screen_match' => 'reply',
	)
);
