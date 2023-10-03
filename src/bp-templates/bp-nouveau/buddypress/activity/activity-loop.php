<?php
/**
 * The template for activity loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/activity-loop.php.
 *
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

bp_nouveau_before_loop(); ?>

<?php if ( bp_has_activities( bp_ajax_querystring( 'activity' ) ) ) : ?>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
		<ul class="activity-list item-list bp-list">
	<?php endif; ?>

	<?php
	while ( bp_activities() ) :
		bp_the_activity();
	?>

		<?php bp_get_template_part( 'activity/entry' ); ?>

	<?php endwhile; ?>

	<?php if ( bp_activity_has_more_items() ) : ?>

		<li class="load-more">
			<a class="button outline" href="<?php bp_activity_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
		</li>

	<?php endif; ?>

	<?php if ( empty( $_POST['page'] ) || 1 === (int) $_POST['page'] ) : ?>
		</ul>
	<?php endif; ?>

<?php else : ?>

		<?php bp_nouveau_user_feedback( 'activity-loop-none' ); ?>

<?php endif; ?>

<?php bp_nouveau_after_loop(); ?>
