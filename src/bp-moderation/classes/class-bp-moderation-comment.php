<?php
/**
 * BuddyBoss Moderation Comment Class
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 *
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Comment.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'comment';

	/**
	 * BP_Moderation_Comment constructor.
	 *
	 * @since BuddyBoss 2.0.0
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

		add_filter( 'comment_text', array( $this, 'blocked_comment_text' ), 10, 2 );
		add_filter( 'get_comment_author_link', array( $this, 'blocked_get_comment_author_link' ), 10, 3 );
		add_filter( 'get_comment_author', array( $this, 'blocked_get_comment_author' ), 10, 2 );
		add_filter( 'get_comment_link', array( $this, 'blocked_get_comment_link' ), 10, 2 );
		add_filter( 'get_comment_date', array( $this, 'blocked_get_comment_date' ), 10, 3 );
		add_filter( 'get_comment_time', array( $this, 'blocked_get_comment_time' ), 10, 5 );
		add_filter( 'comment_reply_link', array( $this, 'blocked_comment_reply_link' ), 10, 3 );
		add_filter( 'edit_comment_link', array( $this, 'blocked_edit_comment_link' ), 10, 2 );

		// button class.
		add_filter( 'bp_moderation_get_report_button_args', array( $this, 'update_button_args' ), 10, 3 );
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
		$content_types[ self::$moderation_type ] = __( 'Comment', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Check comment is blocked or not.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int       $comment_id        The comment ID.
	 * @param int|false $comment_author_id The comment author ID.
	 *
	 * @return bool
	 */
	private function is_blocked( $comment_id, $comment_author_id = false ) {
		if ( in_array( $comment_id, self::get_sitewide_hidden_ids(), true ) ||
			 ( ! empty( $comment_author_id ) && bp_moderation_is_user_suspended( $comment_author_id, true ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Update comment text for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string          $comment_text Text of the current comment.
	 * @param WP_Comment|null $comment      The comment object. Null if not found.
	 *
	 * @return string
	 */
	public function blocked_comment_text( $comment_text, $comment ) {
		if ( ! $comment instanceof WP_Comment ){
			return $comment_text;
		}

		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
		if ( $this->is_blocked( (int) $comment->comment_ID, (int) $comment_author_id ) ) {
			$is_user_blocked   = bp_moderation_is_user_suspended( $comment_author_id, true );
			$is_user_suspended = bp_moderation_is_user_suspended( $comment_author_id );
			if ( $is_user_suspended ) {
				$comment_text = esc_html__( 'Content from suspended user.', 'buddyboss' );
			} elseif ( $is_user_blocked ) {
				$comment_text = esc_html__( 'Content from blocked user.', 'buddyboss' );
			} else {
				$comment_text = esc_html__( 'Blocked Content.', 'buddyboss' );
			}
		}

		return $comment_text;
	}

	/**
	 * Update comment author link for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $return     The HTML-formatted comment author link.
	 *                           Empty for an invalid URL.
	 * @param string $author     The comment author's username.
	 * @param int    $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author_link( $return, $author, $comment_id ) {
		$comment_author_id = self::get_content_owner_id( $comment_id );
		if ( $this->is_blocked( (int) $comment_id, (int) $comment_author_id ) ) {
			$is_user_blocked   = bp_moderation_is_user_suspended( $comment_author_id, true );
			if ( $is_user_blocked ) {
				$return = esc_html__( 'User Blocked.', 'buddyboss' );
			}
		}

		return $return;
	}

	/**
	 * Update comment author for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string     $author     The comment author's username.
	 * @param int        $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author( $author, $comment_id ) {
		$comment_author_id = self::get_content_owner_id( $comment_id );
		if ( $this->is_blocked( (int) $comment_id, (int) $comment_author_id ) ) {
			$is_user_blocked   = bp_moderation_is_user_suspended( $comment_author_id, true );
			if ( $is_user_blocked ) {
				$author = esc_html__( 'User Blocked.', 'buddyboss' );
			}
		}

		return $author;
	}

	/**
	 * Update comment link for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string     $link    The comment permalink with '#comment-$id' appended.
	 * @param WP_Comment $comment The current comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_link( $link, $comment ) {
		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
		if ( $this->is_blocked( (int) $comment->comment_ID, (int) $comment_author_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Update comment date for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string|int $date    Formatted date string or Unix timestamp.
	 * @param string     $format  The format of the date.
	 * @param WP_Comment $comment The comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_date( $date, $format, $comment ) {
		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
		if ( $this->is_blocked( (int) $comment->comment_ID, (int) $comment_author_id ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment time for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
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
		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
		if ( $this->is_blocked( (int) $comment->comment_ID, (int) $comment_author_id ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment reply link for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string     $link    The HTML markup for the comment reply link.
	 * @param array      $args    An array of arguments overriding the defaults.
	 * @param WP_Comment $comment The object of the comment being replied.
	 *
	 * @return string
	 */
	public function blocked_comment_reply_link( $link, $args, $comment ) {
		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
		if ( $this->is_blocked( (int) $comment->comment_ID, (int) $comment_author_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Update comment edit link for blocked comment.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $link       Anchor tag for the edit link.
	 * @param int    $comment_id Comment ID.
	 *
	 * @return string
	 */
	public function blocked_edit_comment_link( $link, $comment_id ) {
		$comment_author_id = self::get_content_owner_id( $comment_id );
		if ( $this->is_blocked( (int) $comment_id, (int) $comment_author_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $comment_id Comment id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $comment_id ) {
		$comment = get_comment( $comment_id );

		return ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int  $comment_id comment id.
	 * @param bool $view_link  add view link
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $comment_id, $view_link = false ) {
		$comment = get_comment( $comment_id );

		$comment_content = ( ! empty( $comment->comment_content ) ) ? $comment->comment_content : '';

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $comment_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$comment_content = ( ! empty( $comment_content ) ) ? $comment_content . ' ' . $link : $link;
		}

		return $comment_content;
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $comment_id comment id.
	 *
	 * @return string
	 */
	public static function get_permalink( $comment_id ) {
		$url = get_comment_link( $comment_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Get blocked comment ids.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array
	 */
	public static function get_sitewide_hidden_ids() {
		$hidden_comment_id = self::get_sitewide_hidden_item_ids( self::$moderation_type );

		return $hidden_comment_id;
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
	 * @return array
	 */
	public function update_button_args( $button, $item_type, $is_reported ) {

		if ( self::$moderation_type === $item_type ) {
			if ( $is_reported ) {
				$button['button_attr']['class'] = 'reported-content';
			} else {
				$button['button_attr']['class'] = 'report-content';
			}
		}

		return $button;
	}
}
