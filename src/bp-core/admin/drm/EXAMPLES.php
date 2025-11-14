<?php
/**
 * BuddyBoss DRM Integration Examples
 *
 * Copy-paste ready code snippets for integrating add-on plugins with the DRM system.
 * DO NOT include this file in production - it's for reference only.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// ============================================================================
// EXAMPLE 1: Platform Pro Integration
// ============================================================================

/**
 * Add this to: buddyboss-platform-pro/class-bb-platform-pro.php
 * In the __construct() method, after constants() and setup_globals()
 */
function example_platform_pro_drm_init() {
	// Register with DRM system.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::register_addon(
			'buddyboss-platform-pro',
			'BuddyBoss Platform Pro',
			array(
				'version' => '2.10.1', // Use actual version variable.
				'file'    => BB_PLATFORM_PRO_PLUGIN_FILE,
			)
		);
	}

	// Check license before loading features.
	if ( $this->should_load_features() ) {
		$this->includes();
		$this->setup_actions();
	} else {
		// Setup lockout hooks instead.
		add_action( 'admin_notices', array( $this, 'display_lockout_notice' ) );
	}
}

/**
 * Add this method to the BB_Platform_Pro class
 */
function example_platform_pro_should_load() {
	if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		// Fall back to existing license check.
		return function_exists( 'bbp_pro_is_license_valid' ) && bbp_pro_is_license_valid();
	}

	// Use centralized DRM.
	return ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' );
}

/**
 * Add this method to display lockout notice
 */
function example_platform_pro_lockout_notice() {
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::display_lockout_notice(
			'buddyboss-platform-pro',
			'admin_notice'
		);
	}
}

// ============================================================================
// EXAMPLE 2: Sharing Plugin Integration
// ============================================================================

/**
 * Add this to: buddyboss-sharing/buddyboss-sharing.php
 * In the init() method of BuddyBoss_Sharing class
 */
function example_sharing_drm_init() {
	// Register with DRM.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::register_addon(
			'buddyboss-sharing',
			'BuddyBoss Sharing',
			array(
				'version' => BUDDYBOSS_SHARING_VERSION,
				'file'    => BUDDYBOSS_SHARING_PLUGIN_FILE,
			)
		);
	}

	// Check if features should load.
	if ( ! $this->can_load_features() ) {
		add_action( 'admin_notices', array( $this, 'show_lockout_notice' ) );
		return; // Stop initialization.
	}

	// Continue normal initialization.
	$this->load_core();
	$this->load_admin();
}

/**
 * Add this method to BuddyBoss_Sharing class
 */
function example_sharing_can_load() {
	// Use centralized DRM if available.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		return ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-sharing' );
	}

	// Fall back to local License_Manager.
	return \BuddyBoss\Sharing\Core\License_Manager::instance()->is_valid();
}

// ============================================================================
// EXAMPLE 3: Gamification Plugin Integration
// ============================================================================

/**
 * Example for a new add-on that doesn't have license checking yet
 */
function example_gamification_drm_integration() {
	// In your main plugin class __construct() or init():

	// Step 1: Register with DRM.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::register_addon(
			'buddyboss-gamification',
			'BuddyBoss Gamification',
			array(
				'version' => '1.0.0',
				'file'    => __FILE__,
			)
		);
	}

	// Step 2: Conditional feature loading.
	if ( $this->is_licensed() ) {
		$this->load_points_system();
		$this->load_badges();
		$this->load_leaderboards();
	} else {
		$this->show_license_notice();
	}
}

/**
 * Simple license check method
 */
function example_gamification_is_licensed() {
	if ( ! class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		return false; // Require Platform DRM.
	}

	return ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-gamification' );
}

// ============================================================================
// EXAMPLE 4: Conditional Feature Loading (Advanced)
// ============================================================================

/**
 * Load different feature sets based on license status
 */
function example_conditional_features() {
	$product_slug = 'buddyboss-membership';

	// Check DRM status.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		$drm    = \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::get_addon_drm( $product_slug );
		$locked = $drm && $drm->should_lock_features();

		if ( $locked ) {
			// Load only basic/free features.
			$this->load_free_memberships();
		} else {
			// Load full premium features.
			$this->load_free_memberships();
			$this->load_paid_memberships();
			$this->load_recurring_payments();
			$this->load_membership_levels();
		}
	}
}

// ============================================================================
// EXAMPLE 5: Admin Page Lockout
// ============================================================================

/**
 * Lock specific admin pages when license is invalid
 */
function example_admin_page_lockout() {
	// In your admin page callback:
	$product_slug = 'buddyboss-docs';

	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) &&
	     \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( $product_slug ) ) {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'BuddyBoss Docs Settings', 'buddyboss' ); ?></h1>
			<?php
			\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::display_lockout_notice(
				$product_slug,
				'modal'
			);
			?>
		</div>
		<?php
		return; // Don't render settings.
	}

	// Render normal settings page.
	$this->render_settings_page();
}

// ============================================================================
// EXAMPLE 6: REST API Endpoint Protection
// ============================================================================

/**
 * Protect REST API endpoints with license check
 */
