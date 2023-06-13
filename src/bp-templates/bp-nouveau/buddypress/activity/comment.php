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
?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="<?php bp_activity_comment_css_class(); ?>" data-bp-activity-comment-id="<?php bp_activity_comment_id(); ?>">

	<?php bb_nouveau_activity_comment_bubble_buttons(); ?>

	<div class="acomment-avatar item-avatar">
		<a href="<?php bp_activity_comment_user_link(); ?>">
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

	<div class="acomment-meta">
		<?php bp_nouveau_activity_comment_action(); ?>
	</div>

	<div class="acomment-content">
		<?php
		bp_activity_comment_content();
		do_action( 'bp_activity_after_comment_content', bp_get_activity_comment_id() );
		?>
	</div>

	<?php
	bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) );
	bp_nouveau_activity_recurse_comments( bp_activity_current_comment() );
	?>
</li>
<?php
bp_nouveau_activity_hook( 'after', 'comment_entry' );
