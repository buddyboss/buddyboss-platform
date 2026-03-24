<?php
/**
 * Events directory screen handler.
 *
 * @package BuddyBoss\Events\Screens
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Set up the events directory screen.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_directory_setup() {
	add_action( 'bp_template_content', 'bp_events_directory_content' );
	bp_core_load_template( apply_filters( 'bp_events_directory_template', 'events/index' ) );
}
add_action( 'bp_events_setup_theme_compat', 'bp_events_directory_setup' );

/**
 * Output the events directory content.
 *
 * Renders the FullCalendar container and view toggle buttons, then delegates
 * to the template part for any additional content (e.g. list fallback).
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_directory_content() {
	?>
	<div class="bb-rl-events-directory">
		<div class="bb-rl-calendar-view-toggle">
			<button class="bb-rl-view-btn active" data-view="dayGridMonth"><?php esc_html_e( 'Month', 'buddyboss' ); ?></button>
			<button class="bb-rl-view-btn" data-view="listMonth"><?php esc_html_e( 'List', 'buddyboss' ); ?></button>
		</div>
		<div id="bb-rl-events-calendar"></div>
	</div>
	<?php
	bp_get_template_part( 'events/index' );
}
