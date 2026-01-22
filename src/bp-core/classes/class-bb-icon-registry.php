<?php
/**
 * BuddyBoss Icon Registry
 *
 * Registry for managing custom icons beyond Dashicons.
 * Supports SVG, Images, Font Icons, and React Components.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Icon Registry Class
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Icon_Registry {

	/**
	 * Singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var BB_Icon_Registry
	 */
	private static $instance = null;

	/**
	 * Registered icons.
	 *
	 * @since BuddyBoss 3.0.0
	 * @var array
	 */
	private $icons = array();

	/**
	 * Get singleton instance.
	 *
	 * @since BuddyBoss 3.0.0
	 * @return BB_Icon_Registry
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
		// Fire hook for icons to register.
		add_action( 'bp_loaded', array( $this, 'init' ), 5 );
	}

	/**
	 * Initialize the registry.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function init() {
		/**
		 * Fired to register custom icons.
		 *
		 * @since BuddyBoss 3.0.0
		 */
		do_action( 'bb_register_icons' );
	}

	/**
	 * Register an icon.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Unique icon identifier (alphanumeric, underscore, hyphen).
	 * @param array  $args {
	 *     Icon arguments.
	 *
	 *     @type string $type        Icon type: 'dashicon', 'svg', 'image', 'font', 'react' (required).
	 *     @type string $label       Icon label (for admin display).
	 *     @type string $slug        For 'dashicon' type: Dashicon slug (e.g., 'dashicons-groups').
	 *     @type string $url         For 'svg' or 'image' type: Full URL to icon file.
	 *     @type string $path        For 'svg' or 'image' type: Path relative to plugin directory.
	 *     @type string $data_uri    For 'svg' type: SVG data URI (base64 encoded).
	 *     @type string $class       For 'font' type: CSS class name (e.g., 'bb-icons-rl-groups').
	 *     @type string $component   For 'react' type: React component name (must be registered in React app).
	 *     @type string $description Icon description.
	 * }
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function register_icon( $icon_id, $args = array() ) {
		// Validate icon ID.
		if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $icon_id ) ) {
			return new WP_Error(
				'invalid_icon_id',
				sprintf(
					/* translators: %s: icon ID */
					__( 'Invalid icon ID "%s". Icon IDs must contain only alphanumeric characters, underscores, and hyphens.', 'buddyboss' ),
					$icon_id
				)
			);
		}

		// Check for conflicts.
		if ( isset( $this->icons[ $icon_id ] ) ) {
			return new WP_Error(
				'icon_already_registered',
				sprintf(
					/* translators: %s: icon ID */
					__( 'Icon "%s" is already registered.', 'buddyboss' ),
					$icon_id
				)
			);
		}

		// Validate required args.
		if ( empty( $args['type'] ) ) {
			return new WP_Error(
				'missing_icon_type',
				sprintf(
					/* translators: %s: icon ID */
					__( 'Icon "%s" must have a type.', 'buddyboss' ),
					$icon_id
				)
			);
		}

		$type = $args['type'];
		$valid_types = array( 'dashicon', 'svg', 'image', 'font', 'react' );

		if ( ! in_array( $type, $valid_types, true ) ) {
			return new WP_Error(
				'invalid_icon_type',
				sprintf(
					/* translators: 1: icon ID, 2: valid types */
					__( 'Invalid icon type for "%1$s". Must be one of: %2$s', 'buddyboss' ),
					$icon_id,
					implode( ', ', $valid_types )
				)
			);
		}

		// Validate type-specific requirements.
		$validation_error = $this->validate_icon_args( $icon_id, $type, $args );
		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		// Set defaults.
		$defaults = array(
			'type'        => '',
			'label'       => '',
			'slug'        => '',
			'url'         => '',
			'path'        => '',
			'data_uri'    => '',
			'class'       => '',
			'component'   => '',
			'description' => '',
		);

		$args = wp_parse_args( $args, $defaults );

		// Process path to URL if needed.
		if ( ! empty( $args['path'] ) && empty( $args['url'] ) ) {
			// Try to resolve as plugin-relative path.
			if ( file_exists( buddypress()->plugin_dir . $args['path'] ) ) {
				$args['url'] = buddypress()->plugin_url . $args['path'];
			} elseif ( file_exists( plugin_dir_path( __FILE__ ) . '../' . $args['path'] ) ) {
				$args['url'] = plugin_dir_url( __FILE__ ) . '../' . $args['path'];
			}
		}

		// Register icon.
		$this->icons[ $icon_id ] = $args;

		/**
		 * Fired after an icon is registered.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param string $icon_id Icon ID.
		 * @param array  $args    Icon arguments.
		 */
		do_action( 'bb_icon_registered', $icon_id, $args );

		return true;
	}

	/**
	 * Validate icon arguments based on type.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Icon ID.
	 * @param string $type    Icon type.
	 * @param array  $args    Icon arguments.
	 * @return bool|WP_Error True if valid, WP_Error on failure.
	 */
	private function validate_icon_args( $icon_id, $type, $args ) {
		switch ( $type ) {
			case 'dashicon':
				if ( empty( $args['slug'] ) ) {
					return new WP_Error(
						'missing_dashicon_slug',
						sprintf(
							/* translators: %s: icon ID */
							__( 'Dashicon "%s" must have a slug.', 'buddyboss' ),
							$icon_id
						)
					);
				}
				break;

			case 'svg':
				if ( empty( $args['url'] ) && empty( $args['path'] ) && empty( $args['data_uri'] ) ) {
					return new WP_Error(
						'missing_svg_source',
						sprintf(
							/* translators: %s: icon ID */
							__( 'SVG icon "%s" must have a url, path, or data_uri.', 'buddyboss' ),
							$icon_id
						)
					);
				}
				break;

			case 'image':
				if ( empty( $args['url'] ) && empty( $args['path'] ) ) {
					return new WP_Error(
						'missing_image_source',
						sprintf(
							/* translators: %s: icon ID */
							__( 'Image icon "%s" must have a url or path.', 'buddyboss' ),
							$icon_id
						)
					);
				}
				break;

			case 'font':
				if ( empty( $args['class'] ) ) {
					return new WP_Error(
						'missing_font_class',
						sprintf(
							/* translators: %s: icon ID */
							__( 'Font icon "%s" must have a CSS class.', 'buddyboss' ),
							$icon_id
						)
					);
				}
				break;

			case 'react':
				if ( empty( $args['component'] ) ) {
					return new WP_Error(
						'missing_react_component',
						sprintf(
							/* translators: %s: icon ID */
							__( 'React icon "%s" must have a component name.', 'buddyboss' ),
							$icon_id
						)
					);
				}
				break;
		}

		return true;
	}

	/**
	 * Get an icon by ID.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Icon ID.
	 * @return array|null Icon data or null if not found.
	 */
	public function get_icon( $icon_id ) {
		return isset( $this->icons[ $icon_id ] ) ? $this->icons[ $icon_id ] : null;
	}

	/**
	 * Get all registered icons.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $type Optional. Filter by icon type.
	 * @return array Array of icons.
	 */
	public function get_icons( $type = null ) {
		$icons = $this->icons;

		if ( ! is_null( $type ) ) {
			$icons = array_filter( $icons, function( $icon ) use ( $type ) {
				return isset( $icon['type'] ) && $icon['type'] === $type;
			} );
		}

		return $icons;
	}

	/**
	 * Check if an icon is registered.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Icon ID.
	 * @return bool True if registered, false otherwise.
	 */
	public function is_registered( $icon_id ) {
		// Only strings can be valid icon IDs.
		if ( ! is_string( $icon_id ) ) {
			return false;
		}
		return isset( $this->icons[ $icon_id ] );
	}

	/**
	 * Get icon data for REST API response.
	 *
	 * Formats icon data for consumption by React frontend.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string|array $icon_id Icon ID, Dashicon slug, or pre-formatted icon array.
	 * @return array|null Icon data formatted for REST API or null if not found.
	 */
	public function get_icon_for_rest( $icon_id ) {
		// If icon is already an array with type, return it directly.
		if ( is_array( $icon_id ) && isset( $icon_id['type'] ) ) {
			return $icon_id;
		}

		// Ensure icon_id is a string for further checks.
		if ( ! is_string( $icon_id ) ) {
			return null;
		}

		// Check if it's a registered icon.
		if ( $this->is_registered( $icon_id ) ) {
			$icon = $this->get_icon( $icon_id );
			return array(
				'type' => $icon['type'],
				'id'   => $icon_id,
				'data' => $icon,
			);
		}

		// Check if it's a Dashicon (WordPress core).
		if ( $this->is_dashicon( $icon_id ) ) {
			return array(
				'type' => 'dashicon',
				'slug' => $icon_id,
			);
		}

		// Check if it's a URL (SVG or image).
		if ( $this->is_url( $icon_id ) ) {
			$extension = strtolower( pathinfo( parse_url( $icon_id, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
			if ( 'svg' === $extension ) {
				return array(
					'type' => 'svg',
					'url'  => $icon_id,
				);
			} else {
				return array(
					'type' => 'image',
					'url'  => $icon_id,
				);
			}
		}

		return null;
	}

	/**
	 * Check if identifier is a Dashicon slug.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Icon identifier.
	 * @return bool True if appears to be a Dashicon slug.
	 */
	private function is_dashicon( $icon_id ) {
		// Dashicons typically start with 'dashicons-'.
		return strpos( $icon_id, 'dashicons-' ) === 0;
	}

	/**
	 * Check if identifier is a URL.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param string $icon_id Icon identifier.
	 * @return bool True if appears to be a URL.
	 */
	private function is_url( $icon_id ) {
		return filter_var( $icon_id, FILTER_VALIDATE_URL ) !== false;
	}
}

/**
 * Get the Icon Registry instance.
 *
 * @since BuddyBoss 3.0.0
 *
 * @return BB_Icon_Registry
 */
function bb_icon_registry() {
	return BB_Icon_Registry::instance();
}

/**
 * Register an icon.
 *
 * @since BuddyBoss 3.0.0
 *
 * @param string $icon_id Icon ID.
 * @param array  $args    Icon arguments.
 * @return bool|WP_Error True on success, WP_Error on failure.
 */
function bb_register_icon( $icon_id, $args = array() ) {
	return bb_icon_registry()->register_icon( $icon_id, $args );
}
