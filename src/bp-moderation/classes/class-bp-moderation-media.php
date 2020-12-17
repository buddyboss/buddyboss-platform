<?php
/**
 * BuddyBoss Moderation Media Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Media.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Media extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'media';

	/**
	 * BP_Moderation_Media constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

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
		add_filter( 'bp_suspend_media_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );

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
	 * @param integer $media_id Media id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $media_id ) {
		$media = new BP_Media( $media_id );

		return ( ! empty( $media->user_id ) ) ? $media->user_id : 0;
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Photo', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Remove hidden/blocked user's medias
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   medias Where sql.
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

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_sub_items( $item_id ) {
		$media = new BP_Media( $item_id );

		if ( empty( $media->id ) ) {
			return array();
		}

		$sub_items = array();
		if ( bp_is_active( 'activity' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity::$moderation_type ) && ! empty( $media->activity_id ) ) {
			$sub_items['id']   = $media->activity_id;
			$sub_items['type'] = BP_Moderation_Activity::$moderation_type;
		}

		return $sub_items;
	}
}
