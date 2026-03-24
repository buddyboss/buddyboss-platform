<?php
/**
 * ReadyLaunch: Edit Event.
 *
 * Template for /events/{slug}/edit
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$event = bp_events_get_current_event();

if ( ! $event || ! $event->user_can_edit() ) {
	return;
}
?>

<div class="bb-rl-event-edit bb-rl-screen-content" data-event-id="<?php echo (int) $event->id; ?>">

	<div class="bb-rl-event-edit__header">
		<a href="<?php echo esc_url( bp_get_event_permalink( $event ) ); ?>" class="bb-rl-back-link">
			&larr; <?php echo esc_html( $event->title ); ?>
		</a>
		<h1><?php esc_html_e( 'Edit Event', 'buddyboss' ); ?></h1>
	</div>

	<?php if ( ! empty( $event->recurrence_rule ) ) : ?>
		<div class="bb-rl-event-edit__recurrence-notice" id="bb-rl-recurrence-notice">
			<p><?php esc_html_e( 'This is a recurring event.', 'buddyboss' ); ?></p>
			<div class="bb-rl-recurrence-edit-choices">
				<label>
					<input type="radio" name="recurrence_edit_scope" value="occurrence" checked>
					<?php esc_html_e( 'Edit this occurrence only', 'buddyboss' ); ?>
				</label>
				<label>
					<input type="radio" name="recurrence_edit_scope" value="series">
					<?php esc_html_e( 'Edit this and all future occurrences', 'buddyboss' ); ?>
				</label>
			</div>
		</div>
	<?php endif; ?>

	<div id="bb-rl-event-edit-form">
		<?php /* Form rendered via React/Vue JS component — data from REST API */ ?>
		<div class="bb-rl-event-edit-form__loading">
			<span class="bb-rl-loading-spinner"></span>
		</div>
	</div>

	<?php if ( ! empty( $event->group_id ) ) : ?>
	<div id="bp-events-invite-panel" class="bp-events-invite-panel" aria-live="polite">
		<h3><?php esc_html_e( 'Invite Group Members', 'buddyboss' ); ?></h3>
		<input type="search" id="bp-events-invite-search"
		       placeholder="<?php esc_attr_e( 'Search members&hellip;', 'buddyboss' ); ?>"
		       aria-label="<?php esc_attr_e( 'Search group members', 'buddyboss' ); ?>" />
		<ul id="bp-events-invite-list" class="bp-events-invite-list" aria-label="<?php esc_attr_e( 'Group members', 'buddyboss' ); ?>">
			<li class="bp-events-invite-loading"><?php esc_html_e( 'Loading members&hellip;', 'buddyboss' ); ?></li>
		</ul>
		<p class="bp-events-invite-selected">
			<span id="bp-events-invite-count">0</span>
			<?php esc_html_e( 'member(s) selected', 'buddyboss' ); ?>
		</p>
		<button type="button" id="bp-events-invite-send" class="bp-btn bp-btn--primary" disabled>
			<?php esc_html_e( 'Send Invites', 'buddyboss' ); ?>
		</button>
		<p id="bp-events-invite-status" role="status"></p>
	</div>
	<?php endif; ?>

</div>
