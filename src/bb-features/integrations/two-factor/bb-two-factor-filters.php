<?php
/**
 * Two-Factor integration filters.
 *
 * Loaded from the integration's includes() on bp_include @8, and only when the Two
 * Factor plugin is active and the feature is enabled.
 *
 * Steps 6-10 and 15 add the submit-button registry entry, the provider-list filter
 * that drops the debug-only Dummy method, and the login redirect callbacks.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
