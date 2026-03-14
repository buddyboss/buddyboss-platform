<?php
/**
 * BuddyBoss Events Filters & Actions.
 *
 * @package BuddyBoss\Events\Filters
 * @since BuddyBoss Events 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Install DB tables on activation.
add_action( 'bp_events_activated', 'bp_events_install' );

/**
 * Register 'events' as an optional BuddyBoss component.
 * This tells bp-core to auto-include bp-events/bp-events-loader.php.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $components Optional components list.
 * @return array
 */
function bp_events_register_optional_component( $components ) {
	$components[] = 'events';
	return $components;
}
add_filter( 'bp_optional_components', 'bp_events_register_optional_component' );

/**
 * Register Events REST API classes in the BuddyBoss autoloader map.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param array $component_map Class-to-component map.
 * @return array
 */
function bp_events_register_autoloader_classes( $component_map ) {
	$component_map['BP_Events_Component']                    = 'events';
	$component_map['BP_Event']                               = 'events';
	$component_map['BP_REST_Events_Endpoint']                = 'events';
	$component_map['BP_REST_Events_Settings_Endpoint']       = 'events';
	return $component_map;
}
add_filter( 'bp_class_component_map', 'bp_events_register_autoloader_classes' );

/**
 * Auto-activate the events component on first load if not yet set.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_maybe_activate() {
	$active = bp_get_option( 'bp-active-components', array() );
	if ( ! isset( $active['events'] ) ) {
		$active['events'] = '1';
		bp_update_option( 'bp-active-components', $active );
	}
}
add_action( 'bp_init', 'bp_events_maybe_activate', 1 );

// Enqueue frontend assets.
add_action( 'wp_enqueue_scripts', 'bp_events_enqueue_scripts' );

/**
 * Enqueue frontend scripts and styles.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_enqueue_scripts() {
	if ( ! bp_is_active( 'events' ) ) {
		return;
	}

	$suffix  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	$version = buddypress()->version;

	// Only load on events pages.
	if ( bp_is_current_component( 'events' ) || bp_is_group_single() ) {
		wp_enqueue_style(
			'bp-events',
			buddypress()->plugin_url . "bp-events/admin/css/bp-events{$suffix}.css",
			array(),
			$version
		);

		wp_enqueue_script(
			'bp-events',
			buddypress()->plugin_url . "bp-events/admin/js/bp-events{$suffix}.js",
			array( 'jquery', 'bp-nouveau' ),
			$version,
			true
		);

		wp_localize_script(
			'bp-events',
			'bpEventsSettings',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'restUrl'       => esc_url_raw( rest_url( 'buddyboss/v1/events' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'currentUserId' => bp_loggedin_user_id(),
				'calendarView'  => bb_get_events_default_calendar_view(),
				'i18n'          => array(
					'createEvent'   => __( 'Create Event', 'buddyboss' ),
					'editEvent'     => __( 'Edit Event', 'buddyboss' ),
					'deleteEvent'   => __( 'Delete Event', 'buddyboss' ),
					'confirmDelete' => __( 'Are you sure you want to delete this event?', 'buddyboss' ),
				),
			)
		);
	}
}

// Enqueue admin assets.
add_action( 'admin_enqueue_scripts', 'bp_events_admin_enqueue_scripts' );

/**
 * Enqueue admin scripts and styles.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_admin_enqueue_scripts( $hook ) {
	if ( false === strpos( $hook, 'bp-events' ) ) {
		return;
	}

	$version = buddypress()->version;

	wp_enqueue_style(
		'bp-events-admin',
		buddypress()->plugin_url . 'bp-events/admin/css/bp-events-admin.css',
		array(),
		$version
	);

	wp_enqueue_script(
		'bp-events-admin',
		buddypress()->plugin_url . 'bp-events/admin/js/bp-events-admin.js',
		array( 'jquery', 'wp-api' ),
		$version,
		true
	);
}

/** BuddyBoss Moderation Integration ******************************************/

/**
 * Instantiate BP_Moderation_Events to register 'events' as a reportable
 * content type in the BuddyBoss moderation system.
 *
 * Runs at priority 20 on 'bp_setup_components' so the events component
 * (priority 9) and moderation component are both loaded first.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_setup_moderation() {
	if ( bp_is_active( 'moderation' ) ) {
		new BP_Moderation_Events();
	}
}
add_action( 'bp_setup_components', 'bp_events_setup_moderation', 20 );

/** Admin: Approve Pending Event **********************************************/

