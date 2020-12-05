<?php
/**
 * BuddyBoss Moderation Forum Topics Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Forum Topics.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Check Component is disabled
		if ( ! bp_is_active( 'document' ) ){
			return;
		}

		// delete topic moderation data when actual topic deleted.
		add_action( 'after_delete_post', array( $this, 'sync_moderation_data_on_delete' ), 10, 2 );

		/**
		 * Moderation code should not add for WordPress backend oror Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		// Remove hidden/blocked users content
		add_filter( 'bp_suspend_forum_topic_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// button class.
		add_filter( 'bp_moderation_get_report_button_args', array( $this, 'update_button_args' ), 10, 3 );

	}

	/**
	 * Get Content.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $topic_id  Topic id.
	 * @param bool    $view_link add view link.
	 *
	 * @return string
	 */
	public static function get_content_excerpt( $topic_id, $view_link = false ) {
		$topic_content = get_post_field( 'post_content', $topic_id );

		if ( true === $view_link ) {
			$link = '<a href="' . esc_url( self::get_permalink( (int) $topic_id ) ) . '">' . esc_html__( 'View',
					'buddyboss' ) . '</a>';;

			$topic_content = ( ! empty( $topic_content ) ) ? $topic_content . ' ' . $link : $link;
		}

		return ( ! empty( $topic_content ) ) ? $topic_content : '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
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
	 * Report content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return string
	 */
	public static function report( $args ) {
		return parent::report( $args );
	}

	/**
	 * Hide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function hide( $args ) {
		return parent::hide( $args );
	}

	/**
	 * Unhide Moderated content
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function unhide( $args ) {
		return parent::unhide( $args );
	}

	/**
	 * Delete Moderated report
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $args Content data.
	 *
	 * @return BP_Moderation|WP_Error
	 */
	public static function delete( $args ) {
		return parent::delete( $args );
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
		$content_types[ self::$moderation_type ] = __( 'Discussion', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Function to delete topic moderation data when actual topic is deleted
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int    $topic_id topic id being deleted.
	 * @param object $topic    topic data.
	 */
	public function sync_moderation_data_on_delete( $topic_id, $topic ) {
		if ( ! empty( $topic_id ) && ! empty( $topic ) && bbp_get_topic_post_type() === $topic->post_type ) {
			$moderation_obj = new BP_Moderation( $topic_id, self::$moderation_type );
			if ( ! empty( $moderation_obj->id ) ) {
				$moderation_obj->delete( true );
			}
		}
	}

	/**
	 * Update where query remove hidden/blocked user's forum's topic
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where forum's topic Where sql
	 * @param object $suspend suspend object
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
	 * @param string $item_type   Content type.
	 * @param string $is_reported Item reported.
	 *
	 * @return string
	 */
	public function update_button_args( $button, $item_type, $is_reported ) {

		if ( self::$moderation_type === $item_type ) {
			if ( $is_reported ) {
				$button['button_attr']['class'] = 'button item-button bp-secondary-action outline reported-content';
			} else {
				$button['button_attr']['class'] = 'button item-button bp-secondary-action outline report-content';
			}
		}

		return $button;
	}
}
