<?php
/**
 * BuddyBoss Suspend Member Classes
 *
 * @package BuddyBoss\Suspend
 * @since   BuddyBoss 1.5.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Member.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Member extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'user';

	/**
	 * BP_Suspend_Member constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_member' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_member' ), 10, 4 );

		// Delete user moderation data when actual user is deleted.
		add_action( 'deleted_user', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		// Migrate existing spammer as suspended user.
		add_action( 'bp_init', array( $this, 'migrate_spam_users' ), 99 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( ( is_admin() ) && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_recipients_recipient_get_where_conditions', array( $this, 'exclude_moderated_recipients' ), 10, 2 );

		add_filter( 'bp_user_query_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_user_query_where_sql', array( $this, 'update_where_sql' ), 10, 2 );
		
		add_filter( 'bp_user_search_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_user_search_where_sql', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'authenticate', array( $this, 'boot_suspended_user' ), 30 );
		add_filter( 'bp_init', array( $this, 'bp_stop_live_suspended' ), 5 );
		add_action( 'login_form_bp-suspended', array( $this, 'bp_live_suspended_login_error' ) );
		add_filter( 'bp_init', array( $this, 'restrict_member_profile' ), 4 );

		add_filter( 'bp_core_get_user_domain', array( $this, 'bp_core_get_user_domain' ), 9999, 2 );
		add_filter( 'get_the_author_user_nicename', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_login', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_email', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_display_name', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'bp_core_get_user_displayname', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_avatar_url', array( $this, 'get_avatar_url' ), 9999, 3 );
		add_filter( 'bp_core_fetch_avatar_url_check', array( $this, 'bp_fetch_avatar_url' ), 1005, 2 );
		add_filter( 'bp_core_fetch_gravatar_url_check', array( $this, 'bp_fetch_avatar_url' ), 1005, 2 );
	}

	/**
	 * Suspend User
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $user_id user id.
	 *
	 */
	public static function suspend_user( $user_id ) {
		BP_Core_Suspend::add_suspend(
			array(
				'item_id'        => $user_id,
				'item_type'      => self::$type,
				'user_suspended' => 1,
			)
		);

		/**
		 * Add related content of reported item into hidden list
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param int $item_id       item id
		 * @param int $hide_sitewide item hidden sitewide or user specific
		 */
		do_action(
			'bp_suspend_hide_' . self::$type,
			$user_id,
			1,
			array(
				'action'           => 'suspended',
				'force_bg_process' => true,
			)
		);
	}

	/**
	 * Un-suspend User
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $user_id user id.
	 *
	 */
	public static function unsuspend_user( $user_id ) {
		BP_Core_Suspend::add_suspend(
			array(
				'item_id'        => $user_id,
				'item_type'      => self::$type,
				'user_suspended' => 0,
			)
		);

		/**
		 * Remove related content of reported item from hidden list.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param int $item_id       item id
		 * @param int $hide_sitewide item hidden sitewide or user specific
		 * @param int $force_all     un-hide for all users
		 */
		do_action(
			'bp_suspend_unhide_' . self::$type,
			$user_id,
			0,
			0,
			array(
				'action'           => 'unsuspended',
				'force_bg_process' => true,
			)
		);
	}

	/**
	 * Prepare member Join SQL query to filter blocked Member
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Member Join sql.
	 * @param string $uid_name User ID field name.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $uid_name = '' ) {

		$join_sql .= $this->exclude_joint_query( 'u.' . $uid_name );

		/**
		 * Filters the hidden member Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_member_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare member Where SQL query to filter blocked Member
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $where_conditions Member Where sql.
	 * @param string $column_name      Column name.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $column_name ) {

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		/**
		 * Filters the hidden member Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $where Query to hide suspended user's member.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_member_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Exclude moderated members from message recipients lists.
	 *
	 * @since BuddyBoss 1.7.8
	 *
	 * @param array $where_conditions Recipients member where sql.
	 * @param array $args             Array of arguments of recipients query.
	 *
	 * @return mixed
	 */
	public function exclude_moderated_recipients( $where_conditions, $args ) {
		global $wpdb;
		$bp = buddypress();
		if (
			! isset( $args['exclude_moderated_members'] ) ||
			(
				false === (bool) $args['exclude_moderated_members']
			)
		) {
			return $where_conditions;
		}
		
		$where          = array();
		$hidden_members = bp_moderation_get_hidden_user_ids();
		if ( ! empty( $hidden_members ) ) {
			$where['blocked_where'] = "( r.user_id NOT IN('" . implode( "','", $hidden_members ) . "') )";
		}
		
		$sql                    = $wpdb->prepare( "SELECT DISTINCT {$this->alias}.item_id FROM {$bp->moderation->table_name} {$this->alias} WHERE {$this->alias}.item_type = %s
								  AND ( {$this->alias}.user_suspended = 1 )", 'user' ); // phpcs:ignore
		$where['suspend_where'] = "( r.user_id NOT IN( " . $sql . " ) )";
		/**
		 * Filters the hidden member Where SQL statement.
		 *
		 * @since BuddyBoss 1.7.8
		 *
		 * @param array $where Query to hide suspended user's member.
		 * @param array $class current class object.
		 */
		$where = apply_filters( 'bp_suspend_member_recipient_get_where_conditions', $where, $this );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of member
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $member_id     member id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_member( $member_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$force_bg_process = false;
		if ( isset( $args['force_bg_process'] ) ) {
			$force_bg_process = (bool) $args['force_bg_process'];
			unset( $args['force_bg_process'] );
		}

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'      => $member_id,
				'item_type'    => self::$type,
				'blocked_user' => $member_id,
			)
		);

		if ( ! empty( $args['action'] ) && in_array( $args['action'], array( 'suspended', 'unsuspended' ), true ) ) {
			$suspend_args['action_suspend'] = true;
		}

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide']  = $hide_sitewide;
			$suspend_args['user_suspended'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ( ! empty( $args ) && ! $force_bg_process ) ) {
			$this->hide_related_content( $member_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $member_id, $hide_sitewide, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of member
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $member_id     member id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_member( $member_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$force_bg_process = false;
		if ( isset( $args['force_bg_process'] ) ) {
			$force_bg_process = (bool) $args['force_bg_process'];
			unset( $args['force_bg_process'] );
		}

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'      => $member_id,
				'item_type'    => self::$type,
				'blocked_user' => $member_id,
			)
		);

		if ( ! empty( $args['action'] ) && in_array( $args['action'], array( 'suspended', 'unsuspended' ), true ) ) {
			$suspend_args['action_suspend'] = true;
		}

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide']  = $hide_sitewide;
			$suspend_args['user_suspended'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ( ! empty( $args ) && ! $force_bg_process ) ) {
			$this->unhide_related_content( $member_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $member_id, $hide_sitewide, $force_all, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Prevent Suspended from logging in.
	 *
	 * When a user logs in, check if they have been marked as a Suspended. If yes
	 * then simply redirect them to the home page and stop them from logging in.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param WP_User|WP_Error $user Either the WP_User object or the WP_Error
	 *                               object, as passed to the 'authenticate' filter.
	 *
	 * @return WP_User|WP_Error If the user is not a Suspended, return the WP_User
	 *                          object. Otherwise a new WP_Error object.
	 */
	public function boot_suspended_user( $user ) {
		// Check to see if the $user has already failed logging in, if so return $user as-is.
		if ( is_wp_error( $user ) || empty( $user ) ) {
			return $user;
		}

		// The user exists; now do a check to see if the user is a suspended.
		if ( is_a( $user, 'WP_User' ) && bp_moderation_is_user_suspended( $user->ID ) ) {
			return new WP_Error( 'invalid_username', __( '<strong>ERROR</strong>: Your account has been suspended. Please contact a site admin for more information.', 'buddyboss' ) );
		}

		// User is good to go!
		return $user;
	}

	/**
	 * Stop a logged-in user who is marked as a suspended.
	 *
	 * When an admin marks a live user account as a suspended, that user can still surf
	 * around and cause havoc on the site until that person is logged out.
	 *
	 * This code checks to see if a logged-in user account is marked as a suspended.  If so,
	 * we redirect the user back to wp-login.php with the 'reauth' parameter.
	 *
	 * This clears the logged-in suspender's cookies and will ask the suspended to
	 * reauthenticate.
	 *
	 * Note: A suspender cannot log back in - {@see boot_suspended_user()}.
	 *
	 * Runs on 'bp_init' at priority 4 so the members component globals are setup
	 * before we do our spammer checks.
	 *
	 * This is important as the $bp->loggedin_user object is setup at priority 4.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function bp_stop_live_suspended() {
		// If we're on the login page, stop now to prevent redirect loop.
		$is_login = false;
		if ( isset( $GLOBALS['pagenow'] ) && ( false !== strpos( $GLOBALS['pagenow'], 'wp-login.php' ) ) ) {
			$is_login = true;
		} elseif ( isset( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], 'wp-login.php' ) ) { // phpcs:ignore
			$is_login = true;
		}

		if ( $is_login ) {
			return;
		}

		// User isn't logged in, so stop!
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = bp_loggedin_user_id();
		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			// Setup login args.
			$args = array(
				// Custom action used to throw an error message.
				'action' => 'bp-suspended',

				// Reauthorize user to login.
				'reauth' => 1,
			);

			/**
			 * Filters the url used for redirection for a logged in user marked as spam.
			 *
			 * @since BuddyPress 1.8.0
			 *
			 * @param string $value URL to redirect user to.
			 */
			$login_url = apply_filters( 'bp_live_suspend_redirect', add_query_arg( $args, wp_login_url() ) );

			// Redirect user to login page.
			wp_safe_redirect( $login_url );
			die();
		}
	}

	/**
	 * Show a custom error message when a logged-in user is marked as a suspended.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function bp_live_suspended_login_error() {
		global $error;

		$error = __( '<strong>ERROR</strong>: Your account has been suspended. Please contact a site admin for more information.', 'buddyboss' ); // phpcs:ignore

		// Shake shake shake!
		add_action( 'login_head', 'wp_shake_js', 12 );
	}

	/**
	 * If the displayed user is marked as a suspended, Show 404.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function restrict_member_profile() {
		$user_id = bp_displayed_user_id();
		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			buddypress()->displayed_user->id = 0;
			bp_do_404();

			return;
		}
	}

	/**
	 * Hide related content
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $member_id     member id.
	 * @param int   $hide_sitewide item hidden sitewide or user specific.
	 * @param array $args          parent args.
	 */
	protected function prepare_suspend_args( $member_id, $hide_sitewide, $args = array() ) {

		$action_suspend = false;
		if ( isset( $args['action'] ) ) {
			$action_suspend = in_array( $args['action'], array( 'suspended', 'unsuspended' ), true );
			unset( $args['action'] );
		}

		if ( empty( $args ) ) {
			$args = array(
				'blocked_user'   => $member_id,
				'user_suspended' => $hide_sitewide,
				'action_suspend' => $action_suspend,
			);
		}

		return $args;
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $member_id member id.
	 * @param array $args      parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $member_id, $args = array() ) {
		$action           = ! empty( $args['action'] ) ? $args['action'] : '';
		$related_contents = array();

		$related_contents[ BP_Suspend_Comment::$type ] = BP_Suspend_Comment::get_member_comment_ids( $member_id, $action );

		/*if ( bp_is_active( 'groups' ) ) {
			$related_contents[ BP_Suspend_Group::$type ] = BP_Suspend_Group::get_member_group_ids( $member_id );
		}*/

		if ( bp_is_active( 'forums' ) ) {
			$related_contents[ BP_Suspend_Forum::$type ]       = BP_Suspend_Forum::get_member_forum_ids( $member_id, $action );
			$related_contents[ BP_Suspend_Forum_Topic::$type ] = BP_Suspend_Forum_Topic::get_member_topic_ids( $member_id, $action );
			$related_contents[ BP_Suspend_Forum_Reply::$type ] = BP_Suspend_Forum_Reply::get_member_reply_ids( $member_id, $action );
		}

		if ( bp_is_active( 'activity' ) ) {
			$related_contents[ BP_Suspend_Activity::$type ]         = BP_Suspend_Activity::get_member_activity_ids( $member_id, $action );
			$related_contents[ BP_Suspend_Activity_Comment::$type ] = BP_Suspend_Activity_Comment::get_member_activity_comment_ids( $member_id, $action );
		}

		if ( bp_is_active( 'document' ) ) {
			$related_contents[ BP_Suspend_Folder::$type ]   = BP_Suspend_Folder::get_member_folder_ids( $member_id, $action );
			$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_member_document_ids( $member_id, $action );
		}

		if ( bp_is_active( 'media' ) ) {
			$related_contents[ BP_Suspend_Album::$type ] = BP_Suspend_Album::get_member_album_ids( $member_id, $action );
			$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_member_media_ids( $member_id, $action );
		}

		if ( bp_is_active( 'video' ) ) {
			$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_member_video_ids( $member_id, $action );
		}

		return $related_contents;
	}

	/**
	 * Delete moderation data when actual user is deleted
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $user_id user id of the user that is being deleted.
	 */
	public function sync_moderation_data_on_delete( $user_id ) {

		if ( empty( $user_id ) ) {
			return;
		}

		BP_Core_Suspend::delete_suspend( $user_id, $this->item_type );
	}

	/**
	 * Migrate existing spammer as suspended user
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function migrate_spam_users() {
		global $wpdb;
		$is_migrated = bp_get_option( 'bpm_migrate_spam_user' );
		if ( empty( $is_migrated ) ) {
			$spam_users = $wpdb->get_results( "SELECT ID FROM {$wpdb->users} WHERE user_status = 1" ); //phpcs:ignore.
			if ( ! empty( $spam_users ) ) {
				foreach ( $spam_users as $spam_user ) {
					self::suspend_user( $spam_user->ID );
					bp_core_process_spammer_status( $spam_user->ID, 'ham' );
				}
			}
			bp_update_option( 'bpm_migrate_spam_user', true );
		}
	}

	/**
	 * Restrict User domain of suspend member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $domain  User domain link.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function bp_core_get_user_domain( $domain, $user_id ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( empty( $username_visible ) && bp_moderation_is_user_suspended( $user_id ) ) {
			return '';
		}

		return $domain;
	}

	/**
	 * Restrict User meta of suspend member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $value   User meta.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function get_the_author_meta( $value, $user_id ) {
		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Restrict User meta name of suspend member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $value   User meta.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function get_the_author_name( $value, $user_id ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;
		if ( ! empty( $username_visible ) || ( bp_is_my_profile() && 'blocked-members' === bp_current_action() ) ) {
			return $value;
		}

		if ( bp_moderation_is_user_suspended( $user_id ) ) {
			return esc_html__( 'Suspended Member', 'buddyboss' );
		}

		return $value;
	}

	/**
	 * Remove Profile photo for suspend member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $retval       The URL of the avatar.
	 * @param mixed  $id_or_email  The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
	 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param array  $args         Arguments passed to get_avatar_data(), after processing.
	 *
	 * @return string
	 */
	public function get_avatar_url( $retval, $id_or_email, $args ) {
		$user = false;

		// Ugh, hate duplicating code; process the user identifier.
		if ( is_numeric( $id_or_email ) ) {
			$user = get_user_by( 'id', absint( $id_or_email ) );
		} elseif ( $id_or_email instanceof WP_User ) {
			// User Object.
			$user = $id_or_email;
		} elseif ( $id_or_email instanceof WP_Post ) {
			// Post Object.
			$user = get_user_by( 'id', (int) $id_or_email->post_author );
		} elseif ( $id_or_email instanceof WP_Comment ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				$user = get_user_by( 'id', (int) $id_or_email->user_id );
			}
		} elseif ( is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
		}

		// No user, so bail.
		if ( false === $user instanceof WP_User ) {
			return $retval;
		}

		if ( bp_moderation_is_user_suspended( $user->ID ) ) {
			return buddypress()->plugin_url . 'bp-core/images/suspended-mystery-man.jpg';
		}

		return $retval;
	}

	/**
	 * Get dummy URL from DB for Group and User
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $avatar_url URL for a locally uploaded avatar.
	 * @param array  $params     Array of parameters for the request.
	 *
	 * @return string $avatar_url
	 */
	public function bp_fetch_avatar_url( $avatar_url, $params ) {

		$item_id = ! empty( $params['item_id'] ) ? absint( $params['item_id'] ) : 0;
		if ( ! empty( $item_id ) && isset( $params['avatar_dir'] ) ) {

			// check for user avatar.
			if ( 'avatars' === $params['avatar_dir'] ) {
				if ( bp_moderation_is_user_suspended( $item_id ) ) {
					$avatar_url = buddypress()->plugin_url . 'bp-core/images/suspended-mystery-man.jpg';
				}
			}
		}

		return $avatar_url;
	}
}
