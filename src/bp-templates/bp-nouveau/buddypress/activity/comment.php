<?php
/**
 * The template for activity feed comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/comment.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

bp_nouveau_activity_hook( 'before', 'comment_entry' );

$activity_comment_id = bp_get_activity_comment_id();
?>

<li id="acomment-<?php echo esc_attr( $activity_comment_id ); ?>" class="<?php bp_activity_comment_css_class(); ?>" data-bp-activity-comment-id="<?php echo esc_attr( $activity_comment_id ); ?>" data-bp-timestamp="<?php bb_nouveau_activity_comment_timestamp(); ?>" data-bp-activity-comment="<?php bb_nouveau_edit_activity_comment_data(); ?>">

	<div id="acomment-display-<?php echo esc_attr( $activity_comment_id ); ?>" class="acomment-display">

		<?php bb_nouveau_activity_comment_bubble_buttons(); ?>

		<div class="acomment_inner">
			<div class="acomment-avatar item-avatar">
				<a href="<?php echo esc_url( bp_get_activity_comment_user_link() ); ?>">
					<?php
					bp_activity_avatar(
						array(
							'type'    => 'thumb',
							'user_id' => bp_get_activity_comment_user_id(),
						)
					);
					?>
				</a>
			</div>

			<div class="acomment-content_wrap">

				<div class="acomment-content_block">
					<div class="acomment-meta">
						<?php bp_nouveau_activity_comment_action(); ?>
					</div>

					<div class="acomment-content">
						<?php
						bp_activity_comment_content();
						do_action( 'bp_activity_after_comment_content', $activity_comment_id );
						?>
					</div>
					<div class="comment-reactions">
						<?php
						if ( bb_is_reaction_activity_comments_enabled() ) {
							echo bb_get_activity_post_user_reactions_html( $activity_comment_id, 'activity_comment' );
						}
						?>
					</div>
				</div>
				<div class="acomment-foot-actions">
					<?php bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) ); ?>
					<?php bp_nouveau_activity_comment_meta(); ?>
				</div>
			</div>
		</div>
	</div>
	<div id="acomment-edit-form-<?php echo esc_attr( $activity_comment_id ); ?>" class="acomment-edit-form"></div>

	<?php
	$args = array(
		'limit_comments'     => isset( $args['limit_comments'] ) && true === $args['limit_comments'],
		'comment_load_limit' => isset( $args['show_replies'] ) && false === $args['show_replies'] ? 0 : bb_get_activity_comment_loading(),
	);
	bp_nouveau_activity_recurse_comments( bp_activity_current_comment(), $args );
	?>
</li>
<?php
bp_nouveau_activity_hook( 'after', 'comment_entry' );
