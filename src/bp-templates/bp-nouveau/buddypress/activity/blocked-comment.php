<?php
/**
 * The template for activity feed blocked comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/blocked-comment.php.
 *
 * @since   BuddyBoss 1.5.6
 * @version 1.5.6
 */

global $activities_template;
$is_user_blocked      = false;
$is_user_suspended    = false;
$is_user_blocked_by   = false;
$check_hidden_content = false;
if ( bp_is_active( 'moderation' ) ) {
	$is_user_suspended    = function_exists( 'bp_moderation_is_user_suspended' ) && bp_moderation_is_user_suspended( bp_get_activity_comment_user_id() );
	$is_user_blocked      = function_exists( 'bp_moderation_is_user_blocked' ) && bp_moderation_is_user_blocked( bp_get_activity_comment_user_id() );
	$is_user_blocked_by   = function_exists( 'bb_moderation_is_user_blocked_by' ) && bb_moderation_is_user_blocked_by( bp_get_activity_comment_user_id() );
	$check_hidden_content = BP_Core_Suspend::check_hidden_content( bp_get_activity_comment_id(), BP_Moderation_Activity_Comment::$moderation_type );
}
?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="<?php bp_activity_comment_css_class(); ?> <?php echo $check_hidden_content ?  'suspended-comment-item' : '' ?>"
	data-bp-activity-comment-id="<?php bp_activity_comment_id(); ?>">

	<?php
	if ( bb_is_group_activity_comment( bp_get_activity_comment_id() ) && ! $check_hidden_content ) {
		bb_nouveau_activity_comment_bubble_buttons();
	}
	?>

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
		$activity_comment_content = bp_get_activity_comment_content();
		$hide_media               = false;
		if ( $check_hidden_content ) {
			$activity_comment_content = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
			$hide_media               = true;
		} elseif ( $is_user_suspended ) {
			$activity_suspend_comment_content = bb_moderation_is_suspended_message( $activity_comment_content, BP_Moderation_Activity_Comment::$moderation_type, bp_get_activity_comment_user_id() );
			if ( $activity_comment_content !== $activity_suspend_comment_content ) {
				$activity_comment_content = $activity_suspend_comment_content;
				$hide_media               = true;
			}
		} elseif ( $is_user_blocked_by ) {
			$activity_is_blocked_comment_content = bb_moderation_is_blocked_message( $activity_comment_content, BP_Moderation_Activity_Comment::$moderation_type, bp_get_activity_comment_user_id() );
			if ( $activity_comment_content !== $activity_is_blocked_comment_content ) {
				$activity_comment_content = $activity_is_blocked_comment_content;
				$hide_media               = true;
			}
		} elseif ( $is_user_blocked ) {
			$activity_has_blocked_comment_content = bb_moderation_has_blocked_message( $activity_comment_content, BP_Moderation_Activity_Comment::$moderation_type, bp_get_activity_comment_user_id() );
			if ( $activity_comment_content !== $activity_has_blocked_comment_content ) {
				$activity_comment_content = $activity_has_blocked_comment_content;
				$hide_media               = true;
			}
		}

		echo $activity_comment_content; // phpcs:ignore

		if ( true === $hide_media && bp_is_active( 'media' ) ) {
			remove_action( 'bp_activity_after_comment_content', 'bp_media_activity_comment_entry' );
			remove_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
			remove_action( 'bp_activity_after_comment_content', 'bp_video_activity_comment_entry' );
			remove_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
		}
		do_action( 'bp_activity_after_comment_content', bp_get_activity_comment_id() );
		if ( true === $hide_media && bp_is_active( 'media' ) ) {
			add_action( 'bp_activity_after_comment_content', 'bp_media_activity_comment_entry' );
			add_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
			add_action( 'bp_activity_after_comment_content', 'bp_video_activity_comment_entry' );
			add_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
		}
		?>

	</div>

	<?php
	if ( bb_is_group_activity_comment( bp_get_activity_comment_id() ) && ! $check_hidden_content ) {
		bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) );
	}
	?>

	<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
</li>
