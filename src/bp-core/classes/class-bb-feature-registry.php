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
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Feature Registry Class
 *
 * @since BuddyBoss 3.0.0
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
		add_action( 'bp_loaded', array( $this, 'init' ), 5 );
	}

	/**
	 * Initialize the registry.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function init() {
		/**
		 * Fired before core features are registered.
		 *
		 * @since BuddyBoss 3.0.0
		 */
		do_action( 'bb_before_register_features' );

		/**
		 * Fired to register core BuddyBoss features.
		 *
		 * @since BuddyBoss 3.0.0
		 */
		do_action( 'bb_register_features' );

		/**
		 * Fired after all features are registered.
		 *
		 * @since BuddyBoss 3.0.0
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
	 *     @type array    $depends_on            Array of feature IDs this feature depends on.
	 *     @type int      $order                 Display order (default: 100).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_feature( $feature_id, $args = array() ) {
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
			$suffix = 1;
			$original_id = $feature_id;
			while ( isset( $this->features[ $feature_id ] ) ) {
				$feature_id = $original_id . '_' . $suffix;
				$suffix++;
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
			'depends_on'            => array(),
			'order'                 => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Validate dependencies.
		if ( ! empty( $args['depends_on'] ) ) {
			// Build dependency graph for circular detection.
			$this->dependency_graph[ $feature_id ] = $args['depends_on'];

			// Check for circular dependencies.
			if ( $this->has_circular_dependency( $feature_id ) ) {
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

		// Initialize side panels array for this feature.
		if ( ! isset( $this->side_panels[ $feature_id ] ) ) {
			$this->side_panels[ $feature_id ] = array();
		}

		// Initialize sections array for this feature.
		if ( ! isset( $this->sections[ $feature_id ] ) ) {
			$this->sections[ $feature_id ] = array();
		}

		// Initialize fields array for this feature.
		if ( ! isset( $this->fields[ $feature_id ] ) ) {
			$this->fields[ $feature_id ] = array();
		}

		// Initialize nav items array for this feature.
		if ( ! isset( $this->nav_items[ $feature_id ] ) ) {
			$this->nav_items[ $feature_id ] = array();
		}

		/**
		 * Fired after a feature is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 * @param array  $args       Feature arguments.
		 */
		do_action( 'bb_feature_registered', $feature_id, $args );

		return true;
	}

	/**
	 * Register a side panel for a feature.
	 *
	 * Side panels appear in the left sidebar navigation when viewing feature settings.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id    Feature ID.
	 * @param string $side_panel_id Side panel ID.
	 * @param array  $args {
	 *     Side panel arguments.
	 *
	 *     @type string $title       Side panel title (required).
	 *     @type string $icon        Icon (dashicon slug, SVG URL, or icon array).
	 *     @type string $help_url    Help documentation URL.
	 *     @type int    $order       Display order (default: 100).
	 *     @type bool   $is_default  Whether this is the default panel (default: false).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_side_panel( $feature_id, $side_panel_id, $args = array() ) {
		// Validate feature exists.
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found. Register the feature first.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Validate side panel ID.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $side_panel_id ) ) {
			return new WP_Error(
				'invalid_side_panel_id',
				sprintf(
					/* translators: %s: side panel ID */
					__( 'Invalid side panel ID "%s". IDs must contain only alphanumeric characters, underscores, and hyphens.', 'buddyboss' ),
					$side_panel_id
				)
			);
		}

		// Check for conflicts.
		if ( isset( $this->side_panels[ $feature_id ][ $side_panel_id ] ) ) {
			// Auto-append unique suffix.
			$suffix = 1;
			$original_id = $side_panel_id;
			while ( isset( $this->side_panels[ $feature_id ][ $side_panel_id ] ) ) {
				$side_panel_id = $original_id . '_' . $suffix;
				$suffix++;
			}
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: original ID, 2: new ID */
					__( 'Side panel ID "%1$s" already registered for feature. Using "%2$s" instead.', 'buddyboss' ),
					$original_id,
					$side_panel_id
				),
				'3.0.0'
			);
		}

		// Validate required args.
		if ( empty( $args['title'] ) ) {
			return new WP_Error(
				'missing_side_panel_title',
				sprintf(
					/* translators: %s: side panel ID */
					__( 'Side panel "%s" must have a title.', 'buddyboss' ),
					$side_panel_id
				)
			);
		}

		// Set defaults.
		$defaults = array(
			'title'      => '',
			'icon'       => null,
			'help_url'   => '',
			'order'      => 100,
			'is_default' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		// Register side panel.
		$this->side_panels[ $feature_id ][ $side_panel_id ] = $args;

		// Initialize sections array for this side panel.
		if ( ! isset( $this->sections[ $feature_id ][ $side_panel_id ] ) ) {
			$this->sections[ $feature_id ][ $side_panel_id ] = array();
		}

		// Initialize fields array for this side panel.
		if ( ! isset( $this->fields[ $feature_id ][ $side_panel_id ] ) ) {
			$this->fields[ $feature_id ][ $side_panel_id ] = array();
		}

		/**
		 * Fired after a side panel is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id    Feature ID.
		 * @param string $side_panel_id Side panel ID.
		 * @param array  $args          Side panel arguments.
		 */
		do_action( 'bb_side_panel_registered', $feature_id, $side_panel_id, $args );

		return true;
	}

	/**
	 * Register a feature section.
	 *
	 * Sections are the white boxes/cards that contain fields.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id    Feature ID.
	 * @param string $side_panel_id Side panel ID.
	 * @param string $section_id    Section ID.
	 * @param array  $args {
	 *     Section arguments.
	 *
	 *     @type string $title       Section title (required).
	 *     @type string $description Section description.
	 *     @type int    $order       Display order (default: 100).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_section( $feature_id, $side_panel_id, $section_id, $args = array() ) {
		// Validate feature exists.
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found. Register the feature first.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Validate side panel exists.
		if ( ! isset( $this->side_panels[ $feature_id ][ $side_panel_id ] ) ) {
			return new WP_Error(
				'side_panel_not_found',
				sprintf(
					/* translators: 1: side panel ID, 2: feature ID */
					__( 'Side panel "%1$s" not found for feature "%2$s". Register the side panel first.', 'buddyboss' ),
					$side_panel_id,
					$feature_id
				)
			);
		}

		// Validate section ID.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $section_id ) ) {
			return new WP_Error(
				'invalid_section_id',
				sprintf(
					/* translators: %s: section ID */
					__( 'Invalid section ID "%s". Section IDs must contain only alphanumeric characters, underscores, and hyphens.', 'buddyboss' ),
					$section_id
				)
			);
		}

		// Check for conflicts.
		if ( isset( $this->sections[ $feature_id ][ $side_panel_id ][ $section_id ] ) ) {
			// Auto-append unique suffix.
			$suffix = 1;
			$original_id = $section_id;
			while ( isset( $this->sections[ $feature_id ][ $side_panel_id ][ $section_id ] ) ) {
				$section_id = $original_id . '_' . $suffix;
				$suffix++;
			}
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: 1: original section ID, 2: new section ID */
					__( 'Section ID "%1$s" already registered for side panel. Using "%2$s" instead.', 'buddyboss' ),
					$original_id,
					$section_id
				),
				'3.0.0'
			);
		}

		// Validate required args.
		if ( empty( $args['title'] ) ) {
			return new WP_Error(
				'missing_section_title',
				sprintf(
					/* translators: %s: section ID */
					__( 'Section "%s" must have a title.', 'buddyboss' ),
					$section_id
				)
			);
		}

		// Set defaults.
		$defaults = array(
			'title'       => '',
			'description' => '',
			'order'       => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Register section.
		$this->sections[ $feature_id ][ $side_panel_id ][ $section_id ] = $args;

		// Initialize fields array for this section.
		if ( ! isset( $this->fields[ $feature_id ][ $side_panel_id ][ $section_id ] ) ) {
			$this->fields[ $feature_id ][ $side_panel_id ][ $section_id ] = array();
		}

		/**
		 * Fired after a section is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id    Feature ID.
		 * @param string $side_panel_id Side panel ID.
		 * @param string $section_id    Section ID.
		 * @param array  $args          Section arguments.
		 */
		do_action( 'bb_feature_section_registered', $feature_id, $side_panel_id, $section_id, $args );

		return true;
	}

	/**
	 * Register a feature field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id    Feature ID.
	 * @param string $side_panel_id Side panel ID.
	 * @param string $section_id    Section ID.
	 * @param array  $args {
	 *     Field arguments.
	 *
	 *     @type string   $name              Option name (required, used as option key).
	 *     @type string   $label             Field label (required).
	 *     @type string   $type              Field type: 'toggle', 'text', 'textarea', 'select', 'radio', 'number', 'email', 'url', 'color', 'date', 'time', 'media', 'repeater', 'field_group', 'rich_text', 'code', 'checkbox_list', 'custom' (default: 'text').
	 *     @type string   $description       Field description/help text.
	 *     @type mixed    $default           Default value.
	 *     @type array    $options           Options for select/radio/checkbox_list fields.
	 *     @type callable $sanitize_callback Sanitization callback (default: based on type).
	 *     @type callable $validate_callback Validation callback.
	 *     @type array    $conditional       Conditional display logic: array( 'field' => 'field_name', 'operator' => '==', 'value' => 'value' ).
	 *     @type bool     $pro_only          Whether this field requires Pro license.
	 *     @type string   $license_tier      License tier: 'free', 'pro', 'plus'.
	 *     @type int      $order             Display order (default: 100).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_field( $feature_id, $side_panel_id, $section_id, $args = array() ) {
		// Validate feature exists.
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found. Register the feature first.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Validate side panel exists.
		if ( ! isset( $this->side_panels[ $feature_id ][ $side_panel_id ] ) ) {
			return new WP_Error(
				'side_panel_not_found',
				sprintf(
					/* translators: 1: side panel ID, 2: feature ID */
					__( 'Side panel "%1$s" not found for feature "%2$s". Register the side panel first.', 'buddyboss' ),
					$side_panel_id,
					$feature_id
				)
			);
		}

		// Validate section exists.
		if ( ! isset( $this->sections[ $feature_id ][ $side_panel_id ][ $section_id ] ) ) {
			return new WP_Error(
				'section_not_found',
				sprintf(
					/* translators: 1: section ID, 2: side panel ID */
					__( 'Section "%1$s" not found for side panel "%2$s". Register the section first.', 'buddyboss' ),
					$section_id,
					$side_panel_id
				)
			);
		}

		// Validate required args.
		if ( empty( $args['name'] ) ) {
			return new WP_Error(
				'missing_field_name',
				__( 'Field must have a name (option key).', 'buddyboss' )
			);
		}

		if ( empty( $args['label'] ) ) {
			return new WP_Error(
				'missing_field_label',
				sprintf(
					/* translators: %s: field name */
					__( 'Field "%s" must have a label.', 'buddyboss' ),
					$args['name']
				)
			);
		}

		$field_name = $args['name'];

		// Check for field name conflicts (field names are option keys, must be unique).
		foreach ( $this->fields as $fid => $side_panels ) {
			foreach ( $side_panels as $spid => $sections ) {
				foreach ( $sections as $sid => $fields ) {
					foreach ( $fields as $existing_field ) {
						if ( $existing_field['name'] === $field_name && ( $fid !== $feature_id || $spid !== $side_panel_id || $sid !== $section_id ) ) {
							return new WP_Error(
								'field_name_conflict',
								sprintf(
									/* translators: %s: field name */
									__( 'Field name "%s" is already registered. Field names must be unique as they are used as option keys.', 'buddyboss' ),
									$field_name
								)
							);
						}
					}
				}
			}
		}

		// Set defaults.
		$defaults = array(
			'name'              => '',
			'label'             => '',
			'type'              => 'text',
			'description'       => '',
			'default'           => '',
			'options'           => array(),
			'sanitize_callback' => null,
			'validate_callback' => null,
			'conditional'       => null,
			'pro_only'          => false,
			'license_tier'      => 'free',
			'order'             => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Set default sanitize callback based on type if not provided.
		if ( is_null( $args['sanitize_callback'] ) ) {
			$args['sanitize_callback'] = $this->get_default_sanitize_callback( $args['type'] );
		}

		// Register field.
		$this->fields[ $feature_id ][ $side_panel_id ][ $section_id ][ $field_name ] = $args;

		/**
		 * Fired after a field is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id    Feature ID.
		 * @param string $side_panel_id Side panel ID.
		 * @param string $section_id    Section ID.
		 * @param string $field_name    Field name.
		 * @param array  $args          Field arguments.
		 */
		do_action( 'bb_feature_field_registered', $feature_id, $side_panel_id, $section_id, $field_name, $args );

		return true;
	}

	/**
	 * Register a navigation item for a feature.
	 *
	 * Navigation items appear in the sidebar but link to non-settings screens (like "All Activity" list).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @param array  $args {
	 *     Navigation item arguments.
	 *
	 *     @type string $id        Navigation item ID (required).
	 *     @type string $label     Navigation item label (required).
	 *     @type string $route     React route (required).
	 *     @type string $icon      Icon identifier.
	 *     @type int    $order     Display order (default: 100).
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_nav_item( $feature_id, $args = array() ) {
		// Validate feature exists.
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found. Register the feature first.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Validate required args.
		if ( empty( $args['id'] ) ) {
			return new WP_Error( 'missing_nav_id', __( 'Navigation item must have an ID.', 'buddyboss' ) );
		}

		if ( empty( $args['label'] ) ) {
			return new WP_Error( 'missing_nav_label', __( 'Navigation item must have a label.', 'buddyboss' ) );
		}

		if ( empty( $args['route'] ) ) {
			return new WP_Error( 'missing_nav_route', __( 'Navigation item must have a route.', 'buddyboss' ) );
		}

		$nav_id = $args['id'];

		// Check for conflicts.
		if ( isset( $this->nav_items[ $feature_id ][ $nav_id ] ) ) {
			_doing_it_wrong(
				__FUNCTION__,
				sprintf(
					/* translators: %s: navigation item ID */
					__( 'Navigation item ID "%s" already registered for feature.', 'buddyboss' ),
					$nav_id
				),
				'3.0.0'
			);
			return false;
		}

		// Set defaults.
		$defaults = array(
			'id'    => '',
			'label' => '',
			'route' => '',
			'icon'  => 'dashicons-admin-generic',
			'order' => 100,
		);

		$args = wp_parse_args( $args, $defaults );

		// Register nav item.
		$this->nav_items[ $feature_id ][ $nav_id ] = $args;

		/**
		 * Fired after a navigation item is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 * @param string $nav_id     Navigation item ID.
		 * @param array  $args       Navigation item arguments.
		 */
		do_action( 'bb_feature_nav_item_registered', $feature_id, $nav_id, $args );

		return true;
	}

	// =========================================================================
	// GETTER METHODS
	// =========================================================================

	/**
	 * Get a single feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return array|null Feature data or null if not found.
	 */
	public function get_feature( $feature_id ) {
		return isset( $this->features[ $feature_id ] ) ? $this->features[ $feature_id ] : null;
	}

	/**
	 * Get all features.
	 *
	 * @since BuddyBoss 3.0.0
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
	public function get_features( $args = array() ) {
		$defaults = array(
			'status'   => 'all',
			'category' => '',
			'search'   => '',
		);

		$args = wp_parse_args( $args, $defaults );
		$features = $this->features;

		// Filter by status.
		if ( 'all' !== $args['status'] ) {
			$features = array_filter( $features, function( $feature ) use ( $args ) {
				$is_active = $this->is_feature_active( array_search( $feature, $this->features, true ) );
				return ( 'active' === $args['status'] && $is_active ) || ( 'inactive' === $args['status'] && ! $is_active );
			} );
		}

		// Filter by category.
		if ( ! empty( $args['category'] ) ) {
			$features = array_filter( $features, function( $feature ) use ( $args ) {
				return isset( $feature['category'] ) && $feature['category'] === $args['category'];
			} );
		}

		// Filter by search.
		if ( ! empty( $args['search'] ) && strlen( $args['search'] ) >= 2 ) {
			$search_lower = strtolower( $args['search'] );
			$features = array_filter( $features, function( $feature ) use ( $search_lower ) {
				return stripos( $feature['label'], $search_lower ) !== false
					|| ( isset( $feature['description'] ) && stripos( $feature['description'], $search_lower ) !== false );
			} );
		}

		// Sort by order.
		uasort( $features, function( $a, $b ) {
			$a_order = isset( $a['order'] ) ? $a['order'] : 100;
			$b_order = isset( $b['order'] ) ? $b['order'] : 100;
			return $a_order - $b_order;
		} );

		return $features;
	}

	/**
	 * Get side panels for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return array Array of side panels.
	 */
	public function get_side_panels( $feature_id ) {
		if ( ! isset( $this->side_panels[ $feature_id ] ) ) {
			return array();
		}

		$side_panels = $this->side_panels[ $feature_id ];

		// Sort by order.
		uasort( $side_panels, function( $a, $b ) {
			$a_order = isset( $a['order'] ) ? $a['order'] : 100;
			$b_order = isset( $b['order'] ) ? $b['order'] : 100;
			return $a_order - $b_order;
		} );

		return $side_panels;
	}

	/**
	 * Get sections for a side panel.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id    Feature ID.
	 * @param string $side_panel_id Side panel ID.
	 * @return array Array of sections.
	 */
	public function get_sections( $feature_id, $side_panel_id ) {
		if ( ! isset( $this->sections[ $feature_id ][ $side_panel_id ] ) ) {
			return array();
		}

		$sections = $this->sections[ $feature_id ][ $side_panel_id ];

		// Sort by order.
		uasort( $sections, function( $a, $b ) {
			$a_order = isset( $a['order'] ) ? $a['order'] : 100;
			$b_order = isset( $b['order'] ) ? $b['order'] : 100;
			return $a_order - $b_order;
		} );

		return $sections;
	}

	/**
	 * Get all sections for a feature (across all side panels).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return array Array of sections grouped by side panel.
	 */
	public function get_all_sections( $feature_id ) {
		if ( ! isset( $this->sections[ $feature_id ] ) ) {
			return array();
		}
		return $this->sections[ $feature_id ];
	}

	/**
	 * Get fields for a section.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id    Feature ID.
	 * @param string $side_panel_id Side panel ID.
	 * @param string $section_id    Section ID. Optional. If not provided, returns all fields for the side panel.
	 * @return array Array of fields.
	 */
	public function get_fields( $feature_id, $side_panel_id, $section_id = null ) {
		if ( ! isset( $this->fields[ $feature_id ][ $side_panel_id ] ) ) {
			return array();
		}

		if ( is_null( $section_id ) ) {
			// Return all fields for the side panel.
			$all_fields = array();
			foreach ( $this->fields[ $feature_id ][ $side_panel_id ] as $section_fields ) {
				$all_fields = array_merge( $all_fields, $section_fields );
			}
			return $all_fields;
		}

		if ( ! isset( $this->fields[ $feature_id ][ $side_panel_id ][ $section_id ] ) ) {
			return array();
		}

		$fields = $this->fields[ $feature_id ][ $side_panel_id ][ $section_id ];

		// Sort by order.
		uasort( $fields, function( $a, $b ) {
			$a_order = isset( $a['order'] ) ? $a['order'] : 100;
			$b_order = isset( $b['order'] ) ? $b['order'] : 100;
			return $a_order - $b_order;
		} );

		return $fields;
	}

	/**
	 * Get all fields for a feature (across all side panels and sections).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return array Array of fields.
	 */
	public function get_all_fields( $feature_id ) {
		if ( ! isset( $this->fields[ $feature_id ] ) ) {
			return array();
		}

		$all_fields = array();
		foreach ( $this->fields[ $feature_id ] as $side_panel_fields ) {
			foreach ( $side_panel_fields as $section_fields ) {
				$all_fields = array_merge( $all_fields, $section_fields );
			}
		}

		return $all_fields;
	}

	/**
	 * Get navigation items for a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return array Array of navigation items.
	 */
	public function get_nav_items( $feature_id ) {
		return isset( $this->nav_items[ $feature_id ] ) ? $this->nav_items[ $feature_id ] : array();
	}

	// =========================================================================
	// STATUS METHODS
	// =========================================================================

	/**
	 * Check if a feature is active.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool True if active, false otherwise.
	 */
	public function is_feature_active( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		$feature = $this->features[ $feature_id ];

		// Use callback if provided.
		if ( ! is_null( $feature['is_active_callback'] ) ) {
			return (bool) call_user_func( $feature['is_active_callback'] );
		}

		// Default: check if feature is enabled via BuddyBoss active components.
		$active_components = bp_get_option( 'bp-active-components', array() );
		return isset( $active_components[ $feature_id ] ) && ! empty( $active_components[ $feature_id ] );
	}

	/**
	 * Check if a feature is available (license check).
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool True if available, false otherwise.
	 */
	public function is_feature_available( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return false;
		}

		$feature = $this->features[ $feature_id ];

		// Use callback if provided.
		if ( ! is_null( $feature['is_available_callback'] ) ) {
			return (bool) call_user_func( $feature['is_available_callback'] );
		}

		// Default: available.
		return true;
	}

	/**
	 * Activate a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function activate_feature( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Check dependencies.
		$feature = $this->features[ $feature_id ];
		if ( ! empty( $feature['depends_on'] ) ) {
			foreach ( $feature['depends_on'] as $dep_feature_id ) {
				if ( ! $this->is_feature_active( $dep_feature_id ) ) {
					return new WP_Error(
						'missing_dependencies',
						sprintf(
							/* translators: 1: feature ID, 2: dependency feature ID */
							__( 'Cannot activate feature "%1$s". Required dependency "%2$s" is not active.', 'buddyboss' ),
							$feature_id,
							$dep_feature_id
						)
					);
				}
			}
		}

		// Update active components.
		$active_components = bp_get_option( 'bp-active-components', array() );
		$active_components[ $feature_id ] = 1;
		bp_update_option( 'bp-active-components', $active_components );

		// Clear caches.
		$this->clear_feature_caches( $feature_id );

		/**
		 * Fired after a feature is activated.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 */
		do_action( 'bb_feature_activated', $feature_id );

		return true;
	}

	/**
	 * Deactivate a feature.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function deactivate_feature( $feature_id ) {
		if ( ! isset( $this->features[ $feature_id ] ) ) {
			return new WP_Error(
				'feature_not_found',
				sprintf(
					/* translators: %s: feature ID */
					__( 'Feature "%s" not found.', 'buddyboss' ),
					$feature_id
				)
			);
		}

		// Check if other features depend on this one.
		foreach ( $this->features as $fid => $f ) {
			if ( ! empty( $f['depends_on'] ) && in_array( $feature_id, $f['depends_on'], true ) ) {
				if ( $this->is_feature_active( $fid ) ) {
					return new WP_Error(
						'dependent_features',
						sprintf(
							/* translators: 1: feature ID, 2: dependent feature ID */
							__( 'Cannot deactivate feature "%1$s". Feature "%2$s" depends on it.', 'buddyboss' ),
							$feature_id,
							$fid
						)
					);
				}
			}
		}

		// Update active components.
		$active_components = bp_get_option( 'bp-active-components', array() );
		unset( $active_components[ $feature_id ] );
		bp_update_option( 'bp-active-components', $active_components );

		// Clear caches.
		$this->clear_feature_caches( $feature_id );

		/**
		 * Fired after a feature is deactivated.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $feature_id Feature ID.
		 */
		do_action( 'bb_feature_deactivated', $feature_id );

		return true;
	}

	// =========================================================================
	// UTILITY METHODS
	// =========================================================================

	/**
	 * Check for circular dependencies using DFS.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID to check.
	 * @return bool True if circular dependency exists, false otherwise.
	 */
	private function has_circular_dependency( $feature_id ) {
		$visited = array();
		$recursion_stack = array();

		return $this->dfs_detect_cycle( $feature_id, $visited, $recursion_stack );
	}

	/**
	 * DFS helper for circular dependency detection.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $node            Current node.
	 * @param array  $visited         Visited nodes.
	 * @param array  $recursion_stack Current recursion stack.
	 * @return bool True if cycle detected, false otherwise.
	 */
	private function dfs_detect_cycle( $node, &$visited, &$recursion_stack ) {
		$visited[ $node ] = true;
		$recursion_stack[ $node ] = true;

		if ( isset( $this->dependency_graph[ $node ] ) ) {
			foreach ( $this->dependency_graph[ $node ] as $neighbor ) {
				if ( ! isset( $visited[ $neighbor ] ) ) {
					if ( $this->dfs_detect_cycle( $neighbor, $visited, $recursion_stack ) ) {
						return true;
					}
				} elseif ( isset( $recursion_stack[ $neighbor ] ) && $recursion_stack[ $neighbor ] ) {
					return true;
				}
			}
		}

		$recursion_stack[ $node ] = false;
		return false;
	}

	/**
	 * Get default sanitize callback for a field type.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $type Field type.
	 * @return string|callable Sanitize callback.
	 */
	private function get_default_sanitize_callback( $type ) {
		$callbacks = array(
			'toggle'        => 'intval',
			'checkbox'      => 'intval',
			'checkbox_list' => array( $this, 'sanitize_checkbox_list' ),
			'text'          => 'sanitize_text_field',
			'email'         => 'sanitize_email',
			'url'           => 'esc_url_raw',
			'textarea'      => 'sanitize_textarea_field',
			'number'        => 'intval',
			'select'        => 'sanitize_text_field',
			'radio'         => 'sanitize_text_field',
			'color'         => 'sanitize_hex_color',
			'date'          => 'sanitize_text_field',
			'time'          => 'sanitize_text_field',
			'media'         => 'absint',
			'rich_text'     => 'wp_kses_post',
			'code'          => 'sanitize_textarea_field',
		);

		return isset( $callbacks[ $type ] ) ? $callbacks[ $type ] : 'sanitize_text_field';
	}

	/**
	 * Sanitize checkbox list field.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param mixed $value The value to sanitize.
	 * @return array Sanitized array of values.
	 */
	public function sanitize_checkbox_list( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Clear feature caches.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $feature_id Feature ID. Optional. If not provided, clears all caches.
	 */
	public function clear_feature_caches( $feature_id = null ) {
		if ( is_null( $feature_id ) ) {
			// Clear all feature caches.
			delete_transient( 'bb_features_list' );
			foreach ( array_keys( $this->features ) as $fid ) {
				delete_transient( "bb_feature_{$fid}" );
			}
		} else {
			// Clear specific feature cache.
			delete_transient( "bb_feature_{$feature_id}" );
		}

		// Clear search index cache.
		delete_transient( 'bb_settings_search_index' );
	}
}

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Get the Feature Registry instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Feature_Registry
 */
