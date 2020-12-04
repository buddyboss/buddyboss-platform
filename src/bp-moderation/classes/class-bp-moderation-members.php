<?php
/**
 * BuddyBoss Moderation Members Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Members.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Members extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'user';

	/**
	 * BP_Moderation_Members constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Delete user moderation data when actual user is deleted.
		add_action( 'deleted_user', array( $this, 'sync_moderation_data_on_delete' ), 10, 3 );

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( is_admin() && ! wp_doing_ajax() && self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content
		add_filter( 'bp_suspend_member_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// button class.
		add_filter( 'bp_moderation_get_report_button_args', array( $this, 'update_button_args' ), 10, 3 );

		add_filter( 'bp_init', array( $this, 'restrict_member_profile' ), 4 );
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $user_id   User id.
	 * @param bool    $view_link add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $user_id, $view_link = false ) {
		return bp_core_get_user_displayname( $user_id );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $member_id member id.
	 *
	 * @return string
	 */
	public static function get_permalink( $member_id ) {
		return '';
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
	 * @param integer $member_id Group id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $member_id ) {
		return ( ! empty( $member_id ) ) ? $member_id : 0;
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
		$content_types[ self::$moderation_type ] = __( 'User', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Delete moderation data when actual user is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $user_id  user id of the user that is being deleted.
	 * @param int    $reassign user id of the user that all content is going to assign.
	 * @param object $user     user data.
	 */
	public function sync_moderation_data_on_delete( $user_id, $reassign, $user ) {

		$moderation_obj = new BP_Moderation( $user_id, self::$moderation_type );
		if ( ! empty( $moderation_obj->id ) ) {
			$moderation_obj->delete( true );
		}
	}

	/**
	 * Update where query remove blocked users
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   blocked users Where sql
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
	 * Function to modify the button class
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array  $button      Button args.
	 * @param string $item_type   Content type.
	 * @param string $is_reported Item reported.
	 *
	 * @return string
	 */
	public function update_button_args( $button, $item_type, $is_reported ) {
		if ( self::$moderation_type === $item_type ) {
			if ( $is_reported ) {
				$button['button_attr']['class'] = 'blocked-member';
			} else {
				$button['button_attr']['class'] = 'block-member';
			}
		}

		return $button;
	}

	/**
	 * If the displayed user is marked as a blocked, Show 404.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function restrict_member_profile() {
		$user_id = bp_displayed_user_id();
		if ( bp_moderation_is_user_blocked( $user_id ) ) {
			buddypress()->displayed_user->id = 0;
			bp_do_404();

			return;
		}
	}
}
