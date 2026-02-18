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
		spl_autoload_register( array( __CLASS__, 'bb_autoload' ), true, false );
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
		// Fast prefix check: only process classes that look like BuddyBoss classes.
		// This avoids running regex + filter logic for every non-BB class autoload.
		$upper_class = strtoupper( substr( $class, 0, 3 ) );
		if ( 'BB_' !== $upper_class && 'BB\\' !== $upper_class && 'BP_' !== $upper_class && 'BP\\' !== $upper_class ) {
			return false;
		}

		// SECURITY: Use strict regex patterns to prevent class name injection.
		// Only allow alphanumeric, underscore, and backslash in class names.
		if ( ! preg_match( '/^[a-zA-Z0-9_\\\\]+$/', $class ) ) {
			return false; // Reject invalid class names.
		}

		// Get feature class mappings (cached after first call).
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

		// Invalidate cached map so bb_get_feature_class_map() picks up the new mapping.
		self::bb_invalidate_class_map_cache();
	}

	/**
	 * Invalidate the cached feature class map.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private static function bb_invalidate_class_map_cache() {
		// Reset the static cache in bb_get_feature_class_map().
		// We use a flag that the method checks.
		self::$class_map_cache_dirty = true;
	}

	/**
	 * Flag indicating the class map cache needs refresh.
	 *
	 * @since BuddyBoss [BBVERSION]
	 * @var bool
	 */
	private static $class_map_cache_dirty = false;

	/**
	 * Get feature class mappings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array Feature class mappings.
	 */
	public static function bb_get_feature_class_map() {
		// Cache the filtered result to avoid re-running apply_filters on every autoload call.
		static $cached_map = null;

		if ( null === $cached_map || self::$class_map_cache_dirty ) {
			self::$class_map_cache_dirty = false;
			/**
			 * Filter feature class mappings.
			 *
			 * @since BuddyBoss [BBVERSION]
			 *
			 * @param array $feature_class_map Feature class mappings.
			 */
			$cached_map = apply_filters( 'bb_feature_class_map', self::$feature_class_map );
		}

		return $cached_map;
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

		// Try to use cached config file paths to avoid glob() on every page load.
		$config_files = get_transient( 'bb_feature_config_paths' );

		if ( false === $config_files ) {
			$config_files = array();

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

					if ( file_exists( $config_file ) ) {
						$config_files[] = $config_file;
					}
				}
			}

			// Cache for 1 week; busted by plugin activation/upgrade via bb_clear_feature_discovery_cache().
			set_transient( 'bb_feature_config_paths', $config_files, WEEK_IN_SECONDS );
		}

		// Load each discovered config file.
		foreach ( $config_files as $config_file ) {
			if ( file_exists( $config_file ) ) {
				require_once $config_file;

				$feature_dir = dirname( $config_file );

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

		/**
		 * Fires after all features have been discovered and loaded.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_features_discovered' );
	}

	/**
	 * Clear the cached feature discovery paths.
	 *
	 * Should be called when plugins are activated/deactivated/upgraded
	 * or when the feature directory structure changes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function bb_clear_feature_discovery_cache() {
		delete_transient( 'bb_feature_config_paths' );
	}
}
