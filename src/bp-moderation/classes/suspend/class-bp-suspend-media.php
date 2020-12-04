<?php
/**
 * BuddyBoss Suspend Activity Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Suspend
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Media.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Suspend_Media extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'media';

	/**
	 * BP_Moderation_Media constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		$this->item_type = self::$type;

		//Manage hidden list
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_media' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_media' ), 10, 4 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_media_get_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_media_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_media_search_join_sql_photo', array( $this, 'update_join_sql' ), 10 );
		add_filter( 'bp_media_search_where_conditions_photo', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Blocked member's media ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $member_id member id
	 *
	 * @return array
	 */
	public static function get_member_media_ids( $member_id ) {
		$media_ids = array();

		//add query

		return $media_ids;
	}

	/**
	 * Prepare media Join SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $join_sql Media Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'm.id' );

		/**
		 * Filters the hidden Media Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_media_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare media Where SQL query to filter blocked Media
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $where_conditions Media Where sql.
	 * @param array $args             Query arguments.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = array() ) {
		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $where_conditions;
		}

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden media Where SQL statement.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $where Query to hide suspended user's media.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_media_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of media
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $media_id media id
	 * @param int|null $hide_sitewide item hidden sitewide or user specific
	 * @param array    $args          parent args
	 */
	public function manage_hidden_media( $media_id, $hide_sitewide, $args = array() ) {
		$suspend_args = wp_parse_args( $args, array(
			'item_id'   => $media_id,
			'item_type' => BP_Suspend_Media::$type,
		) );

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::add_suspend( $suspend_args );
		$this->hide_related_content( $media_id, $hide_sitewide, $args );
	}

	/**
	 * Un-hide related content of media
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $media_id media id
	 * @param int|null $hide_sitewide item hidden sitewide or user specific
	 * @param int      $force_all     un-hide for all users
	 * @param array    $args          parent args
	 */
	public function manage_unhidden_media( $media_id, $hide_sitewide, $force_all, $args = array() ) {
		$suspend_args = wp_parse_args( $args, array(
			'item_id'   => $media_id,
			'item_type' => BP_Suspend_Media::$type,
		) );

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		BP_Core_Suspend::remove_suspend( $suspend_args );
		$this->unhide_related_content( $media_id, $hide_sitewide, $force_all, $args );
	}

	/**
	 * Get Media's comment ids
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $media_id Media id
	 *
	 * @return array
	 */
	protected function get_related_contents( $media_id ) {

		$related_contents = array();
		$media            = new BP_Media( $media_id );

		if ( bp_is_active( 'activity' ) ) {
			$related_contents[ BP_Suspend_Activity::$type ] = array( $media->activity_id );
		}

		return $related_contents;
	}
}
