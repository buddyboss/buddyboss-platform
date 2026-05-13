<?php
/**
 * Legacy Meta-Box Bridge for the Email Template edit modal.
 *
 * Auto-detects third-party plugins that register `add_meta_box()` calls
 * against the legacy `bp-email` CPT edit screen and surfaces their fields
 * as native React fields in the Settings 2.0 Email Template modal.
 *
 * Common consumers: email-customization plugins (Postmark / Mailgun
 * delivery metadata, A/B-testing variants, plugin-specific headers).
 *
 * @package BuddyBoss\Core\Administration
 * @since   BuddyBoss 3.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/legacy-meta-bridge-utils.php';

bb_legacy_register_cpt_meta_bridge(
	array(
		'component'    => 'emails',
		'post_type'    => 'bp-email',
		'screen_match' => 'bp-email',
	)
);
