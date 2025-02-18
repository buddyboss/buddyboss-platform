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
		add_filter( 'bp_nouveau_get_activity_comment_buttons', array( $this, 'bb_rl_get_activity_comment_buttons' ), 10, 3 );
		add_filter( 'bb_get_activity_reaction_button_html', array( $this, 'bb_rl_modify_reaction_button_html' ), 10, 2 );
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
					'item_type' => $item_type,
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
		if ( empty( $activity ) ) {
			return $action;
		}
		switch ( $activity->component ) {
			case 'activity':
				if ( bp_activity_do_mentions() && $usernames = bp_activity_find_mentions( $activity->content ) ) {
					$mentioned_users        = array_filter( array_map( 'bp_get_user_by_nickname', $usernames ) );
					$mentioned_users_link   = [];
					$mentioned_users_avatar = [];
					foreach ( $mentioned_users as $mentioned_user ) {
						$mentioned_users_link[]   = bp_core_get_userlink( $mentioned_user->ID );
						$mentioned_users_avatar[] = bp_core_fetch_avatar(
							array(
								'item_id' => $mentioned_user->ID,
								'type'    => 'thumb',
							)
						);
					}

					// Get the last user link
					$last_user_link = array_pop( $mentioned_users_link );

					$action = sprintf(
						__( '%1$s <span class="activity-to">to</span> %2$s%3$s%4$s%5$s', 'buddyboss' ),
						bp_core_get_userlink( $activity->user_id ),
						$mentioned_users_avatar ? implode( ', ', $mentioned_users_avatar ) : '',
						$mentioned_users_link ? implode( ', ', $mentioned_users_link ) : '',
						$mentioned_users_link ? __( ' and ', 'buddyboss' ) : '',
						$last_user_link
					);
				} else {
					$action = bp_core_get_userlink( $activity->user_id );
				}
				break;
			default:
				$action = bp_core_get_userlink( $activity->user_id );
				break;
		}

		return $action;
	}

	/**
	 * Get activity comment buttons for ReadyLaunch.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $buttons             The activity comment buttons.
	 * @param int   $activity_comment_id The activity comment ID.
	 * @param int   $activity_id         The activity ID.
	 *
	 * @return array
	 */
	public function bb_rl_get_activity_comment_buttons( $buttons, $activity_comment_id, $activity_id ) {
		if ( isset( $buttons['activity_comment_favorite'] ) ) {
			if ( ! bb_get_activity_comment_is_favorite() ) {
				$button_settings                                   = bb_get_reaction_button_settings();
				$buttons['activity_comment_favorite']['link_text'] = sprintf(
					'<span class="bp-screen-reader-text">%1$s</span>
				<i class="bb-icon-%2$s"></i>
				<span class="like-count">%1$s</span>',
					! empty( $button_settings['text'] ) ? esc_html( $button_settings['text'] ) : __( 'Like', 'buddyboss' ),
					esc_attr( $button_settings['icon'] )
				);
			} else {
				// Get user reacted reaction data and prepare the link.
				$reaction_data = bb_activity_get_user_reaction_by_item( $activity_comment_id, 'activity_comment' );
				if ( ! empty( $reaction_data ) ) {
					$prepared_icon                                     = bb_activity_get_reaction_button( $reaction_data['id'], true );
					$buttons['activity_comment_favorite']['link_text'] = sprintf(
						'<span class="bp-screen-reader-text">%1$s</span>
							%2$s
						<span class="like-count reactions_item" style="%3$s">%1$s</span>',
						esc_html( $prepared_icon['icon_text'] ),
						$prepared_icon['icon_html'],
						! empty( $reaction_data['text_color'] ) ? esc_attr( 'color:' . $reaction_data['text_color'] ) : ''
					);
				}
			}
		}

		return $buttons;
	}

	/**
	 * Modify the reaction button HTML.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $button_html The default button HTML
	 * @param array  $args        Button arguments
	 *
	 * @return string Modified button HTML
	 */
	public function bb_rl_modify_reaction_button_html( $button_html, $args ) {
		if ( empty( $args['reaction_id'] ) ) {
			return $button_html;
		}

		$prepared_icon = bb_activity_get_reaction_button( $args['reaction_id'], $args['has_reacted'] );

		// Return your custom HTML structure
		return sprintf(
			'<a href="%1$s" class="button bp-like-button bp-secondary-action %5$s" aria-pressed="false" data-reacted-id="%6$s">
				<span class="bp-screen-reader-text">%2$s</span>
				%3$s
				<span class="like-count reactions_item" style="%4$s">%2$s</span>
			</a>',
			$args['reaction_link'],
			$args['icon_text'],
			$prepared_icon['icon_html'],
			$args['text_color'],
			$args['reaction_button_class'],
			$args['reaction_id']
		);
	}
}
