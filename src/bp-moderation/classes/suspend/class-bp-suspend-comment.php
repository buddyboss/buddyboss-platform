<?php
/**
 * BuddyBoss Suspend Comment Class
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Comment.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Comment extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'comment';

	/**
	 * BP_Suspend_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {
		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_comment' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_comment' ), 10, 4 );

		// Add moderation data when actual post comment is added.
		add_action( 'comment_post', array( $this, 'sync_moderation_data_on_save' ), 10, 3 );

		// Delete moderation data when post comment is deleted.
		add_action( 'delete_comment', array( $this, 'sync_moderation_data_on_delete' ), 10, 3 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'comment_text', array( $this, 'blocked_comment_text' ), 10, 2 );
		add_filter( 'get_comment_author_link', array( $this, 'blocked_get_comment_author_link' ), 10, 3 );
		add_filter( 'get_comment_author', array( $this, 'blocked_get_comment_author' ), 10, 2 );
		add_filter( 'get_comment_link', array( $this, 'blocked_get_comment_link' ), 10, 2 );
		add_filter( 'get_comment_date', array( $this, 'blocked_get_comment_date' ), 10, 3 );
		add_filter( 'get_comment_time', array( $this, 'blocked_get_comment_time' ), 10, 5 );
		add_filter( 'comment_reply_link', array( $this, 'blocked_comment_reply_link' ), 10, 3 );
		add_filter( 'edit_comment_link', array( $this, 'blocked_edit_comment_link' ), 10, 2 );

	}

	/**
	 * Get Blocked member's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id Member id.
	 * @param string $action    Action name to perform.
	 *
	 * @return array
	 */
	public static function get_member_comment_ids( $member_id, $action = '' ) {

		$comment_ids = get_comments(
			array(
				'user_id'                   => $member_id,
				'fields'                    => 'ids',
				'update_comment_meta_cache' => false,
				'update_comment_post_cache' => false,
			)
		);

		if ( 'hide' === $action && ! empty( $comment_ids ) ) {
			foreach ( $comment_ids as $k => $comment_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $comment_id, self::$type, true ) ) {
					unset( $comment_ids[ $k ] );
				}
			}
		}

		return $comment_ids;
	}

	/**
	 * Hide related content of activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $comment_id    comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_comment( $comment_id, $hide_sitewide, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $comment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->hide_related_content( $comment_id, $hide_sitewide, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'hide_related_content' ),
					'args'     => array( $comment_id, $hide_sitewide, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Un-hide related content of activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $comment_id    comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_comment( $comment_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bp_background_updater;

		$suspend_args = wp_parse_args(
			$args,
			array(
				'item_id'   => $comment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		if (
			isset( $suspend_args['author_compare'] ) &&
			true === (bool) $suspend_args['author_compare'] &&
			isset( $suspend_args['type'] ) &&
			$suspend_args['type'] !== self::$type
		) {
			$comment_author_id = BP_Moderation_Comment::get_content_owner_id( $comment_id );
			if ( isset( $suspend_args['blocked_user'] ) && $comment_author_id === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		if ( $this->backgroup_diabled || ! empty( $args ) ) {
			$this->unhide_related_content( $comment_id, $hide_sitewide, $force_all, $args );
		} else {
			$bp_background_updater->push_to_queue(
				array(
					'callback' => array( $this, 'unhide_related_content' ),
					'args'     => array( $comment_id, $hide_sitewide, $force_all, $args ),
				)
			);
			$bp_background_updater->save()->schedule_event();
		}
	}

	/**
	 * Update comment text for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string          $comment_text Text of the current comment.
	 * @param WP_Comment|null $comment      The comment object. Null if not found.
	 *
	 * @return string
	 */
	public function blocked_comment_text( $comment_text, $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			return $comment_text;
		}

		if ( $this->check_is_hidden( $comment->comment_ID ) ) {
			$comment_text = esc_html__( 'This content has been hidden as the member is suspended.', 'buddyboss' );
		}

		return $comment_text;
	}

	/**
	 * Update comment author link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $return     The HTML-formatted comment author link.
	 *                           Empty for an invalid URL.
	 * @param string $author     The comment author's username.
	 * @param int    $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author_link( $return, $author, $comment_id ) {

		if ( $this->check_is_hidden( $comment_id) ) {
			$return = esc_html__( 'Suspended Member', 'buddyboss' );
		}

		return $return;
	}

	/**
	 * Update comment author for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $author     The comment author's username.
	 * @param int    $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author( $author, $comment_id ) {

		if ( $this->check_is_hidden( $comment_id ) ) {
			$author = esc_html__( 'Suspended Member', 'buddyboss' );
		}

		return $author;
	}

	/**
	 * Update comment link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $link    The comment permalink with '#comment-$id' appended.
	 * @param WP_Comment $comment The current comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_link( $link, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $link;
		}

		if ( $this->check_is_hidden( $comment->comment_ID ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Update comment date for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string|int $date    Formatted date string or Unix timestamp.
	 * @param string     $format  The format of the date.
	 * @param WP_Comment $comment The comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_date( $date, $format, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $date;
		}

		if ( $this->check_is_hidden( $comment->comment_ID ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment time for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string|int $date      The comment time, formatted as a date string or Unix timestamp.
	 * @param string     $format    Date format.
	 * @param bool       $gmt       Whether the GMT date is in use.
	 * @param bool       $translate Whether the time is translated.
	 * @param WP_Comment $comment   The comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_time( $date, $format, $gmt, $translate, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $date;
		}

		if ( $this->check_is_hidden( $comment->comment_ID ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment reply link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $link    The HTML markup for the comment reply link.
	 * @param array      $args    An array of arguments overriding the defaults.
	 * @param WP_Comment $comment The object of the comment being replied.
	 *
	 * @return string
	 */
	public function blocked_comment_reply_link( $link, $args, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $link;
		}

		if ( $this->check_is_hidden( $comment->comment_ID ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Update comment edit link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $link       Anchor tag for the edit link.
	 * @param int    $comment_id Comment ID.
	 *
	 * @return string
	 */
	public function blocked_edit_comment_link( $link, $comment_id ) {

		if ( $this->check_is_hidden( $comment_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $comment_id Comment ID.
	 * @param array $args       parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $comment_id, $args = array() ) {

		$related_contents = array();

		if ( bp_is_active( 'activity' ) ) {
			$a_comment_id = get_comment_meta( $comment_id, 'bp_activity_comment_id', true );
			$related_contents[ BP_Suspend_Activity_Comment::$type ] = array( $a_comment_id );
		}

		return $related_contents;
	}

	/**
	 * Fires immediately after a comment is inserted into the database.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int        $comment_id       The comment ID.
	 * @param int|string $comment_approved 1 if the comment is approved, 0 if not, 'spam' if spam.
	 * @param array      $commentdata      Comment data.
	 */
	public function sync_moderation_data_on_save( $comment_id, $comment_approved, $commentdata ) {

		if ( empty( $comment_id ) || empty( $commentdata ) ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $comment_id, BP_Moderation_Comment::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $comment_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Comment::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );
		if ( is_object( $commentdata ) ) {
			$commentdata_user_id = $commentdata->user_id;
		} else {
			$commentdata_user_id = $commentdata['user_id'];
		}
		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $commentdata_user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $comment_id, $commentdata_user_id );
	}

	/**
	 * Update the suspend table to delete the post comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $comment_id The comment ID.
	 */
	public function sync_moderation_data_on_delete( $comment_id ) {

		if ( empty( $comment_id ) ) {
			return;
		}

		BP_Core_Suspend::delete_suspend( $comment_id, $this->item_type );
	}

	/**
	 * Check comment author is suspended or not
	 *
	 * @param int $comment_id comment id.
	 *
	 * @return bool
	 */
	private function check_is_hidden( $comment_id ) {

		if ( BP_Core_Suspend::check_suspended_content( $comment_id, self::$type, true ) ) {
			return true;
		}

		$author_id = BP_Moderation_Comment::get_content_owner_id( $comment_id );
		if ( bp_moderation_is_user_suspended( $author_id ) ) {
			return true;
		}

		return false;
	}
}
