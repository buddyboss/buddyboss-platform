<?php
/**
 * BuddyBoss Events — Group Events Calendar Template.
 *
 * Rendered by BP_Events_Group_Extension::display(). Mounts a FullCalendar
 * instance scoped to the current group's events via the REST API.
 *
 * The noscript fallback is only reached by browsers with JavaScript disabled.
 * Server-side privacy is already enforced: non-members of private/hidden groups
 * do not reach display() at all (BP_Group_Extension access controls gate it).
 * The REST path is further protected by the 403 non-member guard in
 * class-bp-rest-events-endpoint.php.
 *
 * @package BuddyBoss\Events
 * @since BuddyBoss Events 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$bp_events_group_id = isset( $GLOBALS['bp_events_current_group_id'] )
	? (int) $GLOBALS['bp_events_current_group_id']
	: 0;
?>
<div class="bp-events-group-wrap">

	<div id="bp-events-group-calendar"></div>

	<noscript>
		<?php
		$events = bp_events_get_events( array(
			'group_id' => $bp_events_group_id,
			'status'   => 'published',
		) );

		if ( ! empty( $events['events'] ) ) {
			foreach ( $events['events'] as $event ) {
				bp_get_template_part( 'events/event-card', null, array( 'event' => $event ) );
			}
		} else {
			echo '<p>' . esc_html__( 'No events yet.', 'buddyboss' ) . '</p>';
		}
		?>
	</noscript>

</div>
<?php
wp_enqueue_script(
	'bp-events-group-calendar',
	plugins_url( 'src/bp-events/assets/js/bp-events-group-calendar.js', BP_EVENTS_PLUGIN_FILE ),
	array( 'jquery' ),
	BP_PLATFORM_VERSION,
	true
);

wp_localize_script(
	'bp-events-group-calendar',
	'bpEventsGroup',
	array(
		'groupId'   => $bp_events_group_id,
		'eventsUrl' => rest_url( 'buddyboss/v1/events' ),
		'nonce'     => wp_create_nonce( 'wp_rest' ),
	)
);
