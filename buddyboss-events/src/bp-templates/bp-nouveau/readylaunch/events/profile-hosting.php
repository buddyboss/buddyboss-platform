<?php
/**
 * BuddyBoss Events — Profile Hosting Template.
 *
 * Rendered inside the member profile wrapper when visiting
 * /members/{username}/events/hosting.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */
defined( 'ABSPATH' ) || exit;

$user_id = bp_displayed_user_id();
$events  = bp_events_get_events( array(
	'organizer_id' => $user_id,
	'status'       => 'published',
) );
?>
<div class="bp-events-profile bp-events-profile--hosting">
	<h3 class="bp-events-profile__heading">
		<?php esc_html_e( 'Events Hosting', 'buddyboss' ); ?>
	</h3>

	<?php if ( ! empty( $events ) ) : ?>
		<div class="bp-events-list bp-events-list--profile">
			<?php foreach ( $events as $event ) : ?>
				<?php bp_get_template_part( 'events/event-card', null, array( 'event' => $event ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p class="bp-events-profile__empty">
			<?php esc_html_e( 'This member has not hosted any events yet.', 'buddyboss' ); ?>
		</p>
	<?php endif; ?>
</div>
