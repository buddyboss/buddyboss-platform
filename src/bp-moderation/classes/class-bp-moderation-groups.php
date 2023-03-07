<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Groups.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Groups extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'groups';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.6
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
		add_filter( 'bp_suspend_group_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bp_groups_group_pre_validate', array( $this, 'restrict_single_item' ), 10, 3 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Restrict private group report button.
		add_filter( "bp_moderation_{$this->item_type}_button_args", array( $this, 'update_button_args' ), 10, 2 );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );

		// Update the where condition for group subscriptions.
		add_filter( 'bb_subscriptions_suspend_group_get_where_conditions', array( $this, 'bb_subscriptions_moderation_group_where_conditions' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $group_id group id.
	 *
	 * @return string
	 */
	public static function get_permalink( $group_id ) {
		$group = new BP_Groups_Group( $group_id );

		$url = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $group->slug . '/' );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Get Content owner ids.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $group_id Group id.
	 *
	 * @return array
	 */
	public static function get_content_owner_id( $group_id ) {
		$g_admins = groups_get_group_admins( $group_id );

		return ( ! empty( $g_admins ) ) ? wp_list_pluck( $g_admins, 'user_id' ) : 0;
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Groups', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query remove hidden/blocked user's groups
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where groups Where sql.
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
	 * Validate the group is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param boolean $restrict Check the item is valid or not.
	 * @param object  $group    Current group object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $group ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $restrict;
		}

		if ( bp_moderation_is_content_hidden( (int) $group->id, self::$moderation_type ) ) {
			return false;
		}

		return $restrict;
	}

	/**
	 * Function to restrict private group report button for it's member only.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args    report button arguments.
	 * @param int   $item_id group id.
	 *
	 * @return array|mixed
	 */
	public function update_button_args( $args, $item_id ) {

		$group_data    = groups_get_group( $item_id );
		$is_group_user = groups_is_user_member( bp_loggedin_user_id(), $item_id );

		if ( 'private' === $group_data->status && false === $is_group_user ) {
			$args = false;
		}

		return $args;
	}

	/**
	 * Filter to check the group is valid or not.
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

		$group = new BP_Groups_Group( (int) $item_id );

		if ( empty( $group ) || empty( $group->id ) ) {
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
		return esc_html__( 'Report Group', 'buddyboss' );
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
		return esc_html__( 'Group', 'buddyboss' );
	}

	/**
	 * Update where query remove hidden/blocked group subscriptions.
	 *
	 * @since BuddyBoss 2.2.8
	 *
	 * @param array  $where   Subscription groups where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function bb_subscriptions_moderation_group_where_conditions( $where, $suspend ) {
		$moderation_where = 'hide_parent = 1 OR hide_sitewide = 1';

		$blocked_query = $this->blocked_user_query();
		if ( ! empty( $blocked_query ) ) {
			if ( ! empty( $moderation_where ) ) {
				$moderation_where .= ' OR ';
			}
			$moderation_where .= "( id IN ( $blocked_query ) )";
		}

		$where['moderation_where'] = $moderation_where;

		return $where;
	}
}
