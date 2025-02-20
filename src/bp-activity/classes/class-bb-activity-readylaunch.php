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

		add_action( 'wp_ajax_bb_rl_activity_loadmore_comments', array( $this, 'bb_rl_activity_loadmore_comments' ) );
		add_action( 'wp_ajax_nopriv_bb_rl_activity_loadmore_comments', array( $this, 'bb_rl_activity_loadmore_comments' ) );
		add_filter( 'bb_ajax_activity_sync_from_modal_args', array( $this, 'bb_rl_activity_sync_from_modal_args' ) );

		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_activity_localize_scripts' ), 11 );
		add_filter( 'bb_document_get_image_sizes', array( $this, 'bb_rl_modify_document_image_sizes' ), 20 );
		add_filter( 'bb_media_get_activity_max_thumb_length', array( $this, 'bb_rl_modify_activity_max_thumb_length' ) );
		add_filter( 'bb_video_get_activity_max_thumb_length', array( $this, 'bb_rl_modify_activity_max_thumb_length' ) );
		add_filter( 'bb_activity_get_reacted_users_data', array( $this, 'bb_rl_modify_user_data_to_reactions' ), 10, 2 );
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
					'<div class="reactions_item">%s</div>',
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
				$reaction_text = $reaction_count === 1 ?
					esc_html__( 'reaction', 'buddyboss' ) :
					esc_html__( 'reactions', 'buddyboss' );

				$output .= sprintf(
					'<div class="%1$s">%2$s %3$s</div>',
					$reaction_count_class,
					$reaction_count,
					$reaction_text
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

	/**
	 * Load more comments for activity.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_activity_loadmore_comments() {
		if ( ! bp_is_post_request() ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request.', 'buddyboss' ),
				)
			);
		}

		// Nonce check!
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request.', 'buddyboss' ),
				)
			);
		}

		if ( empty( $_POST['activity_id'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Activity id cannot be empty.', 'buddyboss' ),
				)
			);
		}

		if ( empty( $_POST['parent_comment_id'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Parent comment id cannot be empty.', 'buddyboss' ),
				)
			);
		}

		global $activities_template;
		$activity_id       = ! empty( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;
		$parent_comment_id = ! empty( $_POST['parent_comment_id'] ) ? (int) $_POST['parent_comment_id'] : 0;

		$privacy_check = bb_validate_activity_privacy(
			array(
				'activity_id'     => $activity_id,
				'validate_action' => 'view_activity',
			)
		);

		// Bail if activity privacy restrict.
		if ( is_wp_error( $privacy_check ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Sorry, You are not allowed to view more comments.', 'buddyboss' ),
				)
			);
		}

		$activities_template = new stdClass();
		$parent_commment     = new BP_Activity_Activity( $parent_comment_id );
		if ( empty( $parent_commment ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid request.', 'buddyboss' ),
				)
			);
		}

		$last_comment_id = ! empty( $_POST['last_comment_id'] ) ? (int) $_POST['last_comment_id'] : 0;
		$offset          = ! empty( $_POST['offset'] ) ? (int) $_POST['offset'] : 0;
		$comments        = BP_Activity_Activity::append_comments(
			array( $parent_commment ),
			'',
			true,
			array(
				'limit'                  => bb_get_activity_comment_loading(),
				'offset'                 => $offset,
				'last_comment_timestamp' => ! empty( $_POST['last_comment_timestamp'] ) ? sanitize_text_field( $_POST['last_comment_timestamp'] ) : '',
				'last_comment_id'        => $last_comment_id,
				'comment_order_by'       => apply_filters( 'bb_activity_recurse_comments_order_by', 'ASC' ),
			)
		);

		if ( empty( $comments[0] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No more items to load.', 'buddyboss' ),
				)
			);
		}

		$activities_template->activity = $comments[0];
		// We have all comments and replies just loop through.
		ob_start();

		$args = array(
			'limit_comments'     => true,
			'comment_load_limit' => bb_get_activity_comment_loading(),
			'parent_comment_id'  => $parent_comment_id,
			'main_activity_id'   => $activity_id,
			'is_ajax_load_more'  => false,
		);

		// Check if parent is the main activity.
		if ( isset( $activities_template->activity ) ) {
			// No current comment.
			bp_activity_recurse_comments( $activities_template->activity, $args );
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'No more items to load.', 'buddyboss' ),
				)
			);
		}

		$result = array(
			'comments' => ob_get_clean(),
		);

		if ( is_user_logged_in() && empty( $offset ) ) {
			ob_start();
			bp_get_template_part( 'activity/comment-form' );
			$result['comment_form'] = ob_get_clean();
		}
		wp_send_json_success( $result );
	}

	/**
	 * Activity state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_rl_activity_state() {

		$activity_id    = bp_get_activity_id();
		$comment_count  = $this->bb_rl_get_activity_comment_count( $activity_id );
		$reactions      = bb_active_reactions();
		$reaction_count = bb_load_reaction()->bb_get_user_reactions_count(
			array(
				'item_id'     => $activity_id,
				'item_type'   => 'activity',
				'reaction_id' => array_keys( $reactions ),
			)
		);
		?>
		<div class="activity-state <?php echo ! empty( $reaction_count ) ? 'has-likes' : ''; ?> <?php echo $comment_count ? 'has-comments' : ''; ?>">
			<?php
			if ( bb_is_reaction_activity_posts_enabled() ) {
				echo bb_get_activity_post_user_reactions_html( $activity_id );
			}
			?>

			<?php
			if ( bp_activity_can_comment() ) {
				$activity_state_comment_class['activity_state_comment_class'] = 'activity-state-comments';
				if ( $comment_count > 0 ) {
					$activity_state_comment_class['has-comments'] = 'has-comments';
				}
				$activity_state_class = apply_filters( 'bp_nouveau_get_activity_comment_buttons_activity_state', $activity_state_comment_class, $activity_id );
				?>
				<a href="#" class="<?php echo esc_attr( trim( implode( ' ', $activity_state_class ) ) ); ?>">
				<span class="comments-count" data-comments-count="<?php echo esc_attr( $comment_count ); ?>">
					<?php
					if ( $comment_count > 1 || 0 === $comment_count ) {
						printf( _x( '%d Comments', 'placeholder: activity comments count', 'buddyboss' ), $comment_count );
					} else {
						printf( _x( '%d Comment', 'placeholder: activity comment count', 'buddyboss' ), $comment_count );
					}
					?>
				</span>
				</a>
				<?php
			}
			?>
		</div>
		<?php
	}

	/**
	 * Sync activity from modal args.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args The modal arguments.
	 *
	 * @return array
	 */
	public function bb_rl_activity_sync_from_modal_args( $args ) {
		$args['display_comments'] = false;

		return $args;
	}

	/**
	 * Get activity comment count.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $activity_id The activity ID.
	 *
	 * @return int
	 */
	public function bb_rl_get_activity_comment_count( $activity_id ) {
		global $wpdb, $bp;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND item_id = %d",
				$activity_id
			)
		);
	}

	/**
	 * Localize the strings needed for the ReadyLaunch activity.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $params Associative array containing the js strings needed by scripts.
	 *
	 * @return array The same array with specific strings for the ReadyLaunch activity if needed.
	 */
	public function bb_rl_activity_localize_scripts( $params ) {
		if ( ! isset( $params['activity']['strings'] ) ) {
			return $params;
		}
		$reply_strings = array(
			'replyLabel'        => __( '%d Reply', 'buddyboss' ),
			'repliesLabel'      => __( '%d Replies', 'buddyboss' ),
			'video_default_url' => ( function_exists( 'bb_get_video_default_placeholder_image' ) && ! empty( bb_get_video_default_placeholder_image() ) ? bb_get_video_default_placeholder_image() : '' ),
		);

		$params['activity']['strings'] = array_merge( $params['activity']['strings'], $reply_strings );

		return $params;
	}

	/**
	 * Modify document image sizes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $sizes The image sizes.
	 *
	 * @return array
	 */
	public function bb_rl_modify_document_image_sizes( $sizes ) {
		if ( isset( $sizes['bb-document-image-preview-activity-image'] ) ) {
			$sizes['bb-document-image-preview-activity-image'] = array(
				'width'  => 700,
				'height' => 'auto'
			);
		}

		return $sizes;
	}

	/**
	 * Modify activity max thumb length.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int
	 */
	public function bb_rl_modify_activity_max_thumb_length() {
		return 4;
	}

	/**
	 * Add additional user data to reactions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $reaction_data The reaction data array
	 * @param array $args          The arguments passed to bb_activity_get_reacted_users_data
	 *
	 * @return array Modified reaction data
	 */
	public function bb_rl_modify_user_data_to_reactions( $reaction_data, $args ) {
		if ( empty( $reaction_data['reactions'] ) ) {
			return $reaction_data;
		}
		foreach ( $reaction_data['reactions'] as $key => $user_data ) {
			if ( bp_is_active( 'follow' ) ) {
				$followers = bp_get_follower_ids( array( 'user_id' => $user_data['user_id'] ) );
			} elseif ( function_exists( 'bp_get_followers' ) ) {
				$followers = bp_get_followers( array( 'user_id' => $user_data['user_id'] ) );
			}
			$followers_count = 0;
			if ( ! empty( $followers ) ) {
				$followers_count = sprintf(
				/* translators: %d: follower count */
					_n( '%d follower', '%d followers', count( $followers ), 'buddyboss' ),
					count( $followers )
				);
			}
			$reaction_data['reactions'][ $key ]['followers_count'] = $followers_count;
		}

		return $reaction_data;
	}
}
