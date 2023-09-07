<?php
/**
 * BuddyBoss Moderation Comment Class
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Comment.
 *
 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {
		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Register moderation data.
		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ), 11 );

		add_filter( 'comment_reply_link', array( $this, 'add_report_button' ), 999, 3 );

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button", array( $this, 'update_button' ), 10, 2 );

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
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

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );
		add_filter( 'get_comment', array( $this, 'bb_blocked_comment_author_url' ), 10, 1 );
		add_filter( 'comments_clauses', array( $this, 'bb_blocked_comments_pre_query' ), 10, 2 );
		add_filter( 'get_comment_excerpt', array( $this, 'bb_blocked_get_comment_excerpt' ), 10, 3 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $comment_id comment id.
	 *
	 * @return string
	 */
	public static function get_permalink( $comment_id ) {
		if ( empty( $comment_id ) ) {
			return '';
		}

		$get_comment = get_comment( $comment_id );
		if ( empty( $get_comment ) ) {
			return '';
		}

		$url = esc_url( get_comment_link( $get_comment ) );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
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
		$content_types[ self::$moderation_type ] = __( 'Blog Comments', 'buddyboss' );

		return $content_types;
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

		$comment_text = bb_moderation_remove_mention_link( $comment_text );

		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$is_user_blocked = bp_moderation_is_user_blocked( $comment_author_id );

			if ( $is_user_blocked ) {
				$comment_text = bb_moderation_has_blocked_message( $comment_text, $this->item_type, $comment->comment_ID );
			} else {
				$comment_text = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
			}
		} elseif ( bb_moderation_is_user_blocked_by( $comment_author_id ) ) {
			$comment_text = bb_moderation_is_blocked_message( $comment_text, $this->item_type, $comment->comment_ID );
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

		$user_id = self::get_content_owner_id( $comment_id );
		if ( bp_moderation_is_user_blocked( $user_id ) ) {
			return bb_moderation_is_blocked_label( $return, $user_id );
		} elseif ( bb_moderation_is_user_blocked_by( $user_id ) ) {
			return bb_moderation_has_blocked_label( $return, $user_id );
		}

		return $return;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
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

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
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

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
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

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
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

		$user_id = self::get_content_owner_id( $comment->comment_ID );

		if ( $this->is_content_hidden( $comment->comment_ID ) || bb_moderation_is_user_blocked_by( $user_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Add report buttong
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $link    The HTML markup for the comment reply link.
	 * @param array      $args    An array of arguments overriding the defaults.
	 * @param WP_Comment $comment The object of the comment being replied.
	 *
	 * @return string
	 */
	public function add_report_button( $link, $args, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $link;
		}

		if ( ! empty( $link ) && bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			$comment_report_link = bp_moderation_get_report_button(
				array(
					'id'                => 'comment_report',
					'component'         => 'moderation',
					'must_be_logged_in' => true,
					'button_attr'       => array(
						'data-bp-content-id'   => $comment->comment_ID,
						'data-bp-content-type' => self::$moderation_type,
						'class'                => 'report-content',
					),
				)
			);
			if ( ! empty( $comment_report_link ) ) {
				$link .= sprintf( '<div class="bb_more_options"><span class="bb_more_options_action" data-balloon-pos="up" data-balloon="%s"><i class="bb-icon-f bb-icon-ellipsis-h"></i></span><div class="bb_more_options_list">%s</div></div>', esc_html__( 'More Options', 'buddyboss' ), $comment_report_link );
			}
		}

		return $link;
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
			$button['button_attr']['class'] = 'reported-content';
		} else {
			$button['button_attr']['class'] = 'report-content';
		}

		return $button;
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

		if ( $this->is_content_hidden( $comment_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int  $item_id
	 * @param bool $check_hidden
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id, $check_hidden = true ) {

		$author_id = self::get_content_owner_id( $item_id );

		if ( ( $this->is_member_blocking_enabled() && ! empty( $author_id ) && ! bp_moderation_is_user_suspended( $author_id ) && bp_moderation_is_user_blocked( $author_id ) ) ||
			 ( $check_hidden && $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
			return true;
		}

		return false;
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
		return esc_html__( 'Report Comment', 'buddyboss' );
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
		return esc_html__( 'Comment', 'buddyboss' );
	}

	/**
	 * If members url is not set then set member url for the blog comment.
	 *
	 * @since BuddyBoss 2.2.5
	 *
	 * @param object $comment Comment data.
	 *
	 * @return object $comment Comment data.
	 */
	public function bb_blocked_comment_author_url( $comment ) {
		if ( $comment->user_id && ! $comment->comment_author_url ) {
			$comment->comment_author_url = bp_core_get_user_domain( $comment->user_id );
		}

		return $comment;
	}

	/**
	 * Function to exclude is_blocked and has_blocked users comment from recent comment widget.
	 *
	 * @since BuddyBoss 2.2.5
	 *
	 * @param string[] $comment_data An associative array of comment query clauses.
	 * @param object   $query        Current instance of WP_Comment_Query (passed by reference).
	 *
	 * @return mixed
	 */
	public function bb_blocked_comments_pre_query( $comment_data, $query ) {
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'widget_comments_args' ) ) {
			global $wpdb;
			$this->alias = 's';
			$sql         = $this->exclude_where_query();
			if ( ! empty( $sql ) ) {
				$comment_data['where'] .= ' AND ' . $sql;
			}
			$blocked_by_query = bb_moderation_get_blocked_by_sql( bp_loggedin_user_id() );
			if ( ! empty( $blocked_by_query ) ) {
				$comment_data['where'] .= ' AND ' . $wpdb->comments . '.user_id NOT IN ( ' . $blocked_by_query . ' )';
			}
		}

		return $comment_data;
	}

	/**
	 * Update comment excerpt text for blocked comment.
	 *
	 * @since BuddyBoss 2.2.5
	 *
	 * @param string     $excerpt    The comment excerpt text.
	 * @param string     $comment_id The comment ID as a numeric string.
	 * @param WP_Comment $comment    The comment object.
	 *
	 * @return mixed|string
	 */
	public function bb_blocked_get_comment_excerpt( $excerpt, $comment_id, $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			return $excerpt;
		}

		$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;

		if ( $this->is_content_hidden( $comment_id ) ) {
			$is_user_blocked = bp_moderation_is_user_blocked( $comment_author_id );

			if ( $is_user_blocked ) {
				$excerpt = bb_moderation_has_blocked_message( $excerpt, $this->item_type, $comment->comment_ID );
			} else {
				$excerpt = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
			}
		} elseif ( bb_moderation_is_user_blocked_by( $comment_author_id ) ) {
			$excerpt = bb_moderation_is_blocked_message( $excerpt, $this->item_type, $comment->comment_ID );
		}

		return $excerpt;
	}
}
