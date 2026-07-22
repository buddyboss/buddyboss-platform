<?php
/**
 * BuddyBoss Bookmark Type.
 *
 * Abstract base for anything that can be bookmarked. A consumer extends this,
 * implements setup(), and calls register_type() to declare its type slug and
 * how to hydrate its items.
 *
 * Type slug convention:
 *  - A WordPress post type uses the post type slug, exactly as the BuddyBoss
 *    App plugin names it (`post`, `page`). Matching the App's slug is what lets
 *    rows mirror between the two stores with no translation layer.
 *  - A BuddyPress component uses the component name directly (`activity`,
 *    `groups`). These have no App counterpart.
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Bookmark type base class.
 *
 * @since BuddyBoss [BBVERSION]
 */
abstract class BB_Bookmark_Type {

	/**
	 * Instances, keyed by concrete class name.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Types registered by this instance.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var array
	 */
	private $bookmark_types = array();

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_filter( 'bb_bookmark_register_types', array( $this, 'get_registered_types' ), 99, 1 );
	}

	/**
	 * Get the instance of the concrete subclass.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return static
	 */
	public static function instance() {
		$class = get_called_class();

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
			self::$instances[ $class ]->setup();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Register a bookmark type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $type Type slug. See the class docblock for the convention.
	 * @param array  $args {
	 *     An array of arguments.
	 *     @type array    $label          Singular and plural labels.
	 *     @type callable $items_callback Receives an array of bookmark objects and
	 *                                    returns them hydrated with item data.
	 * }
	 *
	 * @return void
	 */
	public function register_type( $type = '', $args = array() ) {
		if ( empty( $type ) ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A bookmark type slug is required.', 'buddyboss' ), '[BBVERSION]' );

			return;
		}

		// The bb_bookmark.type column is a varchar(20). WordPress strips
		// STRICT_TRANS_TABLES from the session sql_mode, so a longer slug would
		// not error -- it would silently truncate on write and then never match
		// on read, leaving an invisible, unremovable bookmark.
		if ( strlen( $type ) > 20 ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A bookmark type slug must be 20 characters or fewer.', 'buddyboss' ), '[BBVERSION]' );

			return;
		}

		$r = bp_parse_args(
			$args,
			array(
				'label'          => array(
					'singular' => '',
					'plural'   => '',
				),
				'items_callback' => '',
			)
		);

		if ( empty( $r['items_callback'] ) || ! is_callable( $r['items_callback'] ) ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'A callable items_callback is required to register a bookmark type.', 'buddyboss' ), '[BBVERSION]' );

			return;
		}

		$this->bookmark_types[ $type ] = array(
			'label'          => array(
				'singular' => ! empty( $r['label']['singular'] ) ? $r['label']['singular'] : $type,
				'plural'   => ! empty( $r['label']['plural'] ) ? $r['label']['plural'] : $type,
			),
			'items_callback' => $r['items_callback'],
		);
	}

	/**
	 * Merge this instance's types into the global registry.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $types Types collected so far.
	 *
	 * @return array
	 */
	public function get_registered_types( $types = array() ) {
		if ( ! is_array( $types ) ) {
			$types = array();
		}

		foreach ( $this->bookmark_types as $type => $data ) {
			$types[ $type ] = $data;
		}

		return $types;
	}

	/**
	 * Register this consumer's type(s). Called once, on first instance().
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	abstract public function setup();
}
