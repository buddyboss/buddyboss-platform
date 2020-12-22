<?php
/**
 * BuddyBoss - Activity Feed Blocked Comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * @version BuddyBoss 2.0.0
 */
$is_user_blocked = $is_user_suspended = false;
if ( bp_is_active( 'moderation' ) ) {
	$is_user_suspended = bp_moderation_is_user_suspended( bp_get_activity_comment_user_id() );
	$is_user_blocked   = bp_moderation_is_user_blocked( bp_get_activity_comment_user_id() );
}
?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="<?php bp_activity_comment_css_class() ?> suspended-comment-item"
	data-bp-activity-comment-id="<?php bp_activity_comment_id(); ?>">

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

		<?php if ( $is_user_suspended ) {
			esc_html_e( 'Hidden content from suspended member.', 'buddyboss' );
		} else if ( $is_user_blocked ) {
			esc_html_e( 'Hidden content from blocked member.', 'buddyboss' );
		} else {
			esc_html_e( 'Hidden content from site admin.', 'buddyboss' );
		} ?>

	</div>
	

	

	<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
</li>