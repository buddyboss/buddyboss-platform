<?php
/**
 * ReadyLaunch - The template for activity feed blocked comment.
 *
 * This template is used by bboss_activity_comments() functions to show
 * each activity.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

global $activities_template;

$activity_comment_id      = bboss_get_activity_comment_id();
$activity_comment_user_id = bboss_get_activity_comment_user_id();

$is_user_blocked      = false;
$is_user_suspended    = false;
$is_user_blocked_by   = false;
$check_hidden_content = false;
if ( bboss_is_active( 'moderation' ) ) {
	$is_user_suspended    = function_exists( 'bboss_moderation_is_user_suspended' ) && bboss_moderation_is_user_suspended( $activity_comment_user_id );
	$is_user_blocked      = function_exists( 'bboss_moderation_is_user_blocked' ) && bboss_moderation_is_user_blocked( $activity_comment_user_id );
	$is_user_blocked_by   = function_exists( 'bboss_moderation_is_user_blocked_by' ) && bboss_moderation_is_user_blocked_by( $activity_comment_user_id );
	$check_hidden_content = BBoss_Core_Suspend::check_hidden_content( $activity_comment_id, BBoss_Moderation_Activity_Comment::$moderation_type );
}
$media_active = bboss_is_active( 'media' );
?>

<li id="acomment-<?php echo esc_attr( $activity_comment_id ); ?>" class="<?php bboss_activity_comment_css_class(); ?> <?php echo $check_hidden_content ? 'suspended-comment-item' : ''; ?>"
	data-bp-activity-comment-id="<?php echo esc_attr( $activity_comment_id ); ?>" data-bp-timestamp="<?php bboss_nouveau_activity_comment_timestamp(); ?>">

	<?php
	if ( bboss_is_group_activity_comment( $activity_comment_id ) && ! $check_hidden_content ) {
		bboss_nouveau_activity_comment_bubble_buttons();
	}
	?>

	<div class="bb-rl-acomment-display">
		<div class="bb-rl-acomment_inner">
			<div class="bb-rl-acomment-avatar bb-rl-item-avatar">
				<a href="<?php echo esc_url( bboss_get_activity_comment_user_link() ); ?>">
					<?php
					bboss_activity_avatar(
						array(
							'type'    => 'thumb',
							'user_id' => $activity_comment_user_id,
						)
					);
					?>
				</a>
			</div>

			<div class="bb-rl-acomment-content_wrap">
				<div class="bb-rl-acomment-content_block">
					<div class="bb-rl-acomment-meta">
						<?php bboss_nouveau_activity_comment_action(); ?>
					</div>

					<div class="bb-rl-acomment-content">
						<?php
						$activity_comment_content = bboss_get_activity_comment_content();
						$hide_media               = false;
						if ( $check_hidden_content ) {
							$activity_comment_content = esc_html__( 'This content has been hidden from site admin.', 'buddyboss' );
							$hide_media               = true;
						} elseif ( $is_user_suspended ) {
							$activity_suspend_comment_content = bboss_moderation_is_suspended_message( $activity_comment_content, BBoss_Moderation_Activity_Comment::$moderation_type, $activity_comment_user_id );
							if ( $activity_comment_content !== $activity_suspend_comment_content ) {
								$activity_comment_content = $activity_suspend_comment_content;
								$hide_media               = true;
							}
						} elseif ( $is_user_blocked_by ) {
							$activity_is_blocked_comment_content = bboss_moderation_is_blocked_message( $activity_comment_content, BBoss_Moderation_Activity_Comment::$moderation_type, $activity_comment_user_id );
							if ( $activity_comment_content !== $activity_is_blocked_comment_content ) {
								$activity_comment_content = $activity_is_blocked_comment_content;
								$hide_media               = true;
							}
						} elseif ( $is_user_blocked ) {
							$activity_has_blocked_comment_content = bboss_moderation_has_blocked_message( $activity_comment_content, BBoss_Moderation_Activity_Comment::$moderation_type, $activity_comment_user_id );
							if ( $activity_comment_content !== $activity_has_blocked_comment_content ) {
								$activity_comment_content = $activity_has_blocked_comment_content;
								$hide_media               = true;
							}
						}

						echo $activity_comment_content; // phpcs:ignore

						if ( true === $hide_media && $media_active ) {
							remove_action( 'bp_activity_after_comment_content', 'bboss_media_activity_comment_entry' );
							remove_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
							if ( bboss_is_active( 'video' ) ) {
								remove_action( 'bp_activity_after_comment_content', 'bp_video_activity_comment_entry' );
							}
							if ( bboss_is_active( 'document' ) ) {
								remove_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
							}
						}
						do_action( 'bp_activity_after_comment_content', $activity_comment_id );
						if ( true === $hide_media && $media_active ) {
							add_action( 'bp_activity_after_comment_content', 'bboss_media_activity_comment_entry' );
							// The GIF feature ships in the BuddyBoss Add-ons plugin; only re-add when it is loaded.
							if ( function_exists( 'bp_media_comment_embed_gif' ) ) {
								add_action( 'bp_activity_after_comment_content', 'bp_media_comment_embed_gif', 20, 1 );
							}
							if ( bboss_is_active( 'video' ) ) {
								add_action( 'bp_activity_after_comment_content', 'bp_video_activity_comment_entry' );
							}
							if ( bboss_is_active( 'document' ) ) {
								add_action( 'bp_activity_after_comment_content', 'bp_document_activity_comment_entry' );
							}
						}
						?>

					</div>
				</div>
				<div class="bb-rl-acomment-foot-actions">
					<?php
					if ( bboss_is_group_activity_comment( $activity_comment_id ) && ! $check_hidden_content ) {
						bboss_nouveau_activity_comment_buttons( array( 'container' => 'div' ) );
					}
					?>
				</div>
			</div>

		</div>
	</div>

	<?php
	$args = array(
		'limit_comments'     => isset( $args['limit_comments'] ) && true === $args['limit_comments'],
		'comment_load_limit' => isset( $args['show_replies'] ) && false === $args['show_replies'] ? 0 : bboss_get_activity_comment_loading(),
	);
	bboss_nouveau_activity_recurse_comments( bboss_activity_current_comment(), $args );
	?>
</li>
