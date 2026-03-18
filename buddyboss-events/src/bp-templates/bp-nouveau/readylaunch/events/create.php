<?php
/**
 * ReadyLaunch: Create Event.
 *
 * Template for /events/create — multi-step event creation form.
 *
 * @package BuddyBoss\Events\Templates
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() || ! bp_events_user_can_create( bp_loggedin_user_id() ) ) {
	?>
	<div class="bb-rl-events-create bb-rl-screen-content">
		<p class="bb-rl-notice bb-rl-notice--error">
			<?php esc_html_e( 'You do not have permission to create events.', 'buddyboss' ); ?>
		</p>
	</div>
	<?php
	return;
}
?>

<div class="bb-rl-events-create bb-rl-screen-content">

	<div class="bb-rl-events-create__header">
		<a href="<?php echo esc_url( bp_get_events_directory_url() ); ?>" class="bb-rl-back-link">
			&larr; <?php esc_html_e( 'Events', 'buddyboss' ); ?>
		</a>
		<h1><?php esc_html_e( 'Create Event', 'buddyboss' ); ?></h1>
	</div>

	<div class="bb-rl-events-create__steps">
		<div class="bb-rl-step-indicator">
			<div class="bb-rl-step is-active" data-step="1">
				<span class="bb-rl-step__number">1</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Details', 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-step" data-step="2">
				<span class="bb-rl-step__number">2</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Date & Time', 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-step" data-step="3">
				<span class="bb-rl-step__number">3</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Location', 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-step" data-step="4">
				<span class="bb-rl-step__number">4</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Visibility', 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-step" data-step="5">
				<span class="bb-rl-step__number">5</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Categories', 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-step" data-step="6">
				<span class="bb-rl-step__number">6</span>
				<span class="bb-rl-step__label"><?php esc_html_e( 'Review', 'buddyboss' ); ?></span>
			</div>
		</div>
	</div>

	<div id="bb-rl-event-create-form">
		<?php /* Multi-step form rendered via JS component */ ?>
		<div class="bb-rl-event-create-form__loading">
			<span class="bb-rl-loading-spinner"></span>
		</div>
	</div>

	<script type="text/javascript">
		window.bpEventsCreate = {
			nonce:       '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>',
			restUrl:     '<?php echo esc_js( esc_url_raw( rest_url( 'buddyboss/v1/events' ) ) ); ?>',
			currentUser: <?php echo (int) bp_loggedin_user_id(); ?>,
			groupId:     <?php echo (int) ( bp_get_current_group_id() ?? 0 ); ?>,
			timezones:   <?php echo wp_json_encode( timezone_identifiers_list() ); ?>,
			moderation:  <?php echo json_encode( bp_events_moderation_enabled() ); ?>,
			i18n:        {
				step1Title:    '<?php echo esc_js( __( 'Event Details', 'buddyboss' ) ); ?>',
				step2Title:    '<?php echo esc_js( __( 'Date & Time', 'buddyboss' ) ); ?>',
				step3Title:    '<?php echo esc_js( __( 'Location', 'buddyboss' ) ); ?>',
				step4Title:    '<?php echo esc_js( __( 'Visibility', 'buddyboss' ) ); ?>',
				step5Title:    '<?php echo esc_js( __( 'Categories & Tags', 'buddyboss' ) ); ?>',
				step6Title:    '<?php echo esc_js( __( 'Review & Submit', 'buddyboss' ) ); ?>',
				submitPending: '<?php echo esc_js( __( 'Event submitted for review.', 'buddyboss' ) ); ?>',
				submitPublish: '<?php echo esc_js( __( 'Event created successfully!', 'buddyboss' ) ); ?>',
			}
		};
	</script>

</div>
