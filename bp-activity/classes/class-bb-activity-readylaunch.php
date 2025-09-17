<?php
/**
 * BuddyBoss Activity Readylaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Activity\Classes
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyBoss Activity ReadyLaunch Class.
 *
 * @since BuddyBoss 2.9.00
 */
class BB_Activity_Readylaunch {

	/**
	 * The single instance of the class.
	 *
	 * @since  BuddyBoss 2.9.00
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor method.
	 *
	 * @since BuddyBoss 2.9.00
	 */
	public function __construct() {
		add_filter( 'bb_get_activity_post_user_reactions_html', array( $this, 'bb_rl_get_activity_post_user_reactions_html' ), 10, 4 );
		add_filter( 'bp_nouveau_get_activity_comment_buttons', array( $this, 'bb_rl_get_activity_comment_buttons' ), 10, 2 );
		add_filter( 'bb_get_activity_reaction_button_html', array( $this, 'bb_rl_modify_reaction_button_html' ), 10, 2 );
		add_filter( 'bp_get_activity_css_class', array( $this, 'bb_rl_add_empty_content_class' ), 10, 1 );

		add_action( 'wp_ajax_bb_rl_activity_loadmore_comments', array( $this, 'bb_rl_activity_loadmore_comments' ) );
		add_action( 'wp_ajax_nopriv_bb_rl_activity_loadmore_comments', array( $this, 'bb_rl_activity_loadmore_comments' ) );
		add_filter( 'bb_ajax_activity_sync_from_modal_args', array( $this, 'bb_rl_activity_sync_from_modal_args' ) );

		add_filter( 'bp_core_get_js_strings', array( $this, 'bb_rl_activity_localize_scripts' ), 11 );
		add_filter( 'bb_document_get_image_sizes', array( $this, 'bb_rl_modify_document_image_sizes' ), 20 );
		add_filter( 'bb_media_get_activity_max_thumb_length', array( $this, 'bb_rl_modify_activity_max_thumb_length' ) );
		add_filter( 'bb_video_get_activity_max_thumb_length', array( $this, 'bb_rl_modify_activity_max_thumb_length' ) );
		add_filter( 'bb_activity_get_reacted_users_data', array( $this, 'bb_rl_modify_user_data_to_reactions' ), 10, 1 );
		add_filter( 'bb_get_activity_comment_threading_depth', array( $this, 'bb_rl_modify_activity_comment_threading_depth' ), 10 );
		add_filter( 'bp_nouveau_get_submit_button', array( $this, 'bb_rl_modify_submit_button' ), 10 );
		add_filter( 'bp_get_activity_content_body', array( $this, 'bb_rl_activity_content_with_changed_avatar' ), 9999, 2 );

		// Remove post content.
		remove_action( 'bp_before_directory_activity', 'bp_activity_directory_page_content' );

		add_filter( 'bp_nouveau_media_description_response_data', array( $this, 'bb_rl_modify_media_description_response_data' ), 10 );
		add_filter( 'bp_nouveau_document_description_response_data', array( $this, 'bb_rl_modify_media_description_response_data' ), 10 );
		add_filter( 'bp_nouveau_video_activity_response_data', array( $this, 'bb_rl_modify_media_description_response_data' ), 10 );
		add_filter( 'bp_nouveau_video_description_response_data', array( $this, 'bb_rl_modify_media_description_response_data' ), 10 );

		add_filter( 'bp_nouveau_activity_widget_query', array( $this, 'bb_rl_modify_activity_widget_query' ), 10 );

		add_filter( 'bp_get_add_follow_button', array( $this, 'bb_rl_modify_add_follow_button' ) );

		add_filter( 'bb_nouveau_get_activity_entry_bubble_buttons', array( $this, 'bb_rl_modify_activity_entry_bubble_buttons' ) );

		add_filter( 'bp_nouveau_object_template_result', array( $this, 'bb_rl_modify_object_template_result' ), 10, 2 );

		add_filter( 'bb_get_close_activity_comments_notice', array( $this, 'bb_rl_modify_close_activity_comments_notice' ) );
		add_filter( 'bb_ajax_activity_update_close_comments', array( $this, 'bb_rl_modify_close_activity_comments_notice' ) );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @since BuddyBoss 2.9.00
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

	/**
	 * Get activity post user reactions HTML.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $output       The output.
	 * @param int    $activity_id  The activity ID.
	 * @param string $item_type    The item type.
	 * @param bool   $is_popup     Whether the popup is enabled.
	 *
	 * @return string The activity post user reactions HTML.
	 */
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
				$reaction_text = 1 === $reaction_count ?
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

		// Build a popup to show reacted items.
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
	 * Get activity comment buttons for ReadyLaunch.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $buttons             The activity comment buttons.
	 * @param int   $activity_comment_id The activity comment ID.
	 *
	 * @return array
	 */
	public function bb_rl_get_activity_comment_buttons( $buttons, $activity_comment_id ) {
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
				// Get user reaction data and prepare the link.
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
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $button_html The default button HTML.
	 * @param array  $args        Button arguments.
	 *
	 * @return string Modified button HTML
	 */
	public function bb_rl_modify_reaction_button_html( $button_html, $args ) {
		if ( empty( $args['reaction_id'] ) ) {
			return $button_html;
		}

		$prepared_icon = bb_activity_get_reaction_button( $args['reaction_id'], $args['has_reacted'] );

		// Return your custom HTML structure.
		return sprintf(
			'<a href="%1$s" class="button bp-like-button bp-secondary-action %5$s" data-pressed="false" data-reacted-id="%6$s">
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
	 * Load more comments for the activity.
	 *
	 * @since BuddyBoss 2.9.00
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
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'bp_nouveau_activity' ) ) { // phpcs:ignore
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

		// Bail if activity privacy is restricted.
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
				'last_comment_timestamp' => ! empty( $_POST['last_comment_timestamp'] ) ? sanitize_text_field( wp_unslash( $_POST['last_comment_timestamp'] ) ) : '',
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
		// We have all comments and replies, just loop through.
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
	 * @since BuddyBoss 2.9.00
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
				echo bb_get_activity_post_user_reactions_html( $activity_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
						/* translators: %d: activity comment count */
						echo esc_html( sprintf( _x( '%d Comments', 'placeholder: activity comments count', 'buddyboss' ), $comment_count ) );
					} else {
						/* translators: %d: activity comment count */
						echo esc_html( sprintf( _x( '%d Comment', 'placeholder: activity comment count', 'buddyboss' ), $comment_count ) );
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
	 * @since BuddyBoss 2.9.00
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
	 * @since BuddyBoss 2.9.00
	 *
	 * @param int $activity_id The activity ID.
	 *
	 * @return int
	 */
	public function bb_rl_get_activity_comment_count( $activity_id ) {
		global $wpdb, $bp;

		// phpcs:ignore
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bp->activity->table_name} WHERE type = 'activity_comment' AND is_spam = 0 AND item_id = %d", $activity_id ) );
	}

	/**
	 * Localise the strings needed for the ReadyLaunch activity.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $params Associative array containing the JS strings needed by scripts.
	 *
	 * @return array The same array with specific strings for the ReadyLaunch activity if needed.
	 */
	public function bb_rl_activity_localize_scripts( $params ) {
		if ( ! isset( $params['activity']['strings'] ) ) {
			return $params;
		}
		$reply_strings = array(
			/* Translators: %d: reply count */
			'replyLabel'         => __( '%d Reply', 'buddyboss' ),
			/* Translators: %d: reply count */
			'repliesLabel'       => __( '%d Replies', 'buddyboss' ),
			'video_default_url'  => ( function_exists( 'bb_get_video_default_placeholder_image' ) && ! empty( bb_get_video_default_placeholder_image() ) ? bb_get_video_default_placeholder_image() : '' ),
			'replyButtonText'    => __( 'Reply', 'buddyboss' ),
			'commentButtonText'  => __( 'Comment', 'buddyboss' ),
			'replyPlaceholder'   => __( 'Write a reply...', 'buddyboss' ),
			'commentPlaceholder' => __( 'Write a comment...', 'buddyboss' ),
		);

		$params['activity']['strings'] = array_merge( $params['activity']['strings'], $reply_strings );

		return $params;
	}

	/**
	 * Modify document image sizes.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $sizes The image sizes.
	 *
	 * @return array
	 */
	public function bb_rl_modify_document_image_sizes( $sizes ) {
		if ( isset( $sizes['bb-document-image-preview-activity-image'] ) ) {
			$sizes['bb-document-image-preview-activity-image'] = array(
				'width'  => 700,
				'height' => 'auto',
			);
		}

		return $sizes;
	}

	/**
	 * Modify activity max thumb length.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return int
	 */
	public function bb_rl_modify_activity_max_thumb_length() {
		return 4;
	}

	/**
	 * Add additional user data to reactions.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $reaction_data The reaction data array.
	 *
	 * @return array Modified reaction data.
	 */
	public function bb_rl_modify_user_data_to_reactions( $reaction_data ) {
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

	/**
	 * Modify activity comment threading depth.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @return bool
	 */
	public function bb_rl_modify_activity_comment_threading_depth() {
		return 2;
	}

	/**
	 * Modify submit button.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $button The submit button array.
	 *
	 * @return array Modified submit button array.
	 */
	public function bb_rl_modify_submit_button( $button ) {
		if ( isset( $button['activity-new-comment'] ) ) {
			$button['activity-new-comment']['attributes']['value'] = esc_html__( 'Comment', 'buddyboss' );
		}
		return $button;
	}

	/**
	 * Modify activity content with changed avatar.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $content The original content.
	 * @param object $activity The activity object.
	 *
	 * @return string Modified content.
	 */
	public function bb_rl_activity_content_with_changed_avatar( $content, $activity ) {
		if ( 'profile' === $activity->component && 'new_avatar' === $activity->type ) {
			$full_avatar = bp_core_fetch_avatar(
				array(
					'item_id' => $activity->user_id,
					'object'  => 'user',
					'type'    => 'full',
					'html'    => true,
				)
			);

			$content = '<div class="bb-rl-activity-content-avatar bb-rl-item-content-avatar">' . $full_avatar . '</div>';
		}

		return $content;
	}

	/**
	 * Add a class to activities with empty content.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param string $activity_class The CSS classes for the activity item.
	 *
	 * @return string Modified CSS classes.
	 */
	public function bb_rl_add_empty_content_class( $activity_class ) {
		global $activities_template;

		// Add a specific class for activities with empty content.
		if ( isset( $activities_template->activity ) && empty( $activities_template->activity->content ) ) {
			$activity_class .= ' bb-rl-empty-content';
		}

		return $activity_class;
	}

	/**
	 * Modify activity new update action.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $args The arguments.
	 *
	 * @return string Modified action.
	 */
	public function bb_rl_activity_new_update_action( $args ) {
		$activity        = ! empty( $args['activity'] ) ? $args['activity'] : null;
		$activity_action = ! empty( $args['activity_action'] ) ? $args['activity_action'] : '';

		if ( empty( $activity ) || empty( $activity_action ) ) {
			return $activity_action;
		}

		$user_link_with_html = bp_core_get_userlink( $activity->user_id );

		if (
			in_array( $activity->component, array( 'groups', 'bbpress' ), true ) &&
			in_array( $activity->type, array( 'bbp_topic_create', 'bbp_reply_create' ), true )
		) {
			if ( 'bbp_topic_create' === $activity->type ) {
				/* Translators: %s: user link */
				$activity_action = sprintf( __( '%s started the discussion', 'buddyboss' ), $user_link_with_html );
			} elseif ( 'bbp_reply_create' === $activity->type ) {
				/* Translators: %s: user link */
				$activity_action = sprintf( __( '%s replied to the discussion', 'buddyboss' ), $user_link_with_html );
			}
			$activity_action = '<p>' . $activity_action . '</p>';
		} elseif ( 'groups' === $activity->component && bp_is_active( 'groups' ) ) {
			if (
				'joined_group' === $activity->type ||
				'group_details_updated' === $activity->type ||
				'created_group' === $activity->type ||
				'zoom_meeting_create' === $activity->type ||
				'zoom_meeting_notify' === $activity->type
			) {
				$group = ! empty( $args['group'] ) ? $args['group'] : null;
				if ( empty( $group ) ) {
					$group = groups_get_group( $activity->item_id );
				}

				$group_link       = '<a href="' . esc_url( bp_get_group_permalink( $group ) ) . '">' . esc_html( $group->name ) . '</a>';
				$secondary_avatar = bp_get_activity_secondary_avatar();

				// Remove the group link and secondary avatar.
				$activity_action = str_replace( $group_link, '', $activity_action );
				$activity_action = str_replace( $secondary_avatar, '', $activity_action );

				// Remove any remaining group links that might have different attributes.
				$group_url       = preg_quote( esc_url( bp_get_group_permalink( $group ) ), '/' );
				$activity_action = preg_replace( '/<a\s+href="' . $group_url . '"[^>]*>.*?<\/a>/i', '', $activity_action );

				if (
					'zoom_meeting_create' === $activity->type ||
					'zoom_meeting_notify' === $activity->type
				) {
					// Remove everything after the second link (Zoom meeting link).
					$activity_action = preg_replace(
						'/^(.*?<a[^>]*>.*?<\/a>.*?<a[^>]*>.*?<\/a>).*$/is',
						'$1',
						$activity_action
					);
				} elseif ( 'group_details_updated' === $activity->type ) {
					$user_link = bp_core_get_userlink( $activity->user_id );
					/* Translators: %s: user link */
					$activity_action = sprintf( __( '%s updated group details', 'buddyboss' ), $user_link );
					$activity_action = '<p>' . $activity_action . '</p>';
				}
			} elseif ( 'activity_update' === $activity->type ) {
				$activity_action = '<p>' . $user_link_with_html . '</p>';
			}
		} elseif ( 'activity' === $activity->component && 'activity_update' === $activity->type ) {
			$user_link = bp_core_get_userlink( $activity->user_id );
			$usernames = bp_activity_find_mentions( $activity->content );
			if ( bp_activity_do_mentions() && ! empty( $usernames ) ) {
				$mentioned_users        = array_filter( array_map( 'bp_get_user_by_nickname', $usernames ) );
				$mentioned_users_link   = array();
				$mentioned_users_avatar = array();
				foreach ( $mentioned_users as $mentioned_user ) {
					$mentioned_users_link[]   = bp_core_get_userlink( $mentioned_user->ID );
					$mentioned_users_avatar[] = bp_core_fetch_avatar(
						array(
							'item_id' => $mentioned_user->ID,
							'type'    => 'thumb',
						)
					);
				}

				// Get the last user link.
				$last_user_link = array_pop( $mentioned_users_link );

				$activity_action = sprintf(
					/* translators: %1$s: user link, %2$s: mentioned users avatar, %3$s: mentioned users link, %4$s: mentioned users link and, %5$s: last mentioned user link */
					__( '%1$s <span class="activity-to">to</span> %2$s%3$s%4$s%5$s', 'buddyboss' ),
					$user_link,
					$mentioned_users_avatar ? implode( ', ', $mentioned_users_avatar ) : '',
					$mentioned_users_link ? implode( ', ', $mentioned_users_link ) : '',
					$mentioned_users_link ? __( ' and ', 'buddyboss' ) : '',
					$last_user_link
				);

				$activity_action = '<p>' . $activity_action . '</p>';
			} else {
				$activity_action = '<p>' . $user_link_with_html . '</p>';
			}
		} elseif ( 'friends' === $activity->component && 'friendship_created' === $activity->type ) {
			$friend_link     = bp_core_get_userlink( $activity->secondary_item_id );
			$activity_action = sprintf(
				/* translators: %1$s: user link, %2$s: friend link */
				__( '%1$s & %2$s are now connected', 'buddyboss' ),
				$user_link_with_html,
				$friend_link
			);
			$activity_action = '<p>' . $activity_action . '</p>';
		}

		return $activity_action;
	}

	/**
	 * Modify media description response data.
	 *
	 * @since BuddyBoss 2.9.00
	 *
	 * @param array $response_data The response data.
	 *
	 * @return array Modified response data.
	 */
	public function bb_rl_modify_media_description_response_data( $response_data ) {
		if ( empty( $response_data['activity_id'] ) ) {
			return $response_data;
		}

		$activity_id   = $response_data['activity_id'];
		$comment_count = $this->bb_rl_get_activity_comment_count( $activity_id );

		if ( is_user_logged_in() && 0 !== (int) $comment_count ) {
			// If reset comment is true, then don't add the comment form for video.
			if ( isset( $response_data['reset_comment'] ) && 'true' === $response_data['reset_comment'] ) {
				return $response_data;
			}

			ob_start();
			bp_get_template_part( 'activity/comment-form' );
			$response_data['comment_form'] = ob_get_clean();
		}

		if ( ! empty( $response_data['description'] ) ) {
			$description = $response_data['description'];
			$modified    = false;

			$search_replacements = array();
			$replace_values      = array();

			if (
				false !== strpos( $description, 'id="add-activity-description"' ) &&
				! empty( $response_data['type'] )
			) {
				$add_description_text = esc_attr__( 'Add a description', 'buddyboss' );

				$type_image_text = sprintf(
					/* translators: %s: media type */
					esc_attr__( 'Type %s description', 'buddyboss' ),
					esc_attr( $response_data['type'] )
				);

				$search_replacements[] = 'title="' . $add_description_text . '"';
				$replace_values[]      = 'title="' . $type_image_text . '"';

				$search_replacements[] = 'placeholder="' . $add_description_text . '"';
				$replace_values[]      = 'placeholder="' . $type_image_text . '"';

				$modified = true;
			}

			// Check for submit button element.
			if ( false !== strpos( $description, 'id="bp-activity-description-new-submit"' ) ) {
				$done_editing_text = esc_attr__( 'Done Editing', 'buddyboss' );
				$done_text         = esc_attr__( 'Done', 'buddyboss' );

				$search_replacements[] = 'value="' . $done_editing_text . '"';
				$replace_values[]      = 'value="' . $done_text . '"';

				$modified = true;
			}

			// Perform single str_replace with all replacements.
			if ( $modified && ! empty( $search_replacements ) ) {
				$response_data['description'] = str_replace( $search_replacements, $replace_values, $description );
			}
		}

		return $response_data;
	}

	/**
	 * Modify activity widget query to remove public scope when relevant feed is enabled.
	 *
	 * @since BuddyBoss 2.9.10
	 *
	 * @param array $args The activity widget arguments.
	 * @return array Modified arguments.
	 */
	public function bb_rl_modify_activity_widget_query( $args ) {
		// Check if relevant feed is enabled and scope exists.
		if ( ! bp_is_relevant_feed_enabled() || ! isset( $args['scope'] ) || empty( $args['scope'] ) ) {
			return $args;
		}

		// Check if 'public' is in the scope.
		if ( false === strpos( $args['scope'], 'public' ) ) {
			return $args;
		}

		// Parse the scope into an array.
		$scope_array = explode( ',', $args['scope'] );

		// Remove 'public' from the scope array.
		$key = array_search( 'public', $scope_array, true );
		if ( false !== $key ) {
			unset( $scope_array[ $key ] );
		}

		// Rebuild the scope string, filtering out empty values and reindexing.
		$filtered_scope = array_values( array_filter( $scope_array ) );
		$args['scope']  = ! empty( $filtered_scope ) ? implode( ',', $filtered_scope ) : '';

		return $args;
	}

	/**
	 * Modify the data-balloon attribute for the follow button when hover on follow button.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array|string $button The button array or HTML string.
	 *
	 * @return array|string The modified button array or HTML string.
	 */
	public function bb_rl_modify_add_follow_button( $button ) {
		// Check if $button is an array and has the required keys.
		if ( ! is_array( $button ) || empty( $button['link_href'] ) ) {
			return $button;
		}

		if ( false !== strpos( $button['link_href'], 'stop-following' ) ) {
			$unfollow_text          = __( 'Unfollow', 'buddyboss' );
			$button['data-balloon'] = $unfollow_text;
			if ( empty( $button['is_tooltips'] ) ) {
				$button['link_class']               .= ' bb-rl-primary-hover-action';
				$button['button_attr']['data-hover'] = $unfollow_text;
			}
		}

		return $button;
	}

	/**
	 * Modify the media activity entry bubble buttons.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array $buttons The buttons.
	 *
	 * @return array The modified buttons.
	 */
	public function bb_rl_modify_activity_entry_bubble_buttons( $buttons ) {
		if ( empty( $buttons ) ) {
			return $buttons;
		}

		$replace_text = array();
		if ( ! empty( $buttons['activity_document_download'] ) ) {
			$replace_text['activity_document_download'] = esc_html__( 'Download document', 'buddyboss' );
		}
		if ( ! empty( $buttons['activity_media_download'] ) ) {
			$replace_text['activity_media_download'] = esc_html__( 'Download photo', 'buddyboss' );
		}
		if ( ! empty( $buttons['activity_video_download'] ) ) {
			$replace_text['activity_video_download'] = esc_html__( 'Download video', 'buddyboss' );
		}

		if ( ! empty( $replace_text ) ) {
			foreach ( $replace_text as $button_key => $replacement_text ) {
				$buttons[ $button_key ]['link_text'] = str_replace( esc_html__( 'Download', 'buddyboss' ), $replacement_text, $buttons[ $button_key ]['link_text'] );
			}
		}

		return $buttons;
	}

	/**
	 * Add the scheduled posts count to the object template result.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array  $result          The result.
	 * @param string $template_object The object.
	 *
	 * @return array The modified result.
	 */
	public function bb_rl_modify_object_template_result( $result, $template_object ) {
		if ( 'activity' === $template_object ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$status   = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
			$template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : '';

			if ( 'scheduled' === $status && 'activity_schedule' === $template ) {
				add_filter( 'bp_ajax_querystring', array( $this, 'bb_rl_get_activity_schedule_count_query' ), 20 );
				bp_has_activities( bp_ajax_querystring( 'activity' ) );
				$result['count'] = bp_core_number_format( $GLOBALS['activities_template']->activity_count );
				remove_filter( 'bp_ajax_querystring', array( $this, 'bb_rl_get_activity_schedule_count_query' ), 20 );
			}
		}

		return $result;
	}

	/**
	 * Get the activity schedule count query.
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array $querystring The querystring.
	 *
	 * @return string The modified querystring.
	 */
	public function bb_rl_get_activity_schedule_count_query( $querystring ) {
		$querystring = bp_parse_args( $querystring );

		$querystring['status'] = 'scheduled';

		$querystring['user_id'] = bp_loggedin_user_id();
		if ( bp_is_active( 'groups' ) && bp_is_group() ) {
			$querystring['object'] = 'groups';
		} else {
			$querystring['object'] = 'activity';
		}
		$querystring['scope'] = '';

		return http_build_query( $querystring );
	}

	/**
	 * Modify the close activity comments notice.
	 *
	 * This method handles both:
	 * - bb_get_close_activity_comments_notice filter (for template display)
	 * - bb_ajax_activity_update_close_comments filter (for AJAX responses)
	 *
	 * @since BuddyBoss 2.9.30
	 *
	 * @param array|string $closed_notice The closed notice.
	 *
	 * @return array|string The modified closed notice.
	 */
	public function bb_rl_modify_close_activity_comments_notice( $closed_notice ) {
		if ( empty( $closed_notice ) ) {
			return $closed_notice;
		}

		$custom_notice = esc_html__( 'Comments are closed for this post', 'buddyboss' );

		// Handle array format for AJAX response.
		if ( is_array( $closed_notice ) && isset( $closed_notice['feedback'] ) ) {
			if ( isset( $_POST['close_comments_action'] ) && 'close_comments' === $_POST['close_comments_action'] ) {
				$closed_notice['feedback'] = $custom_notice;
			}
			return $closed_notice;
		}

		return $custom_notice;
	}
}

