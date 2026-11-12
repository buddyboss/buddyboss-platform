<?php
/**
 * The template for activity loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/activity-loop.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop();

if ( bboss_has_activities( bboss_ajax_querystring( 'activity' ) ) ) :

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		<ul class="activity-list item-list bp-list">
		<?php
	endif;

	while ( bboss_activities() ) :
		bboss_the_activity();
		bboss_get_template_part( 'activity/entry' );
	endwhile;

	if ( bboss_activity_has_more_items() ) :
		?>
		<li class="load-more">
			<a class="button outline" href="<?php bboss_activity_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss-platform' ); ?></a>
		</li>
		<?php
	endif;
	?>

	<li class="activity activity_update activity-item activity-popup"></li>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		</ul>
		<?php
	endif;

else :
	bboss_nouveau_user_feedback( 'activity-loop-none' );
endif;

bboss_nouveau_after_loop();
