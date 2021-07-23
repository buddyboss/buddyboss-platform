<?php
/**
 * BuddyBoss Moderation Video Classes
 *
 * @since   BuddyBoss 1.7.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Video.
 *
 * @since BuddyBoss 1.7.0
 */
class BP_Moderation_Video extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'video';

	/**
	 * BP_Moderation_Video constructor.
	 *
	 * @since BuddyBoss 1.7.0
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
		add_filter( 'bp_suspend_video_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param int $video_id Video id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $video_id ) {
		$video = new BP_Video( $video_id );
		return bb_video_get_symlink( $video );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param integer $video_id Video id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $video_id ) {
		$video = new BP_Video( $video_id );

		return ( ! empty( $video->user_id ) ) ? $video->user_id : 0;
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Videos', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Remove hidden/blocked user's videos
	 *
	 * @since BuddyBoss 1.7.0
	 *
	 * @param string $where   videos Where sql.
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
	 * Filter to check the video is valid or not.
	 *
	 * @since BuddyBoss 1.7.0
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

		$video = new BP_Video( (int) $item_id );

		if ( empty( $video ) || empty( $video->id ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Function to change report button text.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string
	 */
	public function report_button_text( $button_text, $item_id ) {
		return esc_html__( 'Report Video', 'buddyboss' );
	}

	/**
	 * Function to change report type.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string
	 */
	public function report_content_type( $content_type, $item_id ) {
		return esc_html__( 'Video', 'buddyboss' );
	}
}
