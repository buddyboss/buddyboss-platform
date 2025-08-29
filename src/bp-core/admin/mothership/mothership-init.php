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

	// Include GroundLevel framework files first.
	require_once __DIR__ . '/ground-level/container/Container.php';
	require_once __DIR__ . '/ground-level/container/Service.php';
	require_once __DIR__ . '/ground-level/container/Concerns/HasStaticContainer.php';
	require_once __DIR__ . '/ground-level/container/Contracts/StaticContainerAwareness.php';

	// Include GroundLevel mothership framework files.
	require_once __DIR__ . '/ground-level/mothership/AbstractPluginConnection.php';
	require_once __DIR__ . '/ground-level/mothership/Service.php';
	require_once __DIR__ . '/ground-level/mothership/Credentials.php';
	require_once __DIR__ . '/ground-level/mothership/Api/Request.php';
	require_once __DIR__ . '/ground-level/mothership/Api/Response.php';
	require_once __DIR__ . '/ground-level/mothership/Api/Request/LicenseActivations.php';
	require_once __DIR__ . '/ground-level/mothership/Api/Request/Products.php';
	require_once __DIR__ . '/ground-level/mothership/Manager/LicenseManager.php';
	require_once __DIR__ . '/ground-level/mothership/Manager/AddonsManager.php';
	require_once __DIR__ . '/ground-level/mothership/Manager/AddonInstallSkin.php';

	// Include BuddyBoss specific files.
	require_once __DIR__ . '/class-bb-plugin-connector.php';
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