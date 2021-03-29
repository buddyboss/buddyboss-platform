<?php
/**
 * BuddyBoss - Activity Feed Comment
 *
 * This template is used by bp_activity_comments() functions to show
 * each activity.
 *
 * @version 3.0.0
 */

	?>

<li id="acomment-<?php bp_activity_comment_id(); ?>" class="<?php bp_activity_comment_css_class() ?>" data-bp-activity-comment-id="<?php bp_activity_comment_id(); ?>">

	<div class="activity_more_options">

		<span class="activity_more_options_action" data-balloon-pos="up" data-balloon="<?php _e( 'More Options', 'buddyboss' ); ?>">
			<i class="bb-icon bb-icon-menu-dots-h"></i>
		</span>
		<div class="activity_more_options_list">
			<p>
				<a href="#content-report" id="report-content-forum_topic-7154" class="button item-button bp-secondary-action outline report-content" data-bp-content-id="7154" data-bp-content-type="forum_topic" data-bp-nonce="c58f593b49">
					<span class="bp-screen-reader-text">Report</span><span class="report-label">Report</span>
				</a>
			</p>
		</div>

	</div><!-- .activity_more_options -->

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
        <?php bp_activity_comment_content(); ?>

        <?php do_action( 'bp_activity_after_comment_content', bp_get_activity_comment_id() ); ?>
    </div>

	<?php bp_nouveau_activity_comment_buttons( array( 'container' => 'div' ) ); ?>

	<?php bp_nouveau_activity_recurse_comments( bp_activity_current_comment() ); ?>
</li>
