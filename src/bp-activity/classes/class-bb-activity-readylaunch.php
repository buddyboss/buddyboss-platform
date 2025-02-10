<?php
/**
 * BuddyBoss Activity Readylaunch.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Activity\Classes
 */

class BB_Activity_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss [BBVERSION]
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		add_filter( 'bb_get_activity_post_user_reactions_html', array( $this, 'bb_rl_get_activity_post_user_reactions_html' ), 10, 4 );
		add_filter( 'bp_activity_new_update_action', array( $this, 'bb_rl_activity_new_update_action' ), 10, 2 );
		add_filter( 'bp_groups_format_activity_action_activity_update', array( $this, 'bb_rl_activity_new_update_action' ), 10, 2 );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return Controller|BB_Activity_Readylaunch|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	public function bb_rl_get_activity_post_user_reactions_html( $output, $activity_id, $item_type, $is_popup ) {
		$output = '';

		if ( empty( $activity_id ) ) {
			return $output;
		}

		$reaction_count_class = 'activity-reactions_count';
		if ( 'activity_comment' === $item_type ) {
			$reaction_count_class = 'comment-reactions_count';
		}

		$most_reactions = bb_get_activity_most_reactions( $activity_id, $item_type );
		if ( ! empty( $most_reactions ) ) {
			$output .= '<div class="activity-state-reactions">';

			foreach ( $most_reactions as $reaction ) {
				$icon   = bb_activity_prepare_emotion_icon( $reaction['id'] );
				$output .= sprintf(
					'<div class="reactions_item">
				%s
				</div>',
					$icon
				);
			}

			$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
				array(
					'item_id'   => $activity_id,
					'item_type' => 'activity',
				)
			);
			if ( ! empty( $reaction_count ) ) {
				$output .= sprintf(
					'<div class="%1$s">%2$s</div>',
					$reaction_count_class,
					$reaction_count
				);
			}

			$output .= '</div>';
		}

		// Build popup to show reacted items.
		if ( $is_popup ) {
			$output .= '<div class="activity-state-popup">
				<div class="activity-state-popup_overlay"></div>
				<div class="activity-state-popup_inner" id="reaction-content-' . esc_attr( $activity_id ) . '">
				</div>
			</div>';
		}

		return $output;
	}

	/**
	 * Update activity action for ReadyLaunch.
	 * This function will only return the user link.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $action   The activity action.
	 * @param object $activity The activity object.
	 *
	 * @return bool|string
	 */
	public function bb_rl_activity_new_update_action( $action, $activity ) {
		return bp_core_get_userlink( $activity->user_id );
	}
}
