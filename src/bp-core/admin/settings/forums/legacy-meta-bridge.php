<?php
/**
 * Legacy Meta-Box Bridge for the Forum edit modal.
 *
 * Auto-detects third-party plugins that register `add_meta_box()` calls
 * against the legacy `forum` CPT edit screen and surfaces their fields
 * as native React fields in the Settings 2.0 Forum edit modal.
 *
 * Save model: bridge fields run in the 'before' phase so $_POST is
 * populated before `wp_update_post()` fires `save_post_forum`. Any
 * third-party `save_post_forum` handler reads the populated $_POST
 * and persists meta — no manual replay needed.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

bb_legacy_register_cpt_meta_bridge(
	array(
		'component'    => 'forums',
		'post_type'    => 'forum',
		// Post-edit screen IDs follow `<post_type>` for the edit form. Match
		// substring so both `forum` and `edit-forum` (list table) are covered
		// — only the edit screen actually populates per-post metaboxes.
		'screen_match' => 'forum',
	)
);
