<?php
/**
 * BuddyBoss Feature Registry
 *
 * Central registry for managing features, side panels, sections, fields, and navigation items
 * in the new feature-based admin architecture.
 *
 * Hierarchy:
 * - Feature (e.g., Activity)
 *   - Side Panel (sidebar navigation item, e.g., "Activity Settings", "Activity Feed")
 *     - Section (card/box containing fields)
 *       - Field (individual setting)
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BB_Feature_Registry class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Feature_Registry {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Feature_Registry
	 */
	private static $instance = null;

	/**
	 * Registered features.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $features = array();

	/**
	 * Registered side panels by feature.
	 * Structure: $side_panels[ $feature_id ][ $side_panel_id ] = array( ... )
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $side_panels = array();

	/**
	 * Registered sections by feature and side panel.
	 * Structure: $sections[ $feature_id ][ $side_panel_id ][ $section_id ] = array( ... )
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $sections = array();

	/**
	 * Registered fields by feature, side panel, and section.
	 * Structure: $fields[ $feature_id ][ $side_panel_id ][ $section_id ][ $field_name ] = array( ... )
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $fields = array();

	/**
	 * Registered navigation items by feature.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $nav_items = array();

	/**
	 * Feature dependency graph (for circular dependency detection).
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $dependency_graph = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @return BB_Feature_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function __construct() {
		// Fire hook for core features to register.
		add_action( 'bp_loaded', array( $this, 'bb_init' ), 5 );
	}

	/**
	 * Initialize the registry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_init() {
		/**
		 * Fired before core features are registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_before_register_features' );

		/**
		 * Fired to register core BuddyBoss features.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_register_features' );

		/**
		 * Fired after all features are registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		do_action( 'bb_after_register_features' );
	}

	/**
	 * Register a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Unique feature identifier (alphanumeric, underscore, hyphen).
	 * @param array  $args {
	 *     Feature arguments.
	 *
	 *     @type string   $label                 Feature label (required).
	 *     @type string   $description           Feature description.
	 *     @type string   $icon                  Icon identifier (Dashicon slug, SVG URL, or registered icon ID).
	 *     @type string   $category              Category: 'community', 'add-ons', 'integrations' (default: 'community').
	 *     @type string   $license_tier          License tier: 'free', 'pro', 'plus' (default: 'free').
	 *     @type callable $is_available_callback Callback to check if feature is available (license check).
	 *     @type callable $is_active_callback    Callback to check if feature is active (returns bool).
	 *     @type string   $settings_route        React route for settings page (e.g., '/settings/activity').
	 *     @type callable $php_loader            Callback to load PHP code only if feature is active.
	 *     @type callable $admin_loader          Callback to load admin-specific code only if feature is active.
	 *     @type callable $rest_loader           Callback to load REST API code only if feature is active.
	 *     @type string   $component             Legacy component ID this feature maps to (for backward compatibility).
	 *     @type array    $depends_on            Array of feature IDs this feature depends on.
	 *     @type int      $order                 Display order (default: 100).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function bb_register_feature( $feature_id, $args = array() ) {
		// Validate feature ID.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $feature_id ) ) {
			return new WP_Error(
				'invalid_feature_id',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Invalid feature ID "%s". Feature IDs must contain only alphanumeric characters, underscores, and hyphens.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Check for conflicts.
		if ( isset( $this->features[ $feature_id ] ) ) {
			// Auto-append unique suffix.
			$suffix      = 1;
			$original_id = $feature_id;
			while ( isset( $this->features[ $feature_id ] ) ) {
				$feature_id = $original_id . '_' . $suffix;
				++$suffix;
			}
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: original feature ID, 2: new feature ID */
					__( 'Feature ID "%1$s" already registered. Using "%2$s" instead.', 'buddyboss' ),
					$original_id,
					$feature_id
				),
				'3.0.0'
			);
		}

		// Validate required args.
		if ( empty( $args['label'] ) ) {
			return new WP_Error(
				'missing_feature_label',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" must have a label.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Set defaults.
		$defaults = array(
			'label'                 => '',
			'description'           => '',
			'icon'                  => 'dashicons-admin-plugins',
			'category'              => 'community',
			'license_tier'          => 'free',
			'is_available_callback' => null,
			'is_active_callback'    => null,
			'settings_route'        => '/settings/' . $feature_id,
			'php_loader'            => null,
			'admin_loader'          => null,
			'rest_loader'           => null,
			'component'             => null, // Legacy component mapping (single component).
			'components'            => array(), // Multiple components controlled by this feature.
			'integration_id'        => null, // Integration ID (for integration features).
			'standalone'            => false, // Whether this is a standalone feature.
			'depends_on'            => array(),
			'order'                 => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate dependencies.
		if ( ! empty( $args['depends_on'] ) ) {
			// Build dependency graph for circular detection.
			$this->dependency_graph[ $feature_id ] = $args['depends_on'];

			// Check for circular dependencies.
			if ( $this->bb_has_circular_dependency( $feature_id ) ) {
				return new WP_Error(
					'circular_dependency',
					sprintf(
						/* translators: %s: feature ID */
						__( 'Feature "%s" has circular dependencies.', 'buddyboss' ),
						$feature_id
					)
				);
			}
		}

		// Register feature.
		$this->features[ $feature_id ] = $args;

		// Register loaders with Feature Loader if available.
		$this->bb_register_feature_loaders( $feature_id, $args );

		/**
		 * Fired after a feature is registered.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param string $feature_id Feature ID.
		 * @param array  $args       Feature arguments.
		 */
		do_action( 'bb_feature_registered', $feature_id, $args );

		return true;
	}

	/**
	 * Register feature loaders with the Feature Loader.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $args       Feature arguments.
	 */
	private function bb_register_feature_loaders( $feature_id, $args ) {
		// Check if BB_Feature_Loader is available.
		if ( ! function_exists( 'bb_feature_loader' ) ) {
			return;
		}

		$loader = bb_feature_loader();

		// Register PHP loader.
		if ( ! empty( $args['php_loader'] ) && is_callable( $args['php_loader'] ) ) {
			$loader->bb_register_php_loader( $feature_id, $args['php_loader'] );
		}

		// Register admin loader.
		if ( ! empty( $args['admin_loader'] ) && is_callable( $args['admin_loader'] ) ) {
			$loader->bb_register_admin_loader( $feature_id, $args['admin_loader'] );
		}

		// Register REST loader.
		if ( ! empty( $args['rest_loader'] ) && is_callable( $args['rest_loader'] ) ) {
			$loader->bb_register_rest_loader( $feature_id, $args['rest_loader'] );
		}
	}

	/**
	 * Get all features.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter features.
	 *
	 *     @type string $status   Filter by status: 'active', 'inactive', 'all' (default: 'all').
	 *     @type string $category Filter by category.
	 *     @type string $search   Search term.
	 * }
	 * @return array Array of features.
	 */
	public function bb_get_features( $args = array() ) {
		$defaults = array(
			'status'   => 'all',
			'category' => '',
			'search'   => '',
		);

		$args     = wp_parse_args( $args, $defaults );
		$features = $this->features;

		// Filter by status.
		if ( 'all' !== $args['status'] ) {
			$features = array_filter(
				$features,
				function ( $feature ) use ( $args ) {
					$is_active = $this->bb_is_feature_active( array_search( $feature, $this->features, true ) );
					return ( 'active' === $args['status'] && $is_active ) || ( 'inactive' === $args['status'] && ! $is_active );
				}
			);
		}

		// Filter by category.
		if ( ! empty( $args['category'] ) ) {
			$features = array_filter(
				$features,
				function ( $feature ) use ( $args ) {
					return isset( $feature['category'] ) && $feature['category'] === $args['category'];
				}
			);
		}

		// Filter by search.
		if ( ! empty( $args['search'] ) && strlen( $args['search'] ) >= 2 ) {
			$search_lower = strtolower( $args['search'] );
			$features     = array_filter(
				$features,
				function ( $feature ) use ( $search_lower ) {
					return stripos( $feature['label'], $search_lower ) !== false
					|| ( isset( $feature['description'] ) && stripos( $feature['description'], $search_lower ) !== false );
				}
			);
		}

		// Sort by order.
		uasort(
			$features,
			function ( $a, $b ) {
				$a_order = isset( $a['order'] ) ? $a['order'] : 100;
				$b_order = isset( $b['order'] ) ? $b['order'] : 100;
				return $a_order - $b_order;
			}
		);

		return $features;
	}

	/**
	 * Check if a feature is active.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool True if active, false otherwise.
	 */
	public function bb_is_feature_active( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		$feature = $this->features[ $feature_id ];

		// Use callback if provided (for special cases like add-ons with their own activation logic).
		if ( ! is_null( $feature['is_active_callback'] ) ) {
			return (bool) call_user_func( $feature['is_active_callback'] );
		}

		// Primary storage: bb-active-features option (single source of truth).
		$active_features = bp_get_option( 'bb-active-features', array() );

		// If feature state exists in bb-active-features, use it.
		if ( isset( $active_features[ $feature_id ] ) ) {
			return ! empty( $active_features[ $feature_id ] );
		}

		// Migration fallback: If not in bb-active-features, check legacy bp-active-components.
		// This provides backward compatibility during migration.
		$active_components = bp_get_option( 'bp-active-components', array() );
		if ( isset( $active_components[ $feature_id ] ) ) {
			return ! empty( $active_components[ $feature_id ] );
		}

		// Default: feature is inactive.
		return false;
	}
}
