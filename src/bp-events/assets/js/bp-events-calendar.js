/* global FullCalendar, bpEventsSettings */
/**
 * BuddyBoss Events — FullCalendar initialisation.
 *
 * Reads window.bpEventsSettings (localised via wp_localize_script) and
 * mounts a FullCalendar instance on #bb-rl-events-calendar.
 *
 * @package BuddyBoss\Events
 * @since   BuddyBoss Events 1.0.0
 */
document.addEventListener( 'DOMContentLoaded', function() {
	'use strict';

	var el = document.getElementById( 'bb-rl-events-calendar' );

	if ( ! el ) {
		return;
	}

	var settings = window.bpEventsSettings || {};
	var initialView = ( settings.calendarView === 'list' ) ? 'listMonth' : 'dayGridMonth';

	var calendar = new FullCalendar.Calendar( el, {
		initialView: initialView,
		headerToolbar: {
			left:   'prev,next today',
			center: 'title',
			right:  ''
		},
		events: {
			url:         settings.restUrl,
			method:      'GET',
			extraParams: { _fc: 1, per_page: 200 },
			failure:     function() {
				el.insertAdjacentHTML(
					'beforeend',
					'<p class="bb-rl-calendar-error">' +
					( settings.i18n && settings.i18n.loadError
						? settings.i18n.loadError
						: 'Could not load events.' ) +
					'</p>'
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

	// View toggle buttons.
	var toggleBtns = document.querySelectorAll( '.bb-rl-view-btn' );

	toggleBtns.forEach( function( btn ) {
		btn.addEventListener( 'click', function() {
			var view = btn.getAttribute( 'data-view' );

			if ( ! view ) {
				return;
			}

			calendar.changeView( view );

			toggleBtns.forEach( function( b ) {
				b.classList.remove( 'active' );
			} );

			btn.classList.add( 'active' );
		} );
	} );

	// Category filter dropdown.
	var categorySelect = document.getElementById( 'bb-rl-events-category-select' );
	if ( categorySelect ) {
		categorySelect.addEventListener( 'change', function() {
			var catId = this.value;

			// Remove all existing event sources and re-add with updated URL.
			calendar.removeAllEventSources();

			var url = settings.restUrl + '?_fc=1&per_page=200&_wpnonce=' + settings.nonce;
			if ( catId ) {
				url += '&category_id=' + encodeURIComponent( catId );
			}

			calendar.addEventSource( {
				url:    url,
				method: 'GET',
				failure: function() {
					el.insertAdjacentHTML(
						'beforeend',
						'<p class="bb-rl-calendar-error">' +
						( settings.i18n && settings.i18n.loadError
							? settings.i18n.loadError
							: 'Could not load events.' ) +
						'</p>'
					);
				}
			} );
		} );
	}
} );
