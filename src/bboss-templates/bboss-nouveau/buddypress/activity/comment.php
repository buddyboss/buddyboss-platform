<?php
/**
 * The template for activity feed comment
 *
 * This template is used by bboss_activity_comments() functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/comment.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_activity_hook( 'before', 'comment_entry' );

$activity_comment_id = bboss_get_activity_comment_id();
?>

<li id="acomment-<?php echo esc_attr( $activity_comment_id ); ?>" class="<?php bboss_activity_comment_css_class(); ?>" data-bp-activity-comment-id="<?php echo esc_attr( $activity_comment_id ); ?>" data-bp-timestamp="<?php bboss_nouveau_activity_comment_timestamp(); ?>" data-bp-activity-comment="<?php bboss_nouveau_edit_activity_comment_data(); ?>">

	<div id="acomment-display-<?php echo esc_attr( $activity_comment_id ); ?>" class="acomment-display">

		<?php bboss_nouveau_activity_comment_bubble_buttons(); ?>

		<div class="acomment_inner">
			<div class="acomment-avatar item-avatar">
				<?php $user_id = bboss_get_activity_comment_user_id(); ?>
				<a href="<?php echo esc_url( bboss_get_activity_comment_user_link() ); ?>"<?php echo bboss_get_hover_card_profile_attr( $user_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in the helper. ?>>
					<?php
					bboss_activity_avatar(
						array(
							'type'    => 'thumb',
							'user_id' => $user_id,
							'class'   => 'avatar bb-hp-profile-avatar',
						)
					);
					?>
				</a>
			</div>

			<div class="acomment-content_wrap">

				<div class="acomment-content_block">
					<div class="acomment-meta">
						<?php bboss_nouveau_activity_comment_action(); ?>
					</div>

					<div class="acomment-content">
						<?php
						bboss_activity_comment_content();
						do_action( 'bp_activity_after_comment_content', $activity_comment_id );
						?>
					</div>
					<div class="comment-reactions">
						<?php
						if ( bboss_is_reaction_activity_comments_enabled() && function_exists( 'bboss_get_activity_post_user_reactions_html' ) ) {
							echo wp_kses_post( bboss_get_activity_post_user_reactions_html( $activity_comment_id, 'activity_comment' ) );
						}
						?>
					</div>
				</div>
				<div class="acomment-foot-actions">
					<?php bboss_nouveau_activity_comment_buttons( array( 'container' => 'div' ) ); ?>
					<?php bboss_nouveau_activity_comment_meta(); ?>
				</div>
			</div>
		</div>
	</div>
	<div id="acomment-edit-form-<?php echo esc_attr( $activity_comment_id ); ?>" class="acomment-edit-form"></div>

	<?php
	$args = array(
		'limit_comments'     => isset( $args['limit_comments'] ) && true === $args['limit_comments'],
		'comment_load_limit' => isset( $args['show_replies'] ) && false === $args['show_replies'] ? 0 : bboss_get_activity_comment_loading(),
	);
	bboss_nouveau_activity_recurse_comments( bboss_activity_current_comment(), $args );
	?>
</li>
<?php
bboss_nouveau_activity_hook( 'after', 'comment_entry' );
