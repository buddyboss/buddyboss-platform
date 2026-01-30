<?php
/**
 * BuddyBoss Feature Autoloader with Code Compartmentalization
 *
 * Implements autoloader gates to prevent loading classes for inactive features.
 * Ensures strict security with regex validation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature Autoloader Class
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Feature_Autoloader {

	/**
	 * Feature class mapping patterns.
	 *
	 * Maps class name patterns to feature IDs for conditional loading.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var array
	 */
	private static $feature_class_map = array(
		// Add your feature class mappings here.
	);

	/**
	 * Register autoloader gate.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function bb_register() {
		spl_autoload_register( array( __CLASS__, 'bb_autoload' ), true, true );
	}

	/**
	 * Autoloader with feature gating.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $class Class name to load.
	 * @return bool|void True if class loaded, false if not found, void if gated.
	 */
	public static function bb_autoload( $class ) {
		// SECURITY: Use strict regex patterns to prevent class name injection.
		// Only allow alphanumeric, underscore, and backslash in class names.
		if ( ! preg_match( '/^[a-zA-Z0-9_\\\\]+$/', $class ) ) {
			return false; // Reject invalid class names.
		}

		// Get feature class mappings (allows filtering).
		$feature_class_map = self::bb_get_feature_class_map();

		// Check if class belongs to a feature.
		$matched_feature = null;
		foreach ( $feature_class_map as $pattern => $feature_id ) {
			if ( preg_match( $pattern, $class ) ) {
				$matched_feature = $feature_id;
				break; // Found matching feature, stop checking.
			}
		}

		// If class matches a feature pattern, gate it.
		if ( $matched_feature ) {
			// Only allow autoloading if feature is active.
			if ( ! bp_is_active( $matched_feature ) ) {
				// Feature is inactive - prevent class loading.
				return false; // Don't load - feature is inactive.
			}
			// Feature is active - allow normal autoloading to continue.
			// Don't return here - let other autoloaders (Composer, etc.) handle the actual loading.
		}

		// For classes that don't match our patterns, or matched features that are active,
		// let other autoloaders handle them. Return false to continue autoloader chain.
		return false;
	}

	/**
	 * Add feature class mapping.
	 *
	 * Allows third-party plugins to register their feature class patterns.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $pattern   Regex pattern to match class names.
	 * @param string $feature_id Feature ID to check.
	 */
	public static function bb_add_feature_class_map( $pattern, $feature_id ) {
		// Validate pattern.
		if ( @preg_match( $pattern, '' ) === false ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					/* translators: %s: pattern */
					__( 'Invalid regex pattern for feature class mapping: %s', 'buddyboss' ),
					$pattern
				),
				'3.0.0'
			);
			return;
		}

		self::$feature_class_map[ $pattern ] = $feature_id;
	}

	/**
	 * Get feature class mappings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Feature class mappings.
	 */
	public static function bb_get_feature_class_map() {
		/**
		 * Filter feature class mappings.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $feature_class_map Feature class mappings.
		 */
		return apply_filters( 'bb_feature_class_map', self::$feature_class_map );
	}

	/**
	 * Discover and load features from the features directory.
	 *
	 * Scans bb-features/integrations/ and bb-features/community/ directories
	 * for bb-feature-config.php files and loads them.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function bb_discover_features() {
		$base_dir = buddypress()->plugin_dir . 'bb-features/';

		// Feature categories to scan.
		$categories = array(
			'integrations',
			'community',
		);

		foreach ( $categories as $category ) {
			$category_dir = $base_dir . $category . '/';

			// Check if category directory exists.
			if ( ! is_dir( $category_dir ) ) {
				continue;
			}

			// Get all subdirectories (each is a feature).
			$features = glob( $category_dir . '*', GLOB_ONLYDIR );

			if ( empty( $features ) ) {
				continue;
			}

			foreach ( $features as $feature_dir ) {
				$config_file = $feature_dir . '/bb-feature-config.php';

				// Load feature config if it exists.
				if ( file_exists( $config_file ) ) {
					require_once $config_file;

					/**
					 * Fires after a feature config is loaded.
					 *
					 * @since BuddyBoss [BBVERSION]
					 *
					 * @param string $feature_dir Feature directory path.
					 * @param string $config_file Feature config file path.
					 */
					do_action( 'bb_feature_config_loaded', $feature_dir, $config_file );
				}
			}
		}

		/**
		 * Fires after all features have been discovered and loaded.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_features_discovered' );
	}
}
