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
	// Only load in admin area.
	if ( ! is_admin() ) {
		return;
	}

	// Include the main loader class.
	require_once __DIR__ . '/class-bb-mothership-loader.php';

	// Initialize the mothership functionality.
	BuddyBoss\Core\Admin\Mothership\BB_Mothership_Loader::init();
}

// Hook into WordPress admin_init to initialize mothership.
add_action( 'init', 'buddyboss_init_mothership' );

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