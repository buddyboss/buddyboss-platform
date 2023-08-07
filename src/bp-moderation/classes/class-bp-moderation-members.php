<?php
/**
 * BuddyBoss Moderation Members Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Members.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Members extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'user';

	/**
	 * Item type for report member.
	 *
	 * @var string
	 */
	public static $moderation_type_report = 'user_report';

	/**
	 * BP_Moderation_Members constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 */
		add_filter( 'bp_suspend_member_get_where_conditions', array( $this, 'update_where_sql' ), 10, 3 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_member_blocking_enable( 0 ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button", array( $this, 'update_button' ), 10, 2 );
		add_filter( 'bp_init', array( $this, 'restrict_member_profile' ), 4 );

		add_filter( 'bp_core_get_user_domain', array( $this, 'bp_core_get_user_domain' ), 9999, 2 );
		add_filter( 'bp_core_get_userlink', array( $this, 'bp_core_get_userlink' ), 9999, 2 );
		add_filter( 'get_the_author_user_nicename', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_login', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_user_email', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_the_author_display_name', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'bp_core_get_user_displayname', array( $this, 'get_the_author_name' ), 9999, 2 );
		add_filter( 'get_avatar_url', array( $this, 'get_avatar_url' ), 9999, 3 );
		add_filter( 'bp_core_fetch_avatar_url_check', array( $this, 'bp_fetch_avatar_url' ), 1005, 2 );
		add_filter( 'bp_core_fetch_gravatar_url_check', array( $this, 'bp_fetch_avatar_url' ), 1005, 2 );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		add_action( 'bb_activity_before_permalink_redirect_url', array( $this, 'bb_activity_before_permalink_redirect_url' ), 10, 1 );
		add_action( 'bb_activity_after_permalink_redirect_url', array( $this, 'bb_activity_after_permalink_redirect_url' ), 10, 1 );

		add_filter( 'bb_member_directories_get_profile_actions', array( $this, 'bb_member_directories_remove_profile_actions' ), 9999, 2 );
		add_filter( 'bp_member_type_name_string', array( $this, 'bb_remove_member_type_name_string' ), 9999, 3 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $member_id member id.
	 *
	 * @return string
	 */
	public static function get_permalink( $member_id ) {
		return add_query_arg( array( 'modbypass' => 1 ), bp_core_get_user_domain( $member_id ) );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'User', 'buddyboss' );

		if ( bb_is_moderation_member_reporting_enable() ) {
			$content_types[ self::$moderation_type_report ] = __( 'Report Member', 'buddyboss' );
		}

		return $content_types;
	}

	/**
	 * Update where query remove blocked users
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $where       blocked users Where sql.
	 * @param object $suspend     suspend object.
	 * @param string $column_name Table column name.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend, $column_name ) {
		$this->alias = $suspend->alias;

		$blocked_user_query = true;

		if (
			(
				function_exists( 'bp_is_group_members' ) &&
				bp_is_group_members()
			) ||
			(
				function_exists( 'bp_get_group_current_admin_tab' ) &&
				'manage-members' === bp_get_group_current_admin_tab()
			) ||
			(
				! empty( $GLOBALS['wp']->query_vars['rest_route'] ) &&
				preg_match( '/buddyboss\/v+(\d+)\/groups\/+(\d+)\/members/', $GLOBALS['wp']->query_vars['rest_route'], $matches ) &&
				empty( $_REQUEST['scope'] ) &&
				empty( $_REQUEST['show-all'] )
			)
		) {
			$blocked_user_query = false;
		}

		$sql = $this->exclude_where_query( $blocked_user_query );
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		if ( true === $blocked_user_query ) {
			$where['moderation_blocked_by_where'] = "( u.{$column_name} NOT IN (" . bb_moderation_get_blocked_by_sql() . ') )';
		}

		return $where;
	}

	/**
	 * Function to modify the button class
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $button      Button args.
	 * @param string $is_reported Item reported.
	 *
	 * @return string
	 */
	public function update_button( $button, $is_reported ) {
		if ( $is_reported ) {
			$button['button_attr']['class'] = 'blocked-member';
		} else {
			$button['button_attr']['class'] = 'block-member';
		}

		return $button;
	}

	/**
	 * If the displayed user is marked as a blocked, Show 404.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function restrict_member_profile() {
		$user_id = bp_displayed_user_id();
		if (
			! bp_is_single_activity() &&
			(
				bp_moderation_is_user_blocked( $user_id ) ||
				bb_moderation_is_user_blocked_by( $user_id )
			)
		) {
			buddypress()->displayed_user->id = 0;
			bp_do_404();

			return;
		}
	}

	/**
	 * Restrict User domain of blocked member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $domain  User domain link.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function bp_core_get_user_domain( $domain, $user_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		// Alowed to view single group activity.
		if (
			bp_is_single_activity() &&
			! wp_doing_ajax()
		) {
			return $domain;
		}

		if (
			empty( $username_visible ) &&
			! bp_moderation_is_user_suspended( $user_id ) &&
			(
				bp_moderation_is_user_blocked( $user_id ) ||
				bb_moderation_is_user_blocked_by( $user_id )
			)
		) {
			return ''; // To allow to make this function working bp_core_get_userlink() which update using below function.
		}

		return $domain;
	}

	/**
	 * Filters the link text for the passed in user.
	 *
	 * @since BuddyBoss 2.2.5
	 *
	 * @param string $value   Link text based on passed parameters.
	 * @param int    $user_id ID of the user to check.
	 *
	 * @return string
	 */
	public function bp_core_get_userlink( $value, $user_id ) {
		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( empty( $username_visible ) && ( bp_moderation_is_user_blocked( $user_id ) || bb_moderation_is_user_blocked_by( $user_id ) ) ) {
			return '<a>' . bp_core_get_user_displayname( $user_id ) . '</a>';
		}

		return $value;
	}

	/**
	 * Restrict User meta of blocked member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $value   User meta.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function get_the_author_meta( $value, $user_id ) {
		if ( bp_moderation_is_user_blocked( $user_id ) ) {
			return '';
		}

		return $value;
	}

	/**
	 * Restrict User meta name of blocked member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $value   User meta.
	 * @param int    $user_id User id.
	 *
	 * @return string
	 */
	public function get_the_author_name( $value, $user_id ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;
		if ( ! empty( $username_visible ) || ( bp_is_my_profile() && 'blocked-members' === bp_current_action() ) ) {
			return $value;
		}

		$user = get_userdata( $user_id );

		if ( empty( $user ) || ! empty( $user->deleted ) ) {
			return bb_moderation_is_deleted_label();
		}

		if ( ! bp_moderation_is_user_suspended( $user_id ) ) {
			if ( bp_moderation_is_user_blocked( $user_id ) ) {
				return bb_moderation_has_blocked_label( $value, $user_id );
			} elseif ( bb_moderation_is_user_blocked_by( $user_id ) ) {
				return bb_moderation_is_blocked_label( $value, $user_id );
			}
		}

		return $value;
	}

	/**
	 * Remove Profile photo for block member.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param  string $retval      The URL of the avatar.
	 * @param  mixed  $id_or_email The Gravatar to retrieve. Accepts a user_id, gravatar md5 hash,
	 *                             user email, WP_User object, WP_Post object, or WP_Comment object.
	 * @param  array  $args        Arguments passed to get_avatar_data(), after processing.
	 * @return string
	 */
	public function get_avatar_url( $retval, $id_or_email, $args ) {
		$user       = false;
		$old_retval = $retval;

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

		if ( bp_moderation_is_user_blocked( $user->ID ) ) {
			$retval = bb_moderation_has_blocked_avatar( $retval, $user->ID, $args );
		} elseif ( bb_moderation_is_user_blocked_by( $user->ID ) ) {
			$retval = bb_moderation_is_blocked_avatar( $user->ID, $args );
		}

		/**
		 * Filter to update blocked avatar url.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param string $retval         The URL of the avatar.
		 * @param string $old_avatar_url URL for a originally uploaded avatar.
		 * @param array  $args           Arguments passed to get_avatar_data(), after processing.
		 */
		return apply_filters( 'bb_get_blocked_avatar_url', $retval, $old_retval, $args );
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

		$item_id        = ! empty( $params['item_id'] ) ? absint( $params['item_id'] ) : 0;
		$old_avatar_url = $avatar_url;

		if ( ! empty( $item_id ) && isset( $params['avatar_dir'] ) ) {

			// check for user avatar.
			if ( 'avatars' === $params['avatar_dir'] ) {
				if ( bp_moderation_is_user_blocked( $item_id ) ) {
					$avatar_url = bb_moderation_has_blocked_avatar( $avatar_url, $item_id, $params );
				} elseif ( bb_moderation_is_user_blocked_by( $item_id ) ) {
					$avatar_url = bb_moderation_is_blocked_avatar( $item_id, $params );
				}
			}
		}

		/**
		 * Filter to update blocked avatar url.
		 *
		 * @since BuddyBoss 2.1.4
		 *
		 * @param string $avatar_url     URL for a locally uploaded avatar.
		 * @param string $old_avatar_url URL for a originally uploaded avatar.
		 * @param array  $params         Array of parameters for the request.
		 */
		return apply_filters( 'bb_get_blocked_avatar_url', $avatar_url, $old_avatar_url, $params );
	}

	/**
	 * Filter to check the member is valid or not.
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

		$user = get_userdata( (int) $item_id );
		if ( empty( $user ) || ! $user->exists() ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Function to allowed blocked member URL for group single activity.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 */
	public function bb_activity_before_permalink_redirect_url( $activity ) {
		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
			remove_filter( 'bp_core_get_user_domain', array( $this, 'bp_core_get_user_domain' ), 9999, 2 );
		}
	}

	/**
	 * Function to dis-allowed blocked member URL for group single activity.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 */
	public function bb_activity_after_permalink_redirect_url( $activity ) {
		if ( bp_is_active( 'groups' ) && buddypress()->groups->id === $activity->component ) {
			add_filter( 'bp_core_get_user_domain', array( $this, 'bp_core_get_user_domain' ), 9999, 2 );
		}
	}

	/**
	 * Function to remove profile action if member is hasblocked/isblocked.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param array $buttons Member profile actions.
	 * @param int   $user_id Member ID.
	 *
	 * @return array|string Return the member actions.
	 */
	public function bb_member_directories_remove_profile_actions( $buttons, $user_id ) {
		if ( bp_moderation_is_user_blocked( $user_id ) ) {
			$buttons['primary']   = '';
			$buttons['secondary'] = '';
		} elseif ( bb_moderation_is_user_blocked_by( $user_id ) ) {
			$buttons['primary']   = '';
			$buttons['secondary'] = '';
		}

		return $buttons;
	}

	/**
	 * Logged in member is blocked by members from group, then loggedin member can not see member type of is blocked by members.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param string $string      Member type html.
	 * @param string $member_type Member type.
	 * @param int    $user_id     Member ID.
	 *
	 * @return array|string Return the member actions.
	 */
	public function bb_remove_member_type_name_string( $string, $member_type, $user_id ) {
		if ( bb_moderation_is_user_blocked_by( $user_id ) ) {
			$string = '';
		}

		return $string;
	}
}
