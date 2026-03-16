/* global FullCalendar, bpEventsGroup */
/**
 * BuddyBoss Events — Group Calendar initialisation.
 *
 * Reads window.bpEventsGroup (localised via wp_localize_script) and mounts
 * a FullCalendar instance on #bp-events-group-calendar, scoped to the current
 * group by appending ?group_id={groupId}&_fc=1 to the REST events URL.
 *
 * @package BuddyBoss\Events
 * @since   BuddyBoss Events 1.0.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	var el = document.getElementById( 'bp-events-group-calendar' );

	if ( ! el ) {
		return;
	}

	var settings  = window.bpEventsGroup || {};
	var groupId   = parseInt( settings.groupId, 10 ) || 0;
	var eventsUrl = settings.eventsUrl || '';
	var nonce     = settings.nonce || '';

	if ( ! groupId || ! eventsUrl ) {
		return;
	}

	var calendar = new FullCalendar.Calendar( el, {
		initialView: 'dayGridMonth',
		headerToolbar: {
			left:   'prev,next today',
			center: 'title',
			right:  ''
		},
		events: {
			url:         eventsUrl,
			method:      'GET',
			extraParams: {
				_fc:      1,
				per_page: 200,
				group_id: groupId
			},
			extraHeaders: {
				'X-WP-Nonce': nonce
			},
			failure: function() {
				el.insertAdjacentHTML(
					'beforeend',
					'<p class="bp-events-calendar-error">Could not load events.</p>'
				);
			}
		},
		eventClick: function( info ) {
			if ( info.event.url ) {
				window.location.href = info.event.url;
				info.jsEvent.preventDefault();
			}
		}
	} );

	calendar.render();
} );
