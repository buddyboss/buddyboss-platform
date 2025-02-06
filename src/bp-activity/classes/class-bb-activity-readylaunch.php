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

	/**
	 * Activity state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function bb_rl_activity_state() {
		$activity_id    = bp_get_activity_id();
		$comment_count  = bp_activity_get_comment_count();
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
				echo self::bb_get_activity_post_user_reactions_html( $activity_id, $reaction_count, 'activity', true );
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
	 * Get activity post user reactions html.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int    $activity_id    Activity ID.
	 * @param int    $reaction_count Reaction count.
	 * @param string $item_type      Item type.
	 * @param bool   $is_popup       Is popup.
	 *
	 * @return string
	 */
	public static function bb_get_activity_post_user_reactions_html( $activity_id, $reaction_count, $item_type = 'activity', $is_popup = true ) {
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

			if ( 0 < $reaction_count ) {
				$output .= sprintf(
					'<div class="%1$s">%2$s</div>',
					esc_attr( $reaction_count_class ),
					esc_html( sprintf( _n( '%s Reaction', '%s Reactions', $reaction_count, 'buddyboss' ), $reaction_count ) )
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

		return apply_filters( 'bb_get_activity_post_user_reactions_html', $output );
	}

	/**
	 * Activity entry bubble buttons.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $args Arguments.
	 */
	public static function bb_rl_activity_entry_bubble_buttons( $args = array() ) {
		$output = join( ' ', bb_nouveau_get_activity_entry_bubble_buttons( $args ) );

		ob_start();

		/**
		 * Fires at the end of the activity entry top meta data area.
		 *
		 * @since BuddyBoss 1.7.2
		 */
		do_action( 'bp_activity_entry_top_meta' );

		$output .= ob_get_clean();

		$has_content = trim( $output, ' ' );
		if ( ! $has_content ) {
			return;
		}

		if ( ! $args ) {
			$args = array( 'container_classes' => array( 'bb-activity-more-options-wrap' ) );
		}

		ob_start();
		bp_get_template_part( 'common/more-options-view' );
		$template_part_content = ob_get_clean();

		$output = sprintf(
			'<span class="bb-activity-more-options-action" data-balloon-pos="up" data-balloon="%1$s">
		<i class="bb-icon-f bb-icon-ellipsis-h"></i>
		</span>
		<div class="bb-activity-more-options bb_more_dropdown">
			%2$s
			%3$s
		</div>
		<div class="bb_more_dropdown_overlay"></div>',
			esc_html__( 'More Options', 'buddyboss' ),
			$template_part_content,
			$output
		);

		bp_nouveau_wrapper( array_merge( $args, array( 'output' => $output ) ) );
	}
}
