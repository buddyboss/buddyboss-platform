<?php
/**
 * ReadyLaunch - The template for activity loop.
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

bp_nouveau_before_loop();

if ( bp_has_activities( bp_ajax_querystring( 'activity' ) . '&display_comments=false' ) ) :

	$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page'];
	if ( $is_first_page ) :
		?>
		<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list">
	<?php
	endif;

	while ( bp_activities() ) :
		bp_the_activity();
		bp_get_template_part( 'activity/entry' );
	endwhile;

	if ( bp_activity_has_more_items() ) :
		?>
		<li class="bb-rl-load-more">
			<a class="bb-rl-button bb-rl-button--brandFill" href="<?php bp_activity_load_more_link(); ?>">
				<?php esc_html_e( 'Load More', 'buddyboss' ); ?>
			</a>
		</li>
	<?php
	endif;
	?>

	<li class="activity activity_update activity-item bb-rl-activity-popup"></li>

	<?php if ( $is_first_page ) : ?>
		</ul>
	<?php
	endif;

else :
	bp_nouveau_user_feedback( 'activity-loop-none' );
endif;

bp_nouveau_after_loop();
