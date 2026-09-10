<?php
/**
 * Two-Factor integration loader.
 *
 * Included by BP_Core::includes() via the bp_integrations whitelist, so it must keep
 * this exact filename.
 *
 * Step 4 adds the Integration Bridge registration and instantiates the integration on
 * bp_setup_integrations @20. It also requires the functions file, because bp_include
 * runs before feature discovery.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Features\Integrations\TwoFactor
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
