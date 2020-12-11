<?php
/**
 * BuddyBoss Moderation Media Album Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Media Album.
 *
 * @since BuddyBoss 2.0.0
 */
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Check Component is disabled.
		if ( ! bp_is_active( 'media' ) ) {
			return;
		}

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content.
		add_filter( 'bp_suspend_media_album_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $album_id Album id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $album_id ) {
		return 0;
	}

	/**
	 * Remove hidden/blocked user's albums
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   albums Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		return $where;
	}
}