function bb_feature_registry() {
	return BB_Feature_Registry::instance();
}

/**
 * Register a feature.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $args       Feature arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_feature( $feature_id, $args = array() ) {
	return bb_feature_registry()->register_feature( $feature_id, $args );
}

/**
 * Register a side panel for a feature.
 *
 * Side panels appear in the left sidebar navigation when viewing feature settings.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id    Feature ID.
 * @param string $side_panel_id Side panel ID.
 * @param array  $args          Side panel arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_side_panel( $feature_id, $side_panel_id, $args = array() ) {
	return bb_feature_registry()->register_side_panel( $feature_id, $side_panel_id, $args );
}

/**
 * Register a feature section.
 *
 * Sections are the white boxes/cards that contain fields.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id    Feature ID.
 * @param string $side_panel_id Side panel ID.
 * @param string $section_id    Section ID.
 * @param array  $args          Section arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_feature_section( $feature_id, $side_panel_id, $section_id, $args = array() ) {
	return bb_feature_registry()->register_section( $feature_id, $side_panel_id, $section_id, $args );
}

/**
 * Register a feature field.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id    Feature ID.
 * @param string $side_panel_id Side panel ID.
 * @param string $section_id    Section ID.
 * @param array  $args          Field arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_feature_field( $feature_id, $side_panel_id, $section_id, $args = array() ) {
	return bb_feature_registry()->register_field( $feature_id, $side_panel_id, $section_id, $args );
}

/**
 * Register a feature navigation item.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $feature_id Feature ID.
 * @param array  $args       Navigation item arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_feature_nav_item( $feature_id, $args = array() ) {
	return bb_feature_registry()->register_nav_item( $feature_id, $args );
}
