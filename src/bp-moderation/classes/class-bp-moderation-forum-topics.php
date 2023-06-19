<?php
/**
 * BuddyBoss Moderation Forum Topics Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Topics.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Forum_Topics extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'forum_topic';

	/**
	 * BP_Moderation_Forum_Topics constructor.
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
		add_filter( 'bp_suspend_forum_topic_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bbp_get_topic', array( $this, 'restrict_single_item' ), 10, 2 );

		add_filter( 'bbp_get_topic_content', array( $this, 'bb_topic_content_remove_mention_link' ), 10, 2 );

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

		// Prepare report button for topic when activity moderation is disabled.
		if ( bp_is_active( 'activity' ) && ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity::$moderation_type ) ) {
			add_filter( 'bp_activity_get_report_link', array( $this, 'update_report_button_args' ), 10, 2 );
		}

		// Update the where condition for forum Subscriptions.
		add_filter( 'bb_subscriptions_suspend_topic_get_where_conditions', array( $this, 'bb_subscriptions_moderation_where_conditions' ), 10, 2 );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $topic_id Topic id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $topic_id ) {
		return get_post_field( 'post_author', $topic_id );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $topic_id topic id.
	 *
	 * @return string
	 */
	public static function get_permalink( $topic_id ) {
		$url = get_the_permalink( $topic_id );

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
		$content_types[ self::$moderation_type ] = __( 'Forum Discussions', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query remove hidden/blocked user's forum's topic
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   forum's topic Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		global $wpdb;
		$this->alias = $suspend->alias;

		// Remove has blocked/is blocked members discussion from widget.
		$exclude_where = false;
		if ( function_exists( 'bb_did_filter' ) && bb_did_filter( 'bbp_after_topic_widget_settings_parse_args' ) ) {
			$exclude_where = true;
		}

		// Remove has blocked members discussion from widget.
		$sql = $this->exclude_where_query( $exclude_where );
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		if ( true === $exclude_where ) {
			// Remove is blocked members discussion from widget.
			$where['moderation_widget_forums'] = '( ' . $wpdb->posts . '.post_author NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) )';
		}

		return $where;
	}

	/**
	 * Validate the topic is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param object $post   Current topic object.
	 * @param string $output Optional. OBJECT, ARRAY_A, or ARRAY_N. Default = OBJECT.
	 *
	 * @return object|array|null
	 */
	public function restrict_single_item( $post, $output ) {

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $post;
		}

		$post_id = ( ARRAY_A === $output ? $post['ID'] : ( ARRAY_N === $output ? current( $post ) : $post->ID ) );

		if ( $this->is_content_hidden( (int) $post_id ) ) {
			return null;
		}

		return $post;
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
			$button['button_attr']['class'] = 'button item-button bp-secondary-action outline reported-content';
		} else {
			$button['button_attr']['class'] = 'button item-button bp-secondary-action outline report-content';
		}

		return $button;
	}

	/**
	 * Filter to check the topic is valid or not.
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

		$topic = bbp_get_topic( (int) $item_id );

		if ( empty( $topic ) || empty( $topic->ID ) ) {
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
		return esc_html__( 'Report Discussion', 'buddyboss' );
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
		return esc_html__( 'Discussion', 'buddyboss' );
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

		if ( empty( $activity->id ) || 'bbp_topic_create' !== $activity->type ) {
			return $report_button;
		}

		$args['button_attr']['data-bp-content-id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
		$args['button_attr']['data-bp-content-type'] = self::$moderation_type;

		$report_button = bp_moderation_get_report_button( $args, false );

		return $report_button;
	}

	/**
	 * Update where query remove hidden/blocked user's topic subscriptions.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array  $where   Subscription topic Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function bb_subscriptions_moderation_where_conditions( $where, $suspend ) {
		$moderation_where = 'hide_parent = 1 OR hide_sitewide = 1';

		$where['moderation_where'] = $moderation_where;

		return $where;
	}

	/**
	 * Remove mentioned link from discussion's content.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param string $content  Reply content.
	 * @param int    $reply_id Reply id.
	 *
	 * @return string
	 */
	public function bb_topic_content_remove_mention_link( $content, $reply_id ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$content = bb_moderation_remove_mention_link( $content );

		return $content;
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 2.3.50
	 *
	 * @param int $item_id Item id.
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {
		if ( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) {
			return true;
		}
		return false;
	}
}
