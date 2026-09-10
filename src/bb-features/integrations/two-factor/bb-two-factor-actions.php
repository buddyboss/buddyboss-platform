<?php
/**
 * Two-Factor integration actions.
 *
 * Loaded from the integration's includes() on bp_include @8, and only when the Two
 * Factor plugin is active and the feature is enabled.
 *
 * Steps 6-10 add the Security nav and admin-bar entry, the screen handler, the save
 * handler, the TOTP REST guard and the asset enqueues.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
