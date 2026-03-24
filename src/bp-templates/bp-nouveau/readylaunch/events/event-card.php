<?php
/**
 * ReadyLaunch: Event Card.
 *
 * Used in the events directory loop to display a single event card.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<div class="bb-rl-event-card" data-event-id="<?php bp_event_id(); ?>">

	<a href="<?php bp_event_permalink(); ?>" class="bb-rl-event-card__link">

		<div class="bb-rl-event-card__date-badge">
			<span class="bb-rl-event-card__date-day">
				<?php echo esc_html( bp_get_event_start_date( 'j' ) ); ?>
			</span>
			<span class="bb-rl-event-card__date-month">
				<?php echo esc_html( bp_get_event_start_date( 'M' ) ); ?>
			</span>
		</div>

		<div class="bb-rl-event-card__body">

			<div class="bb-rl-event-card__meta">
				<span class="bb-rl-event-card__type bb-rl-event-type--<?php echo esc_attr( buddypress()->events->current_event->type ?? 'in-person' ); ?>">
					<?php bp_event_type_label(); ?>
				</span>
				<span class="bb-rl-event-card__time">
					<?php echo esc_html( bp_get_event_start_date( get_option( 'time_format' ) ) ); ?>
				</span>
			</div>

			<h3 class="bb-rl-event-card__title">
				<?php bp_event_title(); ?>
			</h3>

			<?php
			global $events_template;
			$event = $events_template->event ?? null;
			if ( $event && ! empty( $event->venue_name ) ) :
			?>
				<p class="bb-rl-event-card__location">
					<i class="bb-icon-location"></i>
					<?php echo esc_html( $event->venue_name ); ?>
				</p>
			<?php elseif ( $event && ! empty( $event->virtual_url ) ) : ?>
				<p class="bb-rl-event-card__location bb-rl-event-card__location--virtual">
					<i class="bb-icon-video"></i>
					<?php esc_html_e( 'Virtual Event', 'buddyboss' ); ?>
				</p>
			<?php endif; ?>

		</div>

	</a>

</div>
