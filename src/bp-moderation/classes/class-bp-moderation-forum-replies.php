<?php
/**
 * BuddyBoss Moderation Forum Replies Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Replies.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Forum_Replies extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_reply';

	/**
	 * BP_Moderation_Forum_Replies constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		if ( ! bp_is_active( 'forums' ) ) {
			return;
		}

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend oror Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_forum_reply_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bbp_locate_template_names', array( $this, 'locate_blocked_template' ) );

		add_filter( 'bbp_get_reply_content', array( $this, 'bb_reply_content_remove_mention_link' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button", array( $this, 'update_button' ), 10, 2 );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );

		// Prepare report button for reply when activity moderation is disabled.
		if ( bp_is_active( 'activity' ) && ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity::$moderation_type ) ) {
			add_filter( 'bp_activity_get_report_link', array( $this, 'update_report_button_args' ), 10, 2 );
		}

		add_filter( 'bb_forum_before_activity_content', array( $this, 'bb_blocked_forum_before_activity_content' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $reply_id reply id.
	 *
	 * @return string
	 */
	public static function get_permalink( $reply_id ) {
		$url = get_the_permalink( bbp_get_reply_topic_id( $reply_id ) ) . '#post-' . $reply_id;

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $reply_id Reply id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $reply_id ) {
		return get_post_field( 'post_author', $reply_id );
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
		$content_types[ self::$moderation_type ] = __( 'Forum Replies', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query Remove hidden/blocked user's forum's replies
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   forum's replies Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		global $wpdb;
		$this->alias = $suspend->alias;

		// Remove has blocked/is blocked members replies from widget.
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_replies_widget_settings_parse_args' ) ) {
			// Remove has blocked members replies from widget.
			$sql = $this->exclude_where_query();
			if ( ! empty( $sql ) ) {
				$where['moderation_where'] = $sql;
			}

			// Remove is blocked members replies from widget.
			$where['moderation_widget_forums'] = '( ' . $wpdb->posts . '.post_author NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) )';
		}

		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_replies_widget_settings_parse_args' ) ) {
			$where['moderation_widget_replies'] = '( ' . $wpdb->posts . '.post_author NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) )';
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
			$button['button_attr']['class'] = 'reported-content';
		} else {
			$button['button_attr']['class'] = 'report-content';
		}

		return $button;
	}

	/**
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $template_names Template name.
	 *
	 * @return string
	 */
	public function locate_blocked_template( $template_names ) {

		if ( 'loop-single-reply.php' !== $template_names ) {
			if ( ! is_array( $template_names ) || ! in_array( 'loop-single-reply.php', $template_names, true ) ) {
				return $template_names;
			}
		}

		$reply_id        = bbp_get_reply_id();
		$reply_author_id = bbp_get_reply_author_id( $reply_id );

		if ( $this->is_content_hidden( $reply_id ) || bb_moderation_is_user_blocked_by( $reply_author_id ) ) {
			return 'loop-blocked-single-reply.php';
		}

		return $template_names;
	}

	/**
	 * Filter to check the reply is valid or not.
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

		$reply = bbp_get_reply( (int) $item_id );

		if ( empty( $reply ) || empty( $reply->ID ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {

		$author_id = self::get_content_owner_id( $item_id );

		if ( ( $this->is_member_blocking_enabled() && ! empty( $author_id ) && ! bp_moderation_is_user_suspended( $author_id ) && bp_moderation_is_user_blocked( $author_id ) ) ||
			 ( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
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
		return esc_html__( 'Report Reply', 'buddyboss' );
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
		return esc_html__( 'Reply', 'buddyboss' );
	}

	/**
	 * Function to update activity report button arguments.
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param array $report_button Activity report button
	 * @param array $args          Arguments
	 *
	 * @return array|string
	 */
	public function update_report_button_args( $report_button, $args ) {
		$activity = new BP_Activity_Activity( $args['button_attr']['data-bp-content-id'] );

		if ( empty( $activity->id ) || 'bbp_reply_create' !== $activity->type ) {
			return $report_button;
		}

		$args['button_attr']['data-bp-content-id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
		$args['button_attr']['data-bp-content-type'] = self::$moderation_type;

		$report_button = bp_moderation_get_report_button( $args, false );

		return $report_button;
	}

	/**
	 * Remove mentioned link from discussion's reply.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param string $content  Reply content.
	 * @param int    $reply_id Reply id.
	 *
	 * @return string
	 */
	public function bb_reply_content_remove_mention_link( $content, $reply_id ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$content = bb_moderation_remove_mention_link( $content );

		return $content;
	}

	/**
	 * Function to prevent forum activity content if content will created by hasblocked/isblocked members
	 * and applied filters ( bb_moderation_has_blocked_message, bb_moderation_is_blocked_message ) to restrict content.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param $content  Forum reply content.
	 * @param $activity Activity object data.
	 *
	 * @return string
	 */
	public function bb_blocked_forum_before_activity_content( $content, $activity ) {
		if ( empty( $activity ) ) {
			return;
		}

		$is_forum_activity = false;
		if (
			bp_is_active( 'forums' )
			&& in_array(
				$activity->type,
				array(
					'bbp_reply_create',
				),
				true
			)
			&& bp_is_forums_media_support_enabled()
		) {
			$is_forum_activity = true;
		}
		if ( true === $is_forum_activity ) {
			if ( bp_moderation_is_user_blocked( $activity->user_id ) ) {
				$content = bb_moderation_has_blocked_message( $content, $this->item_type, $activity->id );
			}
			if ( bb_moderation_is_user_blocked_by( $activity->user_id ) ) {
				$content = bb_moderation_is_blocked_message( $content, $this->item_type, $activity->id );
			}
			if ( bp_moderation_is_user_suspended( $activity->user_id ) ) {
				$content = bb_moderation_is_suspended_message( $content, $this->item_type, $activity->id );
			}
		}

		return $content;
	}
}
