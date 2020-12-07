<?php
/**
 * BuddyBoss Moderation Forums Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forums.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Forums extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum';

	/**
	 * BP_Moderation_Forums constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Check Component is disabled
		if ( ! bp_is_active( 'document' ) ){
			return;
		}

		// delete forum moderation data when actual forum deleted.
		add_action( 'after_delete_post', array( $this, 'sync_moderation_data_on_delete' ), 10, 2 );

		/**
		 * Moderation code should not add for WordPress backend oror Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content
		add_filter( 'bp_suspend_forum_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// button.
		add_filter( "bp_moderation_{$this->item_type}_button_args", array( $this, 'update_button_args' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $forum_id forum id.
	 *
	 * @return string
	 */
	public static function get_permalink( $forum_id ) {
		$url = get_the_permalink( $forum_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return string
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
	 * @param integer $forum_id Forum id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $forum_id ) {
		return get_post_field( 'post_author', $forum_id );
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
		$content_types[ self::$moderation_type ] = __( 'Forum', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to delete forum moderation data when actual forum is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $forum_id Forum id being deleted.
	 * @param object $forum    Forum post data.
	 */
	public function sync_moderation_data_on_delete( $forum_id, $forum ) {
		if ( ! empty( $forum_id ) && ! empty( $forum ) && bbp_get_forum_post_type() === $forum->post_type ) {
			$moderation_obj = new BP_Moderation( $forum_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}

	/**
	 * Update where query remove hidden/blocked user's forums
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where forums Where sql
	 * @param object $suspend suspend object
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias               = $suspend->alias;
		$where['moderation_where'] = $this->exclude_where_query();

		return $where;
	}

	/**
	 * Function to modify the button args
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args    Button args.
	 * @param int   $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_args( $args, $item_id ) {

		// Remove report button if forum is group forums
		if ( function_exists( 'bbp_is_forum_group_forum' )
		     && bbp_is_forum_group_forum( $item_id ) ) {
			$args['button_attr']['data-bp-content-sub-id']   = current( bbp_get_forum_group_ids( $item_id ) );
			$args['button_attr']['data-bp-content-sub-type'] = BP_Moderation_Groups::$moderation_type;
		}

		return $args;
	}
}