/**
 * AJAX handler: approve a pending event (set status to published).
 *
 * Expects a POST request with:
 *   - action   : 'bp_events_approve'
 *   - event_id : int
 *   - nonce    : wp_create_nonce( 'bp_events_admin_action' )
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_ajax_approve_event() {
	check_ajax_referer( 'bp_events_admin_action', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( __( 'Permission denied.', 'buddyboss' ) );
	}

	$event_id = absint( $_POST['event_id'] );

	if ( ! $event_id ) {
		wp_send_json_error( __( 'Invalid event ID.', 'buddyboss' ) );
	}

	$result = bp_events_update_event( $event_id, array( 'status' => 'published' ) );

	if ( $result ) {
		wp_send_json_success();
	} else {
		wp_send_json_error( __( 'Could not approve event.', 'buddyboss' ) );
	}
}
add_action( 'wp_ajax_bp_events_approve', 'bp_events_ajax_approve_event' );

/** Admin: Moderation Status Enforcement **************************************/

/**
 * Force 'pending' status when moderation is enabled and a parent event is
 * being saved as 'published'.
 *
 * Child occurrence rows (parent_event_id is set) bypass this check and are
 * always published directly — their parent controls moderation.
 *
 * Hooked on 'bp_events_before_event_save' which fires inside BP_Event::save().
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param BP_Event $event The event object about to be saved.
 */
function bp_events_enforce_moderation_status( $event ) {
	// Only override parent events being saved as 'published'.
	if ( 'published' !== $event->status ) {
		return;
	}

	// Child occurrences are always published without moderation.
	if ( ! empty( $event->parent_event_id ) ) {
		return;
	}

	// Override to 'pending' when moderation queue is enabled.
	if ( bp_events_moderation_enabled() ) {
		$event->status = 'pending';
	}
}
add_action( 'bp_events_before_event_save', 'bp_events_enforce_moderation_status', 10, 1 );

/** Recurring Occurrences *****************************************************/

/**
 * After a parent event is saved, generate occurrences if it is published
 * and has a recurrence rule.
 *
 * Hooked at priority 20 so cache-clearing (priority 10) runs first.
 *
 * @since BuddyBoss Events 1.0.0
 *
 * @param BP_Event $event The saved event object.
 */
add_action( 'bp_events_after_event_save', 'bp_events_generate_occurrences', 20, 1 );

/**
 * WP Cron handler: extend occurrence windows for all recurring parent events
 * whose furthest child is within 90 days of the 2-year window.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_cron_extend_occurrences() {
	global $wpdb;

	$bp = buddypress();

	// Get all published recurring parent events.
	$event_ids = $wpdb->get_col(
		"SELECT id FROM {$bp->events->table_name}
		WHERE parent_event_id IS NULL
		AND recurrence_rule != ''
		AND status = 'published'"
	);

	if ( empty( $event_ids ) ) {
		return;
	}

	foreach ( $event_ids as $event_id ) {
		$event = bp_events_get_event( (int) $event_id );

		if ( ! $event ) {
			continue;
		}

		bp_events_extend_occurrences_for_event( $event );
	}
}
add_action( 'bp_events_extend_occurrences', 'bp_events_cron_extend_occurrences' );

// Template loader override for ReadyLaunch.
add_filter( 'bp_get_template_part', 'bp_events_readylaunch_template_filter', 10, 3 );

/**
 * Override templates with ReadyLaunch versions when ReadyLaunch is active.
 *
 * @since BuddyBoss Events 1.0.0
 */
function bp_events_readylaunch_template_filter( $templates, $slug, $name ) {
	if ( ! bb_is_readylaunch_enabled() ) {
		return $templates;
	}

	if ( false === strpos( $slug, 'events' ) ) {
		return $templates;
	}

	$rl_templates = array();
	foreach ( $templates as $template ) {
		$rl_templates[] = str_replace( 'buddypress/', 'readylaunch/', $template );
	}

	return array_merge( $rl_templates, $templates );
}
