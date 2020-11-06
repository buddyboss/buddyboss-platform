<?php
/**
 * BuddyBoss Moderation Groups Classes
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Members.
 *
 * @since BuddyBoss 1.5.4
 */
class BP_Moderation_Members extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'user';

	/**
	 * BP_Moderation_Group constructor.
	 *
	 * @since BuddyBoss 1.5.4
	 */
	public function __construct() {

		parent::$Moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter('bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend
		 */
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		add_filter( 'bp_user_query_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_user_query_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_user_search_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_user_search_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bp_init', array( $this, 'restrict_member_profile' ), 4 );
		add_filter( 'authenticate', array( $this, 'boot_suspended_user' ), 30 );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $content_types Supported Contents types
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ){
		$content_types[ self::$moderation_type ] = __( 'User', 'buddyboss' );
		return $content_types;
	}

	/**
	 * Prepare Members Join SQL query to filter blocked Members
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $join_sql Members sql.
	 * @param string $uid_name User ID field name.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $uid_name ) {
		$join_sql .= $this->exclude_joint_query( 'u.' . $uid_name );

		return $join_sql;
	}

	/**
	 * Prepare Members Where SQL query to filter blocked Members
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array  $where_conditions Members where sql.
	 * @param string $uid_name         User ID field name.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $uid_name ) {
		$where                = array();
		$where['users_where'] = $this->exclude_where_query( $uid_name );

		/**
		 * Filters the Members Moderation Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.4
		 *
		 * @param array $where array of Members moderation where query.
		 */
		$where = apply_filters( 'bp_moderation_groups_get_where_conditions', $where );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['moderation_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Prepare Where sql for exclude Blocked items
	 *
	 * @param string $uid_name User ID field name.
	 *
	 * @return string|void
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function exclude_where_query( $uid_name = '' ) {
		$sql                = false;
		$hidden_members_ids = self::get_sitewide_hidden_ids();
		if ( ! empty( $hidden_members_ids ) ) {
			$sql = "( u.$uid_name NOT IN ( " . implode( ',', $hidden_members_ids ) . ' ) )';
		}

		return $sql;
	}

	/**
	 * Show 404 if Member is suspended
	 */
	public function restrict_member_profile() {
		$user_id            = bp_displayed_user_id();
		$hidden_members_ids = self::get_sitewide_hidden_ids();
		if ( in_array( $user_id, $hidden_members_ids, true ) ) {
			buddypress()->displayed_user->id = 0;
			bp_do_404();

			return;
		}
	}

	/**
	 * Prevent Suspended from logging in.
	 *
	 * When a user logs in, check if they have been marked as a spammer. If yes
	 * then simply redirect them to the home page and stop them from logging in.
	 *
	 * @param WP_User|WP_Error $user Either the WP_User object or the WP_Error
	 *                               object, as passed to the 'authenticate' filter.
	 *
	 * @return WP_User|WP_Error If the user is not a spammer, return the WP_User
	 *                          object. Otherwise a new WP_Error object.
	 */
	public function boot_suspended_user( $user ) {
		// Check to see if the $user has already failed logging in, if so return $user as-is.
		if ( is_wp_error( $user ) || empty( $user ) ) {
			return $user;
		}

		$hidden_members_ids = self::get_sitewide_hidden_ids( false );
		// The user exists; now do a check to see if the user is a suspended
		if ( is_a( $user, 'WP_User' ) && in_array( $user->id, $hidden_members_ids, true ) ) {
			return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been Suspended.', 'buddyboss' ) );
		}

		// User is good to go!
		return $user;
	}

	/**
	 * Get blocked Member ids
	 *
	 * @param bool $user_include Include item which report by current user even if it's not hidden.
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids( $user_include = true ) {
		return self::get_sitewide_hidden_item_ids( self::$moderation_type, $user_include );
	}

	/**
	 * Get Content owner id.
	 *
	 * @param integer $user_id User id
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $user_id ) {
		return $user_id;
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param array $args Content data
	 *
	 * @return string
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

}
