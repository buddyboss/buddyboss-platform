<?php
/**
 * ReadyLaunch: Events Loop.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php if ( bp_has_events() ) : ?>

	<div class="bb-rl-events-loop">

		<?php while ( bp_events() ) : ?>
			<?php bp_get_template_part( 'events/event-card' ); ?>
		<?php endwhile; ?>

	</div>

<?php else : ?>

	<div class="bb-rl-events-loop--empty">
		<p><?php esc_html_e( 'No events found.', 'buddyboss' ); ?></p>
		<?php if ( bp_events_user_can_create( bp_loggedin_user_id() ) ) : ?>
			<a href="<?php echo esc_url( bp_get_events_directory_url() . 'create/' ); ?>" class="bb-rl-btn bb-rl-btn--primary">
				<?php esc_html_e( 'Create the first event', 'buddyboss' ); ?>
			</a>
		<?php endif; ?>
	</div>

<?php endif; ?>
