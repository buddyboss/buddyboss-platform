<?php
/**
 * BuddyBoss Mothership Initialization
 *
 * This file should be included from the main BuddyBoss plugin file to initialize
 * the license activation and add-ons functionality.
 *
 * @package BuddyBoss
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Initialize BuddyBoss Mothership functionality.
 */
function buddyboss_init_mothership() {
	// The vendor autoloader should already be loaded from bp-loader.php
	// Setup namespace aliases to allow using BuddyBossPlatform namespace consistently.
	require_once __DIR__ . '/autoload-aliases.php';

	// Include BuddyBoss specific files.
	require_once __DIR__ . '/class-bb-plugin-connector.php';
	require_once __DIR__ . '/class-bb-license-manager.php';
	require_once __DIR__ . '/class-bb-addons-manager.php';
	require_once __DIR__ . '/class-bb-license-page.php';
	require_once __DIR__ . '/class-bb-addons-page.php';

	if ( ! class_exists( 'BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader' ) ) {
		// Include the main loader class.
		require_once __DIR__ . '/class-bb-mothership-loader.php';
	}

	// Initialize the mothership functionality.
	new BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader();
}

// Hook into WordPress admin_init to initialize mothership.
buddyboss_init_mothership();

/**
 * For local development, you can define these constants in wp-config.php:
 *
 * define( 'BUDDYBOSS_MOTHERSHIP_API_BASE_URL', 'https://your-local-api.test/v1/' );
 * define( 'BUDDYBOSS_DISABLE_SSL_VERIFY', true );
 * define( 'BUDDYBOSS_LICENSE_KEY', 'your-test-license-key' );
 * define( 'BUDDYBOSS_ACTIVATION_DOMAIN', 'your-test-domain.com' );
 * define( 'BUDDYBOSS_API_EMAIL', 'your-api-email@example.com' );
 * define( 'BUDDYBOSS_API_TOKEN', 'your-api-token' );
 */
