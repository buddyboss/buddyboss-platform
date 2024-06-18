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

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_media_album_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

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
	 * Remove hidden/blocked user's albums
	 *
	 * @since BuddyBoss 1.5.6
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

		if ( isset( $where['moderation_where'] ) && ! empty( $where['moderation_where'] ) ) {
			$where['moderation_where'] .= ' AND ';
		}
		$where['moderation_where'] .= '( m.user_id NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) )';

		return $where;
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
