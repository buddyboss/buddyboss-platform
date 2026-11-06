<?php
/**
 * Stub for the legacy forum-import converter classes.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss [BBVERSION]
 * @deprecated BuddyBoss 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Shared stub the legacy converter class names are aliased to when BuddyBoss Tools is inactive.
 *
 * Every legacy name (see bb_deprecated_forum_import_class_map()) is a class_alias() of this class,
 * so `new Legacy()` and `class X extends Legacy` keep working; every method call returns null.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Deprecated_Forum_Import_Stub {

	/**
	 * Emits the deprecation notice.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		_deprecated_function( esc_html( get_class( $this ) ), 'BuddyBoss 3.1.0', 'BB_Tools_BBP_Converter (install buddyboss-tools - third-party importers calling parent::* on these classes require Tools active)' );
	}

	/**
	 * Every instance method returns null.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return null
	 */
	public function __call( $name, $args ) {
		return null;
	}

	/**
	 * Every static method returns null.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return null
	 */
	public static function __callStatic( $name, $args ) {
		return null;
	}
}
