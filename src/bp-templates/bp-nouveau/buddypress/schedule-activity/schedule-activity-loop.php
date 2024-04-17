<?php
/**
 * The template for activity loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/schedule-activity/schedule-activity-loop.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

bp_nouveau_before_loop();
$activity_schedule_args = bp_parse_args(
	bp_ajax_querystring( 'activity' ),
	array(
		'status'  => sanitize_text_field( $_POST['status'] ),
		'sort'    => 'ASC',
	),
	'activity_schedule_args'
);

$activity_schedule_args['user_id'] = bp_loggedin_user_id();

add_filter( 'bp_activity_default_scope', '__return_empty_array' );
$activity_schedule_args['scope']   = '';

if ( bp_has_activities( $activity_schedule_args ) ) :

	remove_filter( 'bp_activity_default_scope', '__return_empty_array' );

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		<ul class="activity-list item-list bp-list">
		<?php
	endif;

	while ( bp_activities() ) :
		bp_the_activity();
		bp_get_template_part( 'schedule-activity/entry' );
	endwhile;

	if ( bp_activity_has_more_items() ) :
		?>
		<li class="load-more">
			<a class="button outline" href="<?php bp_activity_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
		</li>
		<?php
	endif;

	if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) :
		?>
		</ul>
		<?php
	endif;

else :
	bp_nouveau_user_feedback( 'activity-loop-none' );
endif;

bp_nouveau_after_loop();
