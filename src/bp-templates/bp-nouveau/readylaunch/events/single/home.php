<?php
/**
 * ReadyLaunch: Single Event.
 *
 * Template for /events/{slug}
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$event = bp_events_get_current_event();

if ( ! $event ) {
	return;
}
?>

<div class="bb-rl-event-single bb-rl-screen-content" data-event-id="<?php echo (int) $event->id; ?>">

	<div class="bb-rl-event-single__header">

		<div class="bb-rl-event-single__breadcrumb">
			<a href="<?php echo esc_url( bp_get_events_directory_url() ); ?>">
				<?php esc_html_e( 'Events', 'buddyboss' ); ?>
			</a>
			<span class="bb-rl-breadcrumb-sep">/</span>
			<span><?php echo esc_html( $event->title ); ?></span>
		</div>

		<?php if ( $event->user_can_edit() ) : ?>
			<div class="bb-rl-event-single__actions">
				<a href="<?php echo esc_url( bp_get_event_permalink( $event ) . 'edit/' ); ?>"
				   class="bb-rl-btn bb-rl-btn--secondary bb-rl-btn--sm">
					<?php esc_html_e( 'Edit Event', 'buddyboss' ); ?>
				</a>
				<button class="bb-rl-btn bb-rl-btn--danger bb-rl-btn--sm bb-rl-event-cancel-btn"
						data-event-id="<?php echo (int) $event->id; ?>">
					<?php esc_html_e( 'Cancel Event', 'buddyboss' ); ?>
				</button>
			</div>
		<?php endif; ?>

	</div>

	<div class="bb-rl-event-single__body">

		<div class="bb-rl-event-single__main">

			<div class="bb-rl-event-single__status-badges">
				<span class="bb-rl-event-type-badge bb-rl-event-type--<?php echo esc_attr( $event->type ); ?>">
					<?php
					$types = array(
						'in-person' => __( 'In Person', 'buddyboss' ),
						'virtual'   => __( 'Virtual', 'buddyboss' ),
						'hybrid'    => __( 'Hybrid', 'buddyboss' ),
					);
					echo esc_html( $types[ $event->type ] ?? $event->type );
					?>
				</span>
				<?php if ( ! empty( $event->recurrence_rule ) ) : ?>
					<span class="bb-rl-event-badge bb-rl-event-badge--recurring">
						<?php esc_html_e( 'Recurring', 'buddyboss' ); ?>
					</span>
				<?php endif; ?>
				<?php if ( 'cancelled' === $event->status ) : ?>
					<span class="bb-rl-event-badge bb-rl-event-badge--cancelled">
						<?php esc_html_e( 'Cancelled', 'buddyboss' ); ?>
					</span>
				<?php endif; ?>
			</div>

			<h1 class="bb-rl-event-single__title">
				<?php echo esc_html( $event->title ); ?>
			</h1>

			<div class="bb-rl-event-single__datetime">
				<i class="bb-icon-clock"></i>
				<div>
					<span class="bb-rl-event-single__start">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $event->start_date ) ) ); ?>
					</span>
					<?php if ( ! empty( $event->end_date ) ) : ?>
						<span class="bb-rl-event-single__sep"> &ndash; </span>
						<span class="bb-rl-event-single__end">
							<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $event->end_date ) ) ); ?>
						</span>
					<?php endif; ?>
					<span class="bb-rl-event-single__timezone">(<?php echo esc_html( $event->timezone ); ?>)</span>
				</div>
			</div>

			<?php if ( 'in-person' === $event->type || 'hybrid' === $event->type ) : ?>
				<div class="bb-rl-event-single__location">
					<i class="bb-icon-location"></i>
					<div>
						<?php if ( ! empty( $event->venue_name ) ) : ?>
							<strong><?php echo esc_html( $event->venue_name ); ?></strong><br>
						<?php endif; ?>
						<?php if ( ! empty( $event->venue_address ) ) : ?>
							<span><?php echo esc_html( $event->venue_address ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( 'virtual' === $event->type || 'hybrid' === $event->type ) : ?>
				<div class="bb-rl-event-single__virtual">
					<i class="bb-icon-video"></i>
					<span>
						<?php esc_html_e( 'Virtual Event', 'buddyboss' ); ?>
						<?php if ( ! empty( $event->virtual_type ) ) : ?>
							&mdash; <?php echo esc_html( ucfirst( $event->virtual_type ) ); ?>
						<?php endif; ?>
					</span>
					<?php /* Virtual URL only shown to registered attendees — Phase 2 */ ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $event->capacity ) ) : ?>
				<div class="bb-rl-event-single__capacity">
					<i class="bb-icon-users"></i>
					<?php
					printf(
						esc_html__( 'Capacity: %d', 'buddyboss' ),
						(int) $event->capacity
					);
					?>
				</div>
			<?php endif; ?>

			<div class="bb-rl-event-single__description">
				<?php echo wp_kses_post( $event->description ); ?>
			</div>

		</div>

		<div class="bb-rl-event-single__sidebar">

			<?php
			$current_user_id  = bp_loggedin_user_id();
			$attendees        = bp_events_get_attendees( $event->id, 'registered' );
			$attendee_ids     = wp_list_pluck( $attendees, 'user_id' );
			$registered_count = count( $attendees );
			$at_capacity      = ! is_null( $event->capacity ) && $registered_count >= (int) $event->capacity;
			$is_attending     = $current_user_id && in_array( $current_user_id, $attendee_ids, true );
			$is_waitlisted    = false;
			$can_rsvp         = true;
			$restricted_msg   = '';

			if ( $current_user_id && ! $is_attending ) {
				$waitlisted_ids = wp_list_pluck( bp_events_get_waitlist( $event->id ), 'user_id' );
				$is_waitlisted  = in_array( $current_user_id, $waitlisted_ids, true );
			}

			if ( $current_user_id ) {
				$rsvp_check = bp_events_user_can_rsvp( $event->id, $current_user_id );
				if ( is_wp_error( $rsvp_check ) ) {
					$can_rsvp       = false;
					$restricted_msg = $rsvp_check->get_error_message();
				}
			}

			$rsvp_group_id = (int) bp_events_get_meta( $event->id, 'rsvp_group_id', true );
			if ( ! $current_user_id && $rsvp_group_id > 0 ) {
				$group = groups_get_group( $rsvp_group_id );
				if ( ! empty( $group->id ) ) {
					$can_rsvp       = false;
					$restricted_msg = sprintf( __( 'RSVP limited to members of %s', 'buddyboss' ), $group->name );
				}
			}
			?>

			<div class="bb-rl-event-tickets-panel" id="bb-rl-event-tickets">
				<div class="bb-rl-event-tickets-panel__inner">
					<h3><?php esc_html_e( 'Register', 'buddyboss' ); ?></h3>

					<?php if ( ! $current_user_id ) : ?>
						<p><?php esc_html_e( 'Please log in to RSVP.', 'buddyboss' ); ?></p>

					<?php elseif ( ! $can_rsvp ) : ?>
						<button class="bb-rl-btn bb-rl-btn--secondary" disabled>
							<?php echo esc_html( $restricted_msg ?: __( 'RSVP not available', 'buddyboss' ) ); ?>
						</button>

					<?php elseif ( $is_attending ) : ?>
						<button id="bb-rl-rsvp-btn" class="bb-rl-btn bb-rl-btn--success" data-state="attending">
							<?php esc_html_e( 'Attending &#x2713;', 'buddyboss' ); ?>
						</button>
						<p class="bb-rl-rsvp-cancel-hint">
							<a href="#" id="bb-rl-rsvp-cancel-link"><?php esc_html_e( 'Cancel RSVP', 'buddyboss' ); ?></a>
						</p>

					<?php elseif ( $is_waitlisted ) : ?>
						<button id="bb-rl-rsvp-btn" class="bb-rl-btn bb-rl-btn--secondary" data-state="waitlisted">
							<?php esc_html_e( 'On Waitlist', 'buddyboss' ); ?>
						</button>
						<p class="bb-rl-rsvp-cancel-hint">
							<a href="#" id="bb-rl-rsvp-cancel-link"><?php esc_html_e( 'Leave Waitlist', 'buddyboss' ); ?></a>
						</p>

					<?php elseif ( $at_capacity ) : ?>
						<button id="bb-rl-rsvp-btn" class="bb-rl-btn bb-rl-btn--primary" data-state="none">
							<?php esc_html_e( 'Join Waitlist', 'buddyboss' ); ?>
						</button>

					<?php else : ?>
						<button id="bb-rl-rsvp-btn" class="bb-rl-btn bb-rl-btn--primary" data-state="none">
							<?php esc_html_e( 'RSVP', 'buddyboss' ); ?>
						</button>

					<?php endif; ?>

					<?php if ( ! empty( $event->capacity ) ) : ?>
						<p class="bb-rl-event-capacity-info">
							<?php
							printf(
								esc_html__( '%1$d / %2$d spots filled', 'buddyboss' ),
								$registered_count,
								(int) $event->capacity
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<?php /* Public attendee list */ ?>
			<?php if ( ! empty( $attendees ) ) : ?>
			<div class="bb-rl-event-attendees" id="bb-rl-event-attendees">
				<h4><?php printf( esc_html__( 'Attending (%d)', 'buddyboss' ), $registered_count ); ?></h4>
				<ul class="bb-rl-attendee-list">
					<?php foreach ( $attendees as $attendee ) : ?>
						<li class="bb-rl-attendee-list__item">
							<?php echo bp_core_fetch_avatar( array( 'item_id' => $attendee->user_id, 'type' => 'thumb', 'html' => true ) ); ?>
							<span class="bb-rl-attendee-list__name">
								<?php echo esc_html( get_the_author_meta( 'display_name', $attendee->user_id ) ); ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<?php /* Organizer-only management panel */ ?>
			<?php if ( $event->user_can_edit() ) : ?>
			<div class="bb-rl-event-organizer-panel" id="bb-rl-organizer-panel">
				<h4><?php esc_html_e( 'Manage Attendees', 'buddyboss' ); ?></h4>
				<ul class="bb-rl-attendee-manage-list" id="bb-rl-attendee-manage-list">
					<?php foreach ( $attendees as $attendee ) : ?>
						<li class="bb-rl-attendee-manage-list__item"
							data-user-id="<?php echo (int) $attendee->user_id; ?>">
							<?php echo esc_html( get_the_author_meta( 'display_name', $attendee->user_id ) ); ?>
							<button class="bb-rl-btn bb-rl-btn--sm bb-rl-btn--danger bb-rl-remove-attendee"
									data-user-id="<?php echo (int) $attendee->user_id; ?>">
								<?php esc_html_e( 'Remove', 'buddyboss' ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>

			<div class="bb-rl-event-single__calendar-links">
				<h4><?php esc_html_e( 'Add to Calendar', 'buddyboss' ); ?></h4>
				<a href="<?php echo esc_url( rest_url( 'buddyboss/v1/events/' . $event->id . '/ical' ) ); ?>"
				   class="bb-rl-cal-link bb-rl-cal-link--ical" download>
					<?php esc_html_e( 'iCal / Apple Calendar', 'buddyboss' ); ?>
				</a>
				<a href="#" class="bb-rl-cal-link bb-rl-cal-link--gcal"
				   data-event-id="<?php echo (int) $event->id; ?>">
					<?php esc_html_e( 'Google Calendar', 'buddyboss' ); ?>
				</a>
			</div>

		</div>

	</div>

</div>
