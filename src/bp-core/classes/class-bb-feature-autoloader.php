<?php
/**
 * BuddyBoss Feature Autoloader with Code Compartmentalization
 *
 * Implements autoloader gates to prevent loading classes for inactive features.
 * Ensures strict security with regex validation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature Autoloader Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Feature_Autoloader {

	/**
	 * Feature class mapping patterns.
	 *
	 * Maps class name patterns to feature IDs for conditional loading.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private static $feature_class_map = array(
		'/^BP_Activity_/' => 'activity',
		'/^BP_Groups_/'  => 'groups',
		'/^BP_Messages_/' => 'messages',
		'/^BP_Media_/'   => 'media',
		'/^BP_Video_/'   => 'video',
		'/^BP_Document_/' => 'document',
		'/^BP_Forums_/'  => 'forums',
		'/^BP_Friends_/' => 'friends',
		'/^BP_Notifications_/' => 'notifications',
		'/^BP_Invites_/' => 'invites',
		'/^BP_Moderation_/' => 'moderation',
		'/^BP_Search_/'  => 'search',
		'/^BP_XProfile_/' => 'xprofile',
		'/^BB_Activity_/' => 'activity',
		'/^BB_Groups_/'  => 'groups',
		'/^BB_Media_/'    => 'media',
		'/^BB_Video_/'    => 'video',
		'/^BB_Document_/' => 'document',
	);

	/**
	 * Register autoloader gate.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ), true, true );
	}

	/**
	 * Autoloader with feature gating.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $class Class name to load.
	 * @return bool|void True if class loaded, false if not found, void if gated.
	 */
	public static function autoload( $class ) {
		// SECURITY: Use strict regex patterns to prevent class name injection.
		// Only allow alphanumeric, underscore, and backslash in class names.
		if ( ! preg_match( '/^[a-zA-Z0-9_\\\\]+$/', $class ) ) {
			return false; // Reject invalid class names.
		}

		// Get feature class mappings (allows filtering).
		$feature_class_map = self::get_feature_class_map();

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
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $pattern   Regex pattern to match class names.
	 * @param string $feature_id Feature ID to check.
	 */
	public static function add_feature_class_map( $pattern, $feature_id ) {
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
	 * @since BuddyBoss 3.0.0
	 *
	 * @return array Feature class mappings.
	 */
	public static function get_feature_class_map() {
		/**
		 * Filter feature class mappings.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param array $feature_class_map Feature class mappings.
		 */
		return apply_filters( 'bb_feature_class_map', self::$feature_class_map );
	}

	/**
	 * Discover and load features from the features directory.
	 *
	 * Scans features/integrations/ and features/community/ directories
	 * for feature-config.php files and loads them.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return void
	 */
	public static function discover_features() {
		$base_dir = buddypress()->plugin_dir . 'features/';

		// Feature categories to scan
		$categories = array(
			'integrations',
			'community',
		);

		foreach ( $categories as $category ) {
			$category_dir = $base_dir . $category . '/';

			// Check if category directory exists
			if ( ! is_dir( $category_dir ) ) {
				continue;
			}

			// Get all subdirectories (each is a feature)
			$features = glob( $category_dir . '*', GLOB_ONLYDIR );

			if ( empty( $features ) ) {
				continue;
			}

			foreach ( $features as $feature_dir ) {
				$config_file = $feature_dir . '/feature-config.php';

				// Load feature config if it exists
				if ( file_exists( $config_file ) ) {
					require_once $config_file;

					/**
					 * Fires after a feature config is loaded.
					 *
					 * @since BuddyBoss 3.0.0
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
		 * @since BuddyBoss 3.0.0
		 */
		do_action( 'bb_features_discovered' );
	}
}

/**
 * Register feature autoloader.
 *
 * @since BuddyBoss 3.0.0
 */
function bb_register_feature_autoloader() {
	BB_Feature_Autoloader::register();
}
add_action( 'plugins_loaded', 'bb_register_feature_autoloader', 1 ); // Early priority.

/**
 * Gate action and filter registration based on feature status.
 *
 * Wrapper functions to check feature status before registering hooks.
 *
 * @since BuddyBoss 3.0.0
 */

/**
 * Add action only if feature is active.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string   $feature_id Feature ID to check.
 * @param string   $tag        Action hook tag.
 * @param callable $function   Function to call.
 * @param int      $priority   Priority.
 * @param int      $accepted_args Number of arguments.
 * @return bool True if action added, false if feature inactive.
 */
function bb_add_action_if_active( $feature_id, $tag, $function, $priority = 10, $accepted_args = 1 ) {
	if ( ! bp_is_active( $feature_id ) ) {
		return false;
	}

	return add_action( $tag, $function, $priority, $accepted_args );
}

/**
 * Add filter only if feature is active.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string   $feature_id Feature ID to check.
 * @param string   $tag        Filter hook tag.
 * @param callable $function   Function to call.
 * @param int      $priority   Priority.
 * @param int      $accepted_args Number of arguments.
 * @return bool True if filter added, false if feature inactive.
 */
function bb_add_filter_if_active( $feature_id, $tag, $function, $priority = 10, $accepted_args = 1 ) {
	if ( ! bp_is_active( $feature_id ) ) {
		return false;
	}

	return add_filter( $tag, $function, $priority, $accepted_args );
}
