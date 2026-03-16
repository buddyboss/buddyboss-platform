/**
 * BuddyBoss Events — Single Event Page JS.
 *
 * Handles RSVP button state management, RSVP/cancel REST calls,
 * Google Calendar link fetch, and organizer attendee removal.
 *
 * Initialises from bpEventsSingle localised data (not DOM state)
 * to avoid in-page state-mismatch on reload.
 *
 * @package BuddyBoss\Events
 * @since   BuddyBoss Events 1.0.0
 */
( function() {
	'use strict';

	var cfg  = window.bpEventsSingle || {};
	var i18n = cfg.i18n || {};

	function init() {
		var btn           = document.getElementById( 'bb-rl-rsvp-btn' );
		var cancelLink    = document.getElementById( 'bb-rl-rsvp-cancel-link' );
		var gcalLinks     = document.querySelectorAll( '.bb-rl-cal-link--gcal' );
		var removeButtons = document.querySelectorAll( '.bb-rl-remove-attendee' );

		if ( btn ) {
			btn.addEventListener( 'click', handleRsvpClick );
		}

		if ( cancelLink ) {
			cancelLink.addEventListener( 'click', handleCancelClick );
		}

		gcalLinks.forEach( function( link ) {
			link.addEventListener( 'click', handleGcalClick );
		} );

		removeButtons.forEach( function( removeBtn ) {
			removeBtn.addEventListener( 'click', handleRemoveAttendee );
		} );
	}

	function handleRsvpClick( e ) {
		e.preventDefault();
		var btn   = e.currentTarget;
		var state = btn.dataset.state;
		if ( 'attending' === state || 'waitlisted' === state ) {
			doCancel( btn, cfg.currentUserId );
		} else {
			doRsvp( btn );
		}
	}

	function handleCancelClick( e ) {
		e.preventDefault();
		var btn = document.getElementById( 'bb-rl-rsvp-btn' );
		doCancel( btn, cfg.currentUserId );
	}

	function doRsvp( btn ) {
		btn.disabled = true;
		fetch( cfg.restUrl + '/' + cfg.eventId + '/rsvp', {
			method: 'POST',
			headers: { 'X-WP-Nonce': cfg.nonce, 'Content-Type': 'application/json' }
		} )
		.then( function( r ) { return r.json(); } )
		.then( function( data ) {
			if ( 'registered' === data.status ) {
				cfg.isAttending  = true;
				cfg.isWaitlisted = false;
				btn.textContent  = i18n.attending;
				btn.dataset.state = 'attending';
				btn.className    = 'bb-rl-btn bb-rl-btn--success';
			} else if ( 'waitlisted' === data.status ) {
				cfg.isAttending  = false;
				cfg.isWaitlisted = true;
				btn.textContent  = i18n.onWaitlist;
				btn.dataset.state = 'waitlisted';
				btn.className    = 'bb-rl-btn bb-rl-btn--secondary';
			}
			cfg.atCapacity = data.at_capacity;
		} )
		.catch( function() {
			alert( i18n.errorRsvp );
		} )
		.finally( function() { btn.disabled = false; } );
	}

	function doCancel( btn, userId ) {
		if ( btn ) {
			btn.disabled = true;
		}
		var url = cfg.restUrl + '/' + cfg.eventId + '/rsvp';
		if ( userId && userId !== cfg.currentUserId ) {
			url += '?user_id=' + userId;
		}
		fetch( url, {
			method: 'DELETE',
			headers: { 'X-WP-Nonce': cfg.nonce }
		} )
		.then( function( r ) { return r.json(); } )
		.then( function( data ) {
			if ( data.cancelled ) {
				cfg.isAttending  = false;
				cfg.isWaitlisted = false;
				if ( btn ) {
					btn.textContent   = cfg.atCapacity ? i18n.joinWaitlist : i18n.rsvp;
					btn.dataset.state = 'none';
					btn.className     = 'bb-rl-btn bb-rl-btn--primary';
				}
				// Hide cancel link.
				var cancelLink = document.getElementById( 'bb-rl-rsvp-cancel-link' );
				if ( cancelLink ) {
					cancelLink.parentElement.style.display = 'none';
				}
			}
		} )
		.catch( function() {
			alert( i18n.errorCancel );
		} )
		.finally( function() { if ( btn ) { btn.disabled = false; } } );
	}

	function handleGcalClick( e ) {
		e.preventDefault();
		var eventId = e.currentTarget.dataset.eventId || cfg.eventId;
		fetch( cfg.restUrl + '/' + eventId + '/gcal-url', {
			headers: { 'X-WP-Nonce': cfg.nonce }
		} )
		.then( function( r ) { return r.json(); } )
		.then( function( data ) {
			if ( data.url ) {
				window.open( data.url, '_blank' );
			}
		} )
		.catch( function() {
			alert( i18n.errorGcal );
		} );
	}

	function handleRemoveAttendee( e ) {
		e.preventDefault();
		var userId   = parseInt( e.currentTarget.dataset.userId, 10 );
		if ( ! userId ) {
			return;
		}
		if ( ! confirm( i18n.confirmRemove ) ) {
			return;
		}

		var listItem = e.currentTarget.closest( 'li' );
		fetch( cfg.restUrl + '/' + cfg.eventId + '/rsvp', {
			method: 'DELETE',
			headers: {
				'X-WP-Nonce': cfg.nonce,
				'Content-Type': 'application/json'
			},
			body: JSON.stringify( { user_id: userId } )
		} )
		.then( function( r ) { return r.json(); } )
		.then( function( data ) {
			if ( data.cancelled && listItem ) {
				listItem.remove();
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', init );
}() );

( function( $ ) {
	'use strict';

	var config   = window.bpEventsSingle || {};
	var groupId  = parseInt( config.groupId, 10 ) || 0;
	var eventId  = parseInt( config.eventId, 10 ) || 0;
	var nonce    = config.nonce || '';
	var selected = {};

	// Only activate the invite panel when a group context exists.
	// On the edit screen with a group event, groupId will be non-zero.
	if ( ! groupId || ! eventId ) { return; }

	var $panel  = $( '#bp-events-invite-panel' );
	var $list   = $( '#bp-events-invite-list' );
	var $search = $( '#bp-events-invite-search' );
	var $send   = $( '#bp-events-invite-send' );
	var $status = $( '#bp-events-invite-status' );
	var $count  = $( '#bp-events-invite-count' );

	// Panel is already visible — rendered server-side when group_id is set.
	if ( ! $panel.length ) { return; }

	// Fetch members from platform REST endpoint.
	function fetchMembers( searchTerm ) {
		var url = config.groupsRestUrl + '/' + groupId + '/members?per_page=50';
		if ( searchTerm ) { url += '&search=' + encodeURIComponent( searchTerm ); }
		$list.html( '<li class="bp-events-invite-loading">Loading&hellip;</li>' );
		fetch( url, { headers: { 'X-WP-Nonce': nonce } } )
			.then( function( r ) { return r.json(); } )
			.then( function( members ) {
				$list.empty();
				if ( ! members || ! members.length ) {
					$list.html( '<li>No members found.</li>' );
					return;
				}
				members.forEach( function( member ) {
					var id      = member.id;
					var name    = member.name || 'Member #' + id;
					var avatar  = ( member.avatar_urls && member.avatar_urls.thumb ) || '';
					var checked = selected[ id ] ? ' checked' : '';
					var $li = $( '<li class="bp-events-invite-member">' +
						'<label>' +
						'<input type="checkbox" value="' + id + '"' + checked + '> ' +
						( avatar ? '<img src="' + avatar + '" width="32" height="32" alt=""> ' : '' ) +
						$( '<span>' ).text( name ).html() +
						'</label></li>' );
					$list.append( $li );
				} );
			} )
			.catch( function() {
				$list.html( '<li>Could not load members.</li>' );
			} );
	}

	// Track checkbox selections.
	$list.on( 'change', 'input[type="checkbox"]', function() {
		var uid = parseInt( $( this ).val(), 10 );
		if ( $( this ).is( ':checked' ) ) {
			selected[ uid ] = true;
		} else {
			delete selected[ uid ];
		}
		var count = Object.keys( selected ).length;
		$count.text( count );
		$send.prop( 'disabled', count === 0 );
	} );

	// Search debounce.
	var searchTimer;
	$search.on( 'input', function() {
		clearTimeout( searchTimer );
		var term = $( this ).val();
		searchTimer = setTimeout( function() { fetchMembers( term ); }, 300 );
	} );

	// Send invites — eventId is known from server-side localization.
	$send.on( 'click', function() {
		var userIds = Object.keys( selected ).map( Number );
		$send.prop( 'disabled', true );
		fetch( config.restUrl + '/' + eventId + '/invite', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
			body: JSON.stringify( { user_ids: userIds } ),
		} )
		.then( function( r ) { return r.json(); } )
		.then( function() {
			$status.text( 'Invites sent.' );
			selected = {};
			$count.text( '0' );
		} )
		.catch( function() {
			$status.text( 'Could not send invites. Please try again.' );
			$send.prop( 'disabled', false );
		} );
	} );

	// Initial load.
	fetchMembers( '' );

} )( jQuery );
