<?php
/**
 * BuddyBoss Moderation Message Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Message.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Message extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'message_thread';

	/**
	 * BP_Moderation_Message constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin or Reporting setting disabled
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		parent::__construct();
	}

	/**
	 * Get permalink.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $folder_id folder id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $folder_id ) {
		return '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $folder_id Folder id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $folder_id ) {
		return 0;
	}
}
