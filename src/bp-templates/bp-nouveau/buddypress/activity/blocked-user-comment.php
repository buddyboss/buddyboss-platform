<?php
/**
 * BuddyBoss - Activity Feed Comment of Suspended/Blocked user
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * @version BuddyBoss 2.0.0
 */

?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="<?php bp_activity_comment_css_class() ?>"
	data-bp-activity-comment-id="<?php bp_activity_comment_id(); ?>">
	<div class="acomment-avatar item-avatar">
		<span>
			<?php
			bp_activity_avatar(
					array(
							'type'    => 'thumb',
							'user_id' => 0,
					)
			);
			?>
		</span>
	</div>

	<div class="acomment-meta">

		<span class="author-name"><?php esc_html_e( 'User Blocked', 'buddyboss' ); ?></span>

	</div>

	<div class="acomment-content">
		<?php if ( bp_moderation_is_user_suspended( bp_get_activity_comment_user_id() ) ) {
			esc_html_e( 'Content from suspended user.', 'buddyboss' );
		} else {
			esc_html_e( 'Content from blocked user.', 'buddyboss' );
		} ?>
	</div>

	<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
</li>