function example_rest_api_protection() {
	register_rest_route(
		'buddyboss/v1',
		'/premium-feature',
		array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'premium_endpoint_callback' ),
			'permission_callback' => array( $this, 'check_premium_permission' ),
		)
	);
}

/**
 * Permission callback that checks license
 */
function example_check_premium_permission() {
	// Check user permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Check license.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		$locked = \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'your-product-slug' );
		return ! $locked;
	}

	return false;
}

// ============================================================================
// EXAMPLE 7: Shortcode Protection
// ============================================================================

/**
 * Return license notice instead of rendering shortcode
 */
function example_shortcode_protection( $atts ) {
	$product_slug = 'buddyboss-elementor-sections';

	// Check if features are locked.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) &&
	     \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( $product_slug ) ) {

		// Return message for admins, nothing for regular users.
		if ( current_user_can( 'manage_options' ) ) {
			return \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::get_lockout_message( $product_slug );
		}

		return '';
	}

	// Render shortcode normally.
	return $this->render_premium_shortcode( $atts );
}

// ============================================================================
// EXAMPLE 8: Widget Lockout
// ============================================================================

/**
 * Display lockout message in widget instead of content
 */
function example_widget_lockout( $args, $instance ) {
	$product_slug = 'buddyboss-app';

	// Check license.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) &&
	     \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( $product_slug ) ) {

		echo $args['before_widget'];
		echo $args['before_title'] . esc_html__( 'License Required', 'buddyboss' ) . $args['after_title'];
		echo '<p>' . esc_html__( 'This widget requires an active license.', 'buddyboss' ) . '</p>';
		echo $args['after_widget'];
		return;
	}

	// Render widget normally.
	echo $args['before_widget'];
	// ... widget content.
	echo $args['after_widget'];
}

// ============================================================================
// EXAMPLE 9: AJAX Action Protection
// ============================================================================

/**
 * Protect AJAX actions with license check
 */
function example_ajax_protection() {
	add_action( 'wp_ajax_premium_action', array( $this, 'handle_premium_ajax' ) );
}

function example_handle_premium_ajax() {
	// Check nonce, etc.
	check_ajax_referer( 'premium_nonce' );

	// Check license.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) &&
	     \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'your-product-slug' ) ) {
		wp_send_json_error( array( 'message' => __( 'License required', 'buddyboss' ) ) );
	}

	// Process AJAX action.
	$result = $this->do_premium_action();
	wp_send_json_success( $result );
}

// ============================================================================
// EXAMPLE 10: Feature Flag System
// ============================================================================

/**
 * Use DRM as a feature flag system
 */
function example_feature_flags() {
	// Get DRM instance.
	$drm = \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::get_addon_drm( 'buddyboss-pusher' );

	if ( ! $drm || $drm->should_lock_features() ) {
		// Features locked - use fallback.
		return new BasicNotificationSystem();
	}

	// Features unlocked - use premium version.
	return new PusherNotificationSystem();
}

// ============================================================================
// EXAMPLE 11: Replace Existing bbp_pro_is_license_valid()
// ============================================================================

/**
 * Updated version of bbp_pro_is_license_valid() that uses DRM
 * Replace in: buddyboss-platform-pro/includes/bb-pro-core-functions.php
 */
function bbp_pro_is_license_valid() {
	// Use centralized DRM if available.
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
		return ! \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'buddyboss-platform-pro' );
	}

	// Fall back to old method for backward compatibility.
	if ( bb_pro_check_staging_server() ) {
		return true;
	}

	$license_exists = false;
	if ( class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Plugin_Connector' ) ) {
		$connector      = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector();
		$license_status = $connector->getLicenseActivationStatus();

		if ( ! empty( $license_status ) &&
		     class_exists( '\\BuddyBoss\\Core\\Admin\\Mothership\\BB_Addons_Manager' ) &&
		     \BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::checkProductBySlug( 'buddyboss-platform-pro' ) ) {
			$license_exists = true;
		}
	}

	return $license_exists;
}

// ============================================================================
// EXAMPLE 12: Minimal Integration (Copy & Paste)
// ============================================================================

/**
 * MINIMAL INTEGRATION - Just 3 steps!
 *
 * Step 1: Register (add to __construct or init)
 */
if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) ) {
	\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::register_addon(
		'YOUR-PRODUCT-SLUG',     // Change this
		'Your Plugin Name',       // Change this
		array( 'version' => '1.0.0' )
	);
}

/**
 * Step 2: Check before loading (add before includes/features)
 */
if ( class_exists( '\\BuddyBoss\\Core\\Admin\\DRM\\BB_DRM_Registry' ) &&
     \BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::should_lock_addon_features( 'YOUR-PRODUCT-SLUG' ) ) {
	add_action( 'admin_notices', function() {
		\BuddyBoss\Core\Admin\DRM\BB_DRM_Registry::display_lockout_notice( 'YOUR-PRODUCT-SLUG', 'admin_notice' );
	});
	return; // Stop loading features
}

/**
 * Step 3: Continue normal initialization if license is valid
 * (Your existing code goes here)
 */
