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
		add_filter( 'bb_get_activity_post_user_reactions_html' , array( $this, 'bb_rl_get_activity_post_user_reactions_html' ), 10, 4 );
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
				$icon    = bb_activity_prepare_emotion_icon( $reaction['id'] );
				$output .= sprintf(
					'<div class="reactions_item">
				%s
				</div>',
					$icon
				);
			}

			$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
				array(
					'item_id'     => $activity_id,
					'item_type'   => 'activity',
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
				<div class="activity-state-popup_inner" id="reaction-content-' . $activity_id . '">
				</div>
			</div>';
		}

		return $output;
	}
}
