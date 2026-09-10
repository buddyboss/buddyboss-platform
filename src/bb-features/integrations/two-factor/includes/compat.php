<?php
/**
 * Two Factor plugin compatibility probe.
 *
 * Step 2 adds the plugin-state resolver: active | installed_inactive | not_installed |
 * unsupported_version, plus the version and capability probes.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
