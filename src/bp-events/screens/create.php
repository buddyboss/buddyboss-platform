<?php
/**
 * Event creation screen handler.
 *
 * Handles /events/create — outputs the multi-step wizard container
 * and delegates styling/JS to bp_events_enqueue_create_assets().
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the event create screen.
 *
 * Fires on bp_events_setup_theme_compat when the URL is /events/create.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_create_setup() {
	if ( ! bp_is_action_variable( 'create', 0 ) ) {
		return;
	}

	// Must be logged in to create an event.
	if ( ! is_user_logged_in() ) {
		auth_redirect();
		return;
	}

	add_action( 'bp_template_content', 'bp_events_create_content' );
	bp_core_load_template( apply_filters( 'bp_events_create_template', 'events/create' ) );
}
add_action( 'bp_events_setup_theme_compat', 'bp_events_create_setup' );

/**
 * Output the event creation wizard container.
 *
 * The JavaScript in bp-events-create.js binds to #bb-rl-event-create-form
 * on DOMContentLoaded and renders all wizard steps inside it.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_create_content() {
	?>
	<div id="bb-rl-event-create-form" class="bb-rl-event-wizard">

		<div id="bb-rl-wizard-steps" class="bb-rl-wizard-steps">
			<!-- Step indicators injected by bp-events-create.js -->
		</div>

		<div id="bb-rl-wizard-error" class="bb-rl-wizard-error" style="display:none;"></div>

		<div id="bb-rl-wizard-content" class="bb-rl-wizard-content">
			<!-- Step panels injected by bp-events-create.js -->
		</div>

		<div class="bb-rl-wizard-nav">
			<button id="bb-rl-wizard-prev" class="bb-rl-btn bb-rl-btn-secondary" style="display:none">
				<?php esc_html_e( 'Back', 'buddyboss' ); ?>
			</button>
			<button id="bb-rl-wizard-next" class="bb-rl-btn bb-rl-btn-primary">
				<?php esc_html_e( 'Next', 'buddyboss' ); ?>
			</button>
			<button id="bb-rl-wizard-draft" class="bb-rl-btn bb-rl-btn-secondary" style="display:none">
				<?php esc_html_e( 'Save Draft', 'buddyboss' ); ?>
			</button>
			<button id="bb-rl-wizard-publish" class="bb-rl-btn bb-rl-btn-primary" style="display:none">
				<?php esc_html_e( 'Publish', 'buddyboss' ); ?>
			</button>
		</div>

	</div>
	<?php
}
