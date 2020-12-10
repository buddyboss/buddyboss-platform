<?php
/**
 * BuddyBoss Moderation Forum Replies Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Replies.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Check Component is disabled.
		if ( ! bp_is_active( 'document' ) ) {
			return;
		}

		// delete reply moderation data when actual reply deleted.
		add_action( 'after_delete_post', array( $this, 'delete_moderation_data' ), 10, 2 );

		/**
		 * Moderation code should not add for WordPress backend oror Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content.
		add_filter( 'bp_suspend_forum_reply_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// button.
		add_filter( "bp_moderation_{$this->item_type}_button", array( $this, 'update_button' ), 10, 2 );

		// Blocked template.
		add_filter( 'bbp_locate_template_names', array( $this, 'locate_blocked_template' ) );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Reply', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to delete reply moderation data when actual reply is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $reply_id reply id being deleted.
	 * @param object $reply    reply data.
	 */
	public function sync_moderation_data_on_delete( $reply_id, $reply ) {
		if ( ! empty( $reply_id ) && ! empty( $reply ) && bbp_get_reply_post_type() === $reply->post_type ) {
			$moderation_obj = new BP_Moderation( $reply_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}

	/**
	 * Update where query Remove hidden/blocked user's forum's replies
	 *
	 * @param string $where   forum's replies Where sql.
	 * @param object $suspend suspend object.
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
	 * @since BuddyBoss 2.0.0
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

		$reply_id = bbp_get_reply_id();

		if ( BP_Core_Suspend::check_hidden_content( $reply_id, self::$moderation_type ) ) {
			return 'loop-blocked-single-reply.php';
		}

		return $template_names;
	}
}
