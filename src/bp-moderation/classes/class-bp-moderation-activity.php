<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Activity extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Register moderation data
		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Check Component is active
		if ( ! bp_is_active( 'activity' ) ){
			return;
		}

		// Delete activity moderation data when actual activity get deleted.
		add_action( 'bp_activity_deleted_activities', array( $this, 'sync_moderation_data_on_delete' ), 10 );

		/**
		 * Moderation code should not add for WordPress backend and if Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content
		add_filter( 'bp_suspend_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get Content excerpt.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int  $activity_id activity id.
	 * @param bool $view_link   add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $activity_id, $view_link = false ) {
		$activity = new BP_Activity_Activity( $activity_id );

		$activity_content = ( ! empty( $activity->content ) ) ? $activity->content : $activity->action;

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $activity_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$activity_content = ( ! empty( $activity_content ) ) ? $activity_content . ' ' . $link : $link;
		}

		return $activity_content;
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id.
	 *
	 * @return string
	 */
	public static function get_permalink( $activity_id ) {
		$url = bp_activity_get_permalink( $activity_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

	/**
	 * Hide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function hide( $args ) {
		return parent::hide( $args );
	}

	/**
	 * Unhide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function unhide( $args ) {
		return parent::unhide( $args );
	}

	/**
	 * Delete Moderated report
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function delete( $args ) {
		return parent::delete( $args );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $activity_id Activity id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
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
		$content_types[ self::$moderation_type ] = __( 'Activity', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to delete activity moderation data when actual activity is getting deleted.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $activity_deleted_ids activity ids array.
	 */
	public function sync_moderation_data_on_delete( $activity_deleted_ids ) {

		if ( ! empty( $activity_deleted_ids ) && is_array( $activity_deleted_ids ) ) {
			foreach ( $activity_deleted_ids as $activity_deleted_id ) {
				$moderation_obj = new BP_Moderation( $activity_deleted_id, self::$moderation_type );
				if ( ! empty( $moderation_obj->id ) ) {
					$moderation_obj->delete( true );
				}
			}
		}
	}

	/**
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where Activity Where sql
	 * @param object $suspend suspend object
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias               = $suspend->alias;
		$where['moderation_where'] = $this->exclude_where_query();

		return $where;
	}
}
