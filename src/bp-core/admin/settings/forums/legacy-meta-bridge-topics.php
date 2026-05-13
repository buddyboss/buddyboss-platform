<?php
/**
 * Legacy Meta-Box Bridge for the Topic / Discussion edit modal.
 *
 * Auto-detects third-party plugins that register `add_meta_box()` calls
 * against the legacy `topic` CPT edit screen and surfaces their fields
 * as native React fields in the Settings 2.0 Topic edit modal.
 *
 * Note: the Settings 2.0 component name for topics is `discussions`,
 * not `topics` — third-party fields are registered via the
 * `bb_register_discussions_meta_fields` hook.
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

bb_legacy_register_cpt_meta_bridge(
	array(
		'component'    => 'discussions',
		'post_type'    => 'topic',
		'screen_match' => 'topic',
	)
);
