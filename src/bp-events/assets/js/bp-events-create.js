/* global bpEventsCreate */
/**
 * BuddyBoss Events — Multi-step event creation wizard.
 *
 * Manages a state object, drives step transitions, conditional field
 * visibility, RRULE string construction, and REST API submission.
 * No external libraries required — plain JS, no FullCalendar dependency.
 *
 * @package BuddyBoss\Events
 * @since   BuddyBoss Events 1.0.0
 */
( function() {
	'use strict';

	// -------------------------------------------------------------------------
	// State
	// -------------------------------------------------------------------------

	var state = {
		step:            1,
		type:            'in-person', // 'in-person' | 'virtual' | 'hybrid'
		title:           '',
		description:     '',
		start_date:      '',           // ISO 8601 datetime string
		end_date:        '',
		timezone:        '',
		venue_name:      '',
		venue_address:   '',
		virtual_url:     '',
		virtual_type:    '',           // 'zoom' | 'meet' | 'other'
		capacity:        null,
		showRecurrence:  false,
		recurrence_rule: '',           // e.g. 'FREQ=WEEKLY;INTERVAL=1;BYDAY=MO,WE;COUNT=10'
		rsvp_restrict:   false,        // true when RSVP is restricted to a group
		rsvp_group_id:   0,            // BuddyPress group ID for restriction
		category_ids:    [],           // selected event category term IDs
		tags:            [],           // tag strings entered by the user
		status:          'draft'       // 'draft' | 'published'
	};

	// Recurrence UI sub-state (kept separate, assembled into state.recurrence_rule on change).
	var recurrenceUi = {
		freq:      'WEEKLY',
		interval:  1,
		bydays:    [],
		endType:   'COUNT', // 'COUNT' | 'UNTIL'
		count:     10,
		until:     ''
	};

	// -------------------------------------------------------------------------
	// Step helpers
	// -------------------------------------------------------------------------

	/**
	 * Total number of wizard steps.
	 * Fixed steps: 1 Type, 2 Basic Details, 3 Date & Time, 4 Location,
	 *              5 Recurrence (may be skipped), 6 RSVP Settings,
	 *              7 Categories & Tags, 8 Review.
	 * Step 5 (Recurrence) is skipped in navigation when showRecurrence is false,
	 * but totalSteps always reflects visible steps for the indicator strip.
	 *
	 * @returns {number}
	 */
	function totalSteps() {
		return state.showRecurrence ? 8 : 7;
	}

	/**
	 * Logical step number → display step label mapping.
	 * When recurrence is hidden the indicator strip only shows 7 steps, with
	 * what would be step 6 displayed as position 5, step 7 as position 6,
	 * and step 8 as position 7.
	 *
	 * @param {number} logicalStep  Display position (1-based index in indicator).
	 * @returns {string}
	 */
	function stepLabel( logicalStep ) {
		if ( state.showRecurrence ) {
			// All 8 steps visible.
			var labelsAll = [
				'Event Type',
				'Basic Details',
				'Date & Time',
				'Location / Virtual',
				'Recurrence',
				'RSVP Settings',
				'Categories & Tags',
				'Review & Publish'
			];
			return labelsAll[ logicalStep - 1 ] || '';
		}
		// 7 steps visible (recurrence hidden).
		var labelsNo = [
			'Event Type',
			'Basic Details',
			'Date & Time',
			'Location / Virtual',
			'RSVP Settings',
			'Categories & Tags',
			'Review & Publish'
		];
		return labelsNo[ logicalStep - 1 ] || '';
	}

	// -------------------------------------------------------------------------
	// RRULE builder
	// -------------------------------------------------------------------------

	/**
	 * Rebuild state.recurrence_rule from recurrenceUi sub-state.
	 */
	function buildRrule() {
		var rule = 'FREQ=' + recurrenceUi.freq + ';INTERVAL=' + recurrenceUi.interval;

		if ( recurrenceUi.freq === 'WEEKLY' && recurrenceUi.bydays.length > 0 ) {
			rule += ';BYDAY=' + recurrenceUi.bydays.join( ',' );
		}

		if ( recurrenceUi.endType === 'COUNT' ) {
			rule += ';COUNT=' + recurrenceUi.count;
		} else if ( recurrenceUi.endType === 'UNTIL' && recurrenceUi.until ) {
			// RRULE UNTIL must be YYYYMMDDTHHMMSSZ format; convert from date input value.
			var until = recurrenceUi.until.replace( /-/g, '' );
			rule += ';UNTIL=' + until + 'T000000Z';
		}

		state.recurrence_rule = rule;
	}

	// -------------------------------------------------------------------------
	// Timezone list
	// -------------------------------------------------------------------------

	/**
	 * Return a curated list of IANA timezone strings for the select element.
	 *
	 * @returns {string[]}
	 */
	function timezoneList() {
		return [
			'Pacific/Midway',
			'Pacific/Honolulu',
			'America/Anchorage',
			'America/Los_Angeles',
			'America/Denver',
			'America/Chicago',
			'America/New_York',
			'America/Halifax',
			'America/Sao_Paulo',
			'Atlantic/Azores',
			'Europe/London',
			'Europe/Paris',
			'Europe/Helsinki',
			'Europe/Moscow',
			'Asia/Dubai',
			'Asia/Karachi',
			'Asia/Kolkata',
			'Asia/Dhaka',
			'Asia/Bangkok',
			'Asia/Singapore',
			'Asia/Tokyo',
			'Australia/Sydney',
			'Pacific/Auckland'
		];
	}

	/**
	 * Detect the browser's current timezone.
	 *
	 * @returns {string}
	 */
	function browserTimezone() {
		if ( window.Intl && Intl.DateTimeFormat ) {
			try {
				return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
			} catch ( e ) {
				return 'UTC';
			}
		}
		return 'UTC';
	}

	// -------------------------------------------------------------------------
	// HTML helpers
	// -------------------------------------------------------------------------

	/**
	 * Escape HTML entities in a string for safe attribute/text output.
	 *
	 * @param {string} str
	 * @returns {string}
	 */
	function esc( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	// -------------------------------------------------------------------------
	// Step renderers
	// -------------------------------------------------------------------------

	/**
	 * Render step 1 — Event Type.
	 *
	 * @returns {string} HTML string.
	 */
	function renderStep1() {
		var types = [
			{ value: 'in-person', label: 'In-Person' },
			{ value: 'virtual',   label: 'Virtual'   },
			{ value: 'hybrid',    label: 'Hybrid'    }
		];

		var html = '<div class="bb-rl-wizard-step bb-rl-step-1">';
		html += '<h2 class="bb-rl-step-title">Event Type</h2>';
		html += '<p class="bb-rl-step-desc">How will attendees participate?</p>';
		html += '<div class="bb-rl-event-type-options">';

		types.forEach( function( t ) {
			var checked = ( state.type === t.value ) ? ' checked' : '';
			html += '<label class="bb-rl-event-type-label">';
			html += '<input type="radio" name="bb-rl-event-type" value="' + esc( t.value ) + '"' + checked + '> ';
			html += esc( t.label );
			html += '</label>';
		} );

		html += '</div>';
		html += '</div>';
		return html;
	}

	/**
	 * Render step 2 — Basic Details.
	 *
	 * @returns {string} HTML string.
	 */
	function renderStep2() {
		var html = '<div class="bb-rl-wizard-step bb-rl-step-2">';
		html += '<h2 class="bb-rl-step-title">Basic Details</h2>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-event-title">Event Title <span class="bb-rl-required">*</span></label>';
		html += '<input type="text" id="bb-rl-event-title" class="bb-rl-input" value="' + esc( state.title ) + '" required>';
		html += '</div>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-event-description">Description</label>';
		html += '<textarea id="bb-rl-event-description" class="bb-rl-textarea" rows="6">' + esc( state.description ) + '</textarea>';
		html += '</div>';

		html += '</div>';
		return html;
	}

	/**
	 * Render step 3 — Date & Time.
	 *
	 * @returns {string} HTML string.
	 */
	function renderStep3() {
		var detectedTz = browserTimezone();
		var currentTz  = state.timezone || detectedTz;
		var tzList     = timezoneList();

		// Ensure detected tz is in list.
		if ( tzList.indexOf( detectedTz ) === -1 ) {
			tzList.unshift( detectedTz );
		}

		var tzOptions = tzList.map( function( tz ) {
			var sel = ( tz === currentTz ) ? ' selected' : '';
			return '<option value="' + esc( tz ) + '"' + sel + '>' + esc( tz ) + '</option>';
		} ).join( '' );

		var recChecked = state.showRecurrence ? ' checked' : '';

		var html = '<div class="bb-rl-wizard-step bb-rl-step-3">';
		html += '<h2 class="bb-rl-step-title">Date & Time</h2>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-start-date">Start Date & Time <span class="bb-rl-required">*</span></label>';
		html += '<input type="datetime-local" id="bb-rl-start-date" class="bb-rl-input" value="' + esc( state.start_date ) + '">';
		html += '</div>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-end-date">End Date & Time</label>';
		html += '<input type="datetime-local" id="bb-rl-end-date" class="bb-rl-input" value="' + esc( state.end_date ) + '">';
		html += '</div>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-timezone">Timezone</label>';
		html += '<select id="bb-rl-timezone" class="bb-rl-select">' + tzOptions + '</select>';
		html += '</div>';

		html += '<div class="bb-rl-field bb-rl-recurrence-toggle">';
		html += '<label>';
		html += '<input type="checkbox" id="bb-rl-show-recurrence"' + recChecked + '> ';
		html += 'Make this a recurring event';
		html += '</label>';
		html += '</div>';

		html += '</div>';
		return html;
	}

	/**
	 * Render step 4 — Location / Virtual.
	 * Fields shown depend on state.type (in-person | virtual | hybrid).
	 *
	 * @returns {string} HTML string.
	 */
	function renderStep4() {
		var showInPerson = ( state.type === 'in-person' || state.type === 'hybrid' );
		var showVirtual  = ( state.type === 'virtual'   || state.type === 'hybrid' );

		var html = '<div class="bb-rl-wizard-step bb-rl-step-4">';
		html += '<h2 class="bb-rl-step-title">Location / Virtual</h2>';

		if ( showInPerson ) {
			html += '<div class="bb-rl-in-person-fields">';
			html += '<h3 class="bb-rl-fields-subheading">Venue Details</h3>';

			html += '<div class="bb-rl-field">';
			html += '<label for="bb-rl-venue-name">Venue Name</label>';
			html += '<input type="text" id="bb-rl-venue-name" class="bb-rl-input" value="' + esc( state.venue_name ) + '">';
			html += '</div>';

			html += '<div class="bb-rl-field">';
			html += '<label for="bb-rl-venue-address">Venue Address</label>';
			html += '<input type="text" id="bb-rl-venue-address" class="bb-rl-input" value="' + esc( state.venue_address ) + '">';
			html += '</div>';

			html += '<div class="bb-rl-field">';
			html += '<label for="bb-rl-capacity">Capacity</label>';
			html += '<input type="number" id="bb-rl-capacity" class="bb-rl-input" min="1" value="' + esc( state.capacity !== null ? state.capacity : '' ) + '">';
			html += '</div>';

			html += '</div>';
		}

		if ( showVirtual ) {
			html += '<div class="bb-rl-virtual-fields">';
			html += '<h3 class="bb-rl-fields-subheading">Virtual Details</h3>';

			html += '<div class="bb-rl-field">';
			html += '<label for="bb-rl-virtual-url">Meeting URL</label>';
			html += '<input type="url" id="bb-rl-virtual-url" class="bb-rl-input" placeholder="https://" value="' + esc( state.virtual_url ) + '">';
			html += '</div>';

			var vtOptions = [
				{ value: '',      label: '-- Select Type --' },
				{ value: 'zoom',  label: 'Zoom'              },
				{ value: 'meet',  label: 'Google Meet'       },
				{ value: 'other', label: 'Other'             }
			].map( function( opt ) {
				var sel = ( state.virtual_type === opt.value ) ? ' selected' : '';
				return '<option value="' + esc( opt.value ) + '"' + sel + '>' + esc( opt.label ) + '</option>';
			} ).join( '' );

			html += '<div class="bb-rl-field">';
			html += '<label for="bb-rl-virtual-type">Platform</label>';
			html += '<select id="bb-rl-virtual-type" class="bb-rl-select">' + vtOptions + '</select>';
			html += '</div>';

			html += '</div>';
		}

		html += '</div>';
		return html;
	}

	/**
	 * Render step 5 — Recurrence (conditional, only when state.showRecurrence is true).
	 *
	 * @returns {string} HTML string.
	 */
	function renderStep5() {
		var freqOptions = [
			{ value: 'DAILY',   label: 'Daily'   },
			{ value: 'WEEKLY',  label: 'Weekly'  },
			{ value: 'MONTHLY', label: 'Monthly' }
		].map( function( opt ) {
			var sel = ( recurrenceUi.freq === opt.value ) ? ' selected' : '';
			return '<option value="' + esc( opt.value ) + '"' + sel + '>' + esc( opt.label ) + '</option>';
		} ).join( '' );

		var weekdays = [
			{ value: 'MO', label: 'Mon' },
			{ value: 'TU', label: 'Tue' },
			{ value: 'WE', label: 'Wed' },
			{ value: 'TH', label: 'Thu' },
			{ value: 'FR', label: 'Fri' },
			{ value: 'SA', label: 'Sat' },
			{ value: 'SU', label: 'Sun' }
		];

		var weekdayChecks = weekdays.map( function( d ) {
			var checked = ( recurrenceUi.bydays.indexOf( d.value ) !== -1 ) ? ' checked' : '';
			return '<label class="bb-rl-day-label">' +
				'<input type="checkbox" class="bb-rl-byday" value="' + esc( d.value ) + '"' + checked + '> ' +
				esc( d.label ) +
				'</label>';
		} ).join( '' );

		var bydayDisplay = ( recurrenceUi.freq === 'WEEKLY' ) ? '' : ' style="display:none"';

		var countChecked = ( recurrenceUi.endType === 'COUNT' ) ? ' checked' : '';
		var untilChecked = ( recurrenceUi.endType === 'UNTIL' ) ? ' checked' : '';

		var html = '<div class="bb-rl-wizard-step bb-rl-step-5">';
		html += '<h2 class="bb-rl-step-title">Recurrence</h2>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-freq">Repeats</label>';
		html += '<select id="bb-rl-freq" class="bb-rl-select">' + freqOptions + '</select>';
		html += '</div>';

		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-interval">Every</label>';
		html += '<input type="number" id="bb-rl-interval" class="bb-rl-input bb-rl-input-narrow" min="1" value="' + esc( recurrenceUi.interval ) + '">';
		html += '<span class="bb-rl-interval-label" id="bb-rl-interval-label"> ' + recurrenceUi.freq.toLowerCase() + '(s)</span>';
		html += '</div>';

		html += '<div class="bb-rl-field bb-rl-byday-field"' + bydayDisplay + '>';
		html += '<label>On days</label>';
		html += '<div class="bb-rl-day-checkboxes">' + weekdayChecks + '</div>';
		html += '</div>';

		html += '<div class="bb-rl-field">';
		html += '<label>Ends</label>';
		html += '<div class="bb-rl-end-options">';
		html += '<label class="bb-rl-end-label"><input type="radio" name="bb-rl-end-type" value="COUNT"' + countChecked + '> After';
		html += ' <input type="number" id="bb-rl-count" class="bb-rl-input bb-rl-input-narrow" min="1" value="' + esc( recurrenceUi.count ) + '"> occurrences</label>';
		html += '<label class="bb-rl-end-label"><input type="radio" name="bb-rl-end-type" value="UNTIL"' + untilChecked + '> Until';
		html += ' <input type="date" id="bb-rl-until" class="bb-rl-input" value="' + esc( recurrenceUi.until ) + '"></label>';
		html += '</div>';
		html += '</div>';

		html += '</div>';
		return html;
	}

	/**
	 * Render the RSVP Settings step.
	 *
	 * @returns {string} HTML string.
	 */
	function renderRsvpSettingsStep() {
		var restrictChecked = state.rsvp_restrict ? ' checked' : '';
		var selectorDisplay = state.rsvp_restrict ? '' : ' style="display:none;"';

		var html = '<div class="bb-rl-wizard-step bb-rl-step-rsvp-settings" data-step="rsvp-settings">';
		html += '<h2 class="bb-rl-step-title">RSVP Settings</h2>';

		html += '<div class="bb-rl-field">';
		html += '<label>';
		html += '<input type="checkbox" id="bb-rl-rsvp-restrict" name="rsvp_restrict"' + restrictChecked + '> ';
		html += 'Restrict RSVP to group members';
		html += '</label>';
		html += '</div>';

		html += '<div id="bb-rl-rsvp-group-selector"' + selectorDisplay + '>';
		html += '<div class="bb-rl-field">';
		html += '<label for="bb-rl-rsvp-group-search">Search groups</label>';
		html += '<input type="text" id="bb-rl-rsvp-group-search" class="bb-rl-input" placeholder="Type to search groups..." autocomplete="off" value="">';
		html += '</div>';
		html += '<ul id="bb-rl-rsvp-group-results" class="bb-rl-wizard-group-results"></ul>';
		html += '<input type="hidden" id="bb-rl-rsvp-group-id" name="rsvp_group_id" value="' + esc( state.rsvp_group_id > 0 ? state.rsvp_group_id : '' ) + '">';
		if ( state.rsvp_group_id > 0 ) {
			html += '<span id="bb-rl-rsvp-group-selected" class="bb-rl-wizard-selected-group">' + esc( state._rsvp_group_name || '' ) + '</span>';
		} else {
			html += '<span id="bb-rl-rsvp-group-selected" class="bb-rl-wizard-selected-group"></span>';
		}
		html += '</div>';

		html += '</div>';
		return html;
	}

	/**
	 * Render step 7 — Categories & Tags.
	 *
	 * Renders category checkboxes (from bpEventsCreate.categories) with
	 * hierarchical indentation, plus a tokenised tag input.
	 *
	 * @returns {string} HTML string.
	 */
	function renderCategoriesStep() {
		var categories = ( window.bpEventsCreate && bpEventsCreate.categories )
			? bpEventsCreate.categories
			: [];

		var html = '<div class="bb-rl-wizard-step bb-rl-step-categories">';
		html += '<h2 class="bb-rl-step-title">Categories &amp; Tags</h2>';

		// Category checkboxes.
		html += '<div class="bb-rl-form-group">';
		html += '<label class="bb-rl-field-label">Event Categories</label>';
		html += '<div class="bb-rl-category-list" id="bb-event-categories">';

		if ( categories.length ) {
			categories.forEach( function( cat ) {
				var checked  = ( state.category_ids.indexOf( cat.id ) !== -1 ) ? ' checked' : '';
				var indent   = cat.parent ? ' style="margin-left:20px;"' : '';
				var iconHtml = '';
				if ( cat.meta && cat.meta._bb_event_cat_icon_id ) {
					// Icon ID is present — would need attachment URL lookup; skip for now.
				}
				html += '<label class="bb-rl-category-item"' + indent + '>';
				html += '<input type="checkbox" class="bb-rl-category-check" value="' + esc( cat.id ) + '"' + checked + '> ';
				html += esc( cat.name );
				html += '</label>';
			} );
		} else {
			html += '<p class="bb-rl-notice">';
			html += 'No categories available. You can add categories from the admin dashboard.';
			html += '</p>';
		}

		html += '</div>'; // .bb-rl-category-list
		html += '</div>'; // .bb-rl-form-group

		// Tag input.
		html += '<div class="bb-rl-form-group">';
		html += '<label class="bb-rl-field-label">Tags</label>';
		html += '<div class="bb-rl-tag-input-wrap">';
		html += '<div class="bb-rl-tag-tokens" id="bb-event-tag-tokens">';

		state.tags.forEach( function( tag ) {
			html += '<span class="bb-rl-tag-token">' + esc( tag );
			html += '<button type="button" class="bb-rl-tag-remove" data-tag="' + esc( tag ) + '">&times;</button>';
			html += '</span>';
		} );

		html += '</div>'; // .bb-rl-tag-tokens
		html += '<input type="text" id="bb-event-tags-input" class="bb-rl-input"';
		html += ' placeholder="Type and press comma or Enter to add tags" />';
		html += '</div>'; // .bb-rl-tag-input-wrap
		html += '</div>'; // .bb-rl-form-group

		html += '</div>';
		return html;
	}

	/**
	 * Add a tag string to state.tags if not already present.
	 *
	 * @param {string} tagStr
	 */
	function addTag( tagStr ) {
		var trimmed = tagStr.trim().replace( /,+$/, '' ).trim();
		if ( trimmed && state.tags.indexOf( trimmed ) === -1 ) {
			state.tags.push( trimmed );
		}
	}

	/**
	 * Render the review step (step 8).
	 *
	 * @returns {string} HTML string.
	 */
	function renderReviewStep() {
		var typeLabels = { 'in-person': 'In-Person', 'virtual': 'Virtual', 'hybrid': 'Hybrid' };

		var rows = [
			[ 'Event Type',    typeLabels[ state.type ] || state.type ],
			[ 'Title',         state.title    || '—' ],
			[ 'Description',   state.description || '—' ],
			[ 'Start',         state.start_date  || '—' ],
			[ 'End',           state.end_date    || '—' ],
			[ 'Timezone',      state.timezone    || '—' ]
		];

		if ( state.type === 'in-person' || state.type === 'hybrid' ) {
			rows.push( [ 'Venue Name',    state.venue_name    || '—' ] );
			rows.push( [ 'Venue Address', state.venue_address || '—' ] );
			if ( state.capacity !== null ) {
				rows.push( [ 'Capacity', state.capacity ] );
			}
		}

		if ( state.type === 'virtual' || state.type === 'hybrid' ) {
			rows.push( [ 'Meeting URL',  state.virtual_url  || '—' ] );
			rows.push( [ 'Platform',     state.virtual_type || '—' ] );
		}

		if ( state.showRecurrence && state.recurrence_rule ) {
			rows.push( [ 'Recurrence', state.recurrence_rule ] );
		}

		if ( state.rsvp_restrict && state.rsvp_group_id > 0 ) {
			rows.push( [ 'RSVP Restriction', 'Group members only (Group ID: ' + state.rsvp_group_id + ')' ] );
		}

		// Categories.
		if ( state.category_ids.length > 0 ) {
			var categories = ( window.bpEventsCreate && bpEventsCreate.categories )
				? bpEventsCreate.categories
				: [];
			var selectedNames = state.category_ids.map( function( id ) {
				var match = categories.filter( function( c ) { return c.id === id; } );
				return match.length ? match[0].name : 'ID: ' + id;
			} );
			rows.push( [ 'Categories', selectedNames.join( ', ' ) ] );
		}

		// Tags.
		if ( state.tags.length > 0 ) {
			rows.push( [ 'Tags', state.tags.join( ', ' ) ] );
		}

		var tableRows = rows.map( function( row ) {
			return '<tr><th class="bb-rl-review-label">' + esc( row[0] ) + '</th>' +
				'<td class="bb-rl-review-value">' + esc( row[1] ) + '</td></tr>';
		} ).join( '' );

		var html = '<div class="bb-rl-wizard-step bb-rl-step-review">';
		html += '<h2 class="bb-rl-step-title">Review & Publish</h2>';
		html += '<table class="bb-rl-review-table">' + tableRows + '</table>';
		html += '</div>';
		return html;
	}

	// -------------------------------------------------------------------------
	// Step indicators
	// -------------------------------------------------------------------------

	/**
	 * Re-render the step indicator strip.
	 *
	 * The indicator always shows totalSteps() positions. When recurrence is
	 * hidden, logical steps 1–4, 6, 7 are mapped to display positions 1–6.
	 */
	function renderStepIndicators() {
		var container = document.getElementById( 'bb-rl-wizard-steps' );
		if ( ! container ) {
			return;
		}

		// Build the ordered list of logical step numbers that are visible.
		var visibleSteps;
		if ( state.showRecurrence ) {
			visibleSteps = [ 1, 2, 3, 4, 5, 6, 7, 8 ];
		} else {
			visibleSteps = [ 1, 2, 3, 4, 6, 7, 8 ]; // Skip step 5 (Recurrence).
		}

		var html = '';

		for ( var idx = 0; idx < visibleSteps.length; idx++ ) {
			var logicalStep  = visibleSteps[ idx ];
			var displayPos   = idx + 1;
			var cls = 'bb-rl-step-indicator';
			if ( logicalStep === state.step ) {
				cls += ' is-active';
			} else if ( logicalStep < state.step ) {
				cls += ' is-complete';
			}
			html += '<span class="' + cls + '" data-step="' + logicalStep + '">';
			html += '<span class="bb-rl-step-num">' + displayPos + '</span>';
			html += '<span class="bb-rl-step-label">' + esc( stepLabel( displayPos ) ) + '</span>';
			html += '</span>';
		}

		container.innerHTML = html;
	}

	// -------------------------------------------------------------------------
	// Navigation buttons
	// -------------------------------------------------------------------------

	/**
	 * Update the visibility of wizard navigation buttons for the current step.
	 */
	function updateNavButtons() {
		var prev    = document.getElementById( 'bb-rl-wizard-prev' );
		var next    = document.getElementById( 'bb-rl-wizard-next' );
		var draft   = document.getElementById( 'bb-rl-wizard-draft' );
		var publish = document.getElementById( 'bb-rl-wizard-publish' );

		if ( ! prev || ! next || ! draft || ! publish ) {
			return;
		}

		var isFirst  = ( state.step === 1 );
		var isReview = ( state.step === 8 ); // Step 8 is always Review & Publish.

		prev.style.display    = isFirst  ? 'none'         : 'inline-block';
		next.style.display    = isReview ? 'none'         : 'inline-block';
		draft.style.display   = isReview ? 'inline-block' : 'none';
		publish.style.display = isReview ? 'inline-block' : 'none';
	}

	// -------------------------------------------------------------------------
	// Main render
	// -------------------------------------------------------------------------

	/**
	 * Render the content area for a given logical step and bind its events.
	 *
	 * @param {number} stepNumber
	 */
	function renderStep( stepNumber ) {
		state.step = stepNumber;

		var content = document.getElementById( 'bb-rl-wizard-content' );
		if ( ! content ) {
			return;
		}

		var html = '';

		if ( stepNumber === 1 ) {
			html = renderStep1();
		} else if ( stepNumber === 2 ) {
			html = renderStep2();
		} else if ( stepNumber === 3 ) {
			html = renderStep3();
		} else if ( stepNumber === 4 ) {
			html = renderStep4();
		} else if ( stepNumber === 5 ) {
			// Step 5 is Recurrence — only reached when showRecurrence is true.
			html = renderStep5();
		} else if ( stepNumber === 6 ) {
			// Step 6 is always RSVP Settings.
			html = renderRsvpSettingsStep();
		} else if ( stepNumber === 7 ) {
			// Step 7 is always Categories & Tags.
			html = renderCategoriesStep();
		} else if ( stepNumber === 8 ) {
			// Step 8 is always Review & Publish.
			html = renderReviewStep();
		}

		content.innerHTML = html;
		bindStepEvents( stepNumber );
		renderStepIndicators();
		updateNavButtons();

		// Scroll form top into view.
		var form = document.getElementById( 'bb-rl-event-create-form' );
		if ( form ) {
			form.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	}

	// -------------------------------------------------------------------------
	// Step event binding
	// -------------------------------------------------------------------------

	/**
	 * Bind input/change listeners for the currently rendered step.
	 *
	 * @param {number} stepNumber
	 */
	function bindStepEvents( stepNumber ) {
		if ( stepNumber === 1 ) {
			bindStep1Events();
		} else if ( stepNumber === 2 ) {
			bindStep2Events();
		} else if ( stepNumber === 3 ) {
			bindStep3Events();
		} else if ( stepNumber === 4 ) {
			bindStep4Events();
		} else if ( stepNumber === 5 ) {
			// Step 5 = Recurrence (only navigated to when showRecurrence is true).
			bindStep5Events();
		} else if ( stepNumber === 6 ) {
			// Step 6 = RSVP Settings.
			bindRsvpSettingsEvents();
		} else if ( stepNumber === 7 ) {
			// Step 7 = Categories & Tags.
			bindCategoriesStepEvents();
		}
	}

	function bindStep1Events() {
		var radios = document.querySelectorAll( 'input[name="bb-rl-event-type"]' );
		radios.forEach( function( radio ) {
			radio.addEventListener( 'change', function() {
				state.type = this.value;
			} );
		} );
	}

	function bindStep2Events() {
		var titleInput = document.getElementById( 'bb-rl-event-title' );
		var descInput  = document.getElementById( 'bb-rl-event-description' );

		if ( titleInput ) {
			titleInput.addEventListener( 'input', function() {
				state.title = this.value;
			} );
		}
		if ( descInput ) {
			descInput.addEventListener( 'input', function() {
				state.description = this.value;
			} );
		}
	}

	function bindStep3Events() {
		var startInput = document.getElementById( 'bb-rl-start-date' );
		var endInput   = document.getElementById( 'bb-rl-end-date' );
		var tzSelect   = document.getElementById( 'bb-rl-timezone' );
		var recCheck   = document.getElementById( 'bb-rl-show-recurrence' );

		if ( startInput ) {
			startInput.addEventListener( 'change', function() {
				// Convert datetime-local value to ISO 8601 string.
				state.start_date = this.value ? new Date( this.value ).toISOString() : '';
			} );
		}
		if ( endInput ) {
			endInput.addEventListener( 'change', function() {
				state.end_date = this.value ? new Date( this.value ).toISOString() : '';
			} );
		}
		if ( tzSelect ) {
			tzSelect.addEventListener( 'change', function() {
				state.timezone = this.value;
			} );
			// Initialise timezone state if empty.
			if ( ! state.timezone ) {
				state.timezone = tzSelect.value;
			}
		}
		if ( recCheck ) {
			recCheck.addEventListener( 'change', function() {
				state.showRecurrence = this.checked;
			} );
		}
	}

	function bindStep4Events() {
		var venueName    = document.getElementById( 'bb-rl-venue-name' );
		var venueAddress = document.getElementById( 'bb-rl-venue-address' );
		var capacity     = document.getElementById( 'bb-rl-capacity' );
		var virtualUrl   = document.getElementById( 'bb-rl-virtual-url' );
		var virtualType  = document.getElementById( 'bb-rl-virtual-type' );

		if ( venueName ) {
			venueName.addEventListener( 'input', function() { state.venue_name = this.value; } );
		}
		if ( venueAddress ) {
			venueAddress.addEventListener( 'input', function() { state.venue_address = this.value; } );
		}
		if ( capacity ) {
			capacity.addEventListener( 'input', function() {
				state.capacity = this.value !== '' ? parseInt( this.value, 10 ) : null;
			} );
		}
		if ( virtualUrl ) {
			virtualUrl.addEventListener( 'input', function() { state.virtual_url = this.value; } );
		}
		if ( virtualType ) {
			virtualType.addEventListener( 'change', function() { state.virtual_type = this.value; } );
		}
	}

	function bindStep5Events() {
		var freqSel  = document.getElementById( 'bb-rl-freq' );
		var interval = document.getElementById( 'bb-rl-interval' );
		var count    = document.getElementById( 'bb-rl-count' );
		var until    = document.getElementById( 'bb-rl-until' );
		var bydayField  = document.querySelector( '.bb-rl-byday-field' );
		var intervalLbl = document.getElementById( 'bb-rl-interval-label' );

		function syncRrule() {
			buildRrule();
		}

		if ( freqSel ) {
			freqSel.addEventListener( 'change', function() {
				recurrenceUi.freq = this.value;
				// Show/hide weekday checkboxes.
				if ( bydayField ) {
					bydayField.style.display = ( this.value === 'WEEKLY' ) ? '' : 'none';
				}
				if ( intervalLbl ) {
					intervalLbl.textContent = ' ' + this.value.toLowerCase() + '(s)';
				}
				syncRrule();
			} );
		}

		if ( interval ) {
			interval.addEventListener( 'input', function() {
				recurrenceUi.interval = parseInt( this.value, 10 ) || 1;
				syncRrule();
			} );
		}

		// Weekday checkboxes.
		var bydayBoxes = document.querySelectorAll( '.bb-rl-byday' );
		bydayBoxes.forEach( function( box ) {
			box.addEventListener( 'change', function() {
				var val = this.value;
				var idx = recurrenceUi.bydays.indexOf( val );
				if ( this.checked && idx === -1 ) {
					recurrenceUi.bydays.push( val );
				} else if ( ! this.checked && idx !== -1 ) {
					recurrenceUi.bydays.splice( idx, 1 );
				}
				syncRrule();
			} );
		} );

		// End condition radios.
		var endRadios = document.querySelectorAll( 'input[name="bb-rl-end-type"]' );
		endRadios.forEach( function( radio ) {
			radio.addEventListener( 'change', function() {
				recurrenceUi.endType = this.value;
				syncRrule();
			} );
		} );

		if ( count ) {
			count.addEventListener( 'input', function() {
				recurrenceUi.count = parseInt( this.value, 10 ) || 1;
				syncRrule();
			} );
		}

		if ( until ) {
			until.addEventListener( 'change', function() {
				recurrenceUi.until = this.value;
				syncRrule();
			} );
		}

		// Build initial RRULE.
		syncRrule();
	}

	/**
	 * Bind events for the RSVP Settings step.
	 *
	 * - Checkbox shows/hides the group selector.
	 * - Text input triggers debounced group search via BuddyBoss REST groups endpoint.
	 * - Clicking a result selects the group and populates the hidden input.
	 */
	function bindRsvpSettingsEvents() {
		var restrictCheck = document.getElementById( 'bb-rl-rsvp-restrict' );
		var groupSelector = document.getElementById( 'bb-rl-rsvp-group-selector' );
		var groupSearch   = document.getElementById( 'bb-rl-rsvp-group-search' );
		var groupResults  = document.getElementById( 'bb-rl-rsvp-group-results' );
		var groupIdInput  = document.getElementById( 'bb-rl-rsvp-group-id' );
		var groupSelected = document.getElementById( 'bb-rl-rsvp-group-selected' );

		if ( restrictCheck ) {
			restrictCheck.addEventListener( 'change', function() {
				state.rsvp_restrict = this.checked;
				if ( groupSelector ) {
					groupSelector.style.display = this.checked ? '' : 'none';
				}
				// Clear group selection when unchecked.
				if ( ! this.checked ) {
					state.rsvp_group_id = 0;
					if ( groupIdInput )  { groupIdInput.value = ''; }
					if ( groupSelected ) { groupSelected.textContent = ''; }
					if ( groupResults )  { groupResults.innerHTML = ''; }
				}
			} );
		}

		var searchTimer = null;

		if ( groupSearch ) {
			groupSearch.addEventListener( 'input', function() {
				var term = this.value;
				clearTimeout( searchTimer );

				if ( ! term || term.length < 2 ) {
					if ( groupResults ) { groupResults.innerHTML = ''; }
					return;
				}

				searchTimer = setTimeout( function() {
					if ( ! window.bpEventsCreate || ! bpEventsCreate.restUrl ) {
						return;
					}

					var groupsUrl = bpEventsCreate.restUrl.replace( /\/events$/, '/groups' );
					var fetchUrl  = groupsUrl + '?search=' + encodeURIComponent( term ) + '&per_page=10';

					fetch( fetchUrl, {
						headers: { 'X-WP-Nonce': bpEventsCreate.nonce }
					} )
					.then( function( response ) { return response.json(); } )
					.then( function( groups ) {
						if ( ! groupResults ) { return; }
						groupResults.innerHTML = '';

						if ( ! groups || ! groups.length ) {
							var noResult = document.createElement( 'li' );
							noResult.className = 'bb-rl-group-result-none';
							noResult.textContent = 'No groups found.';
							groupResults.appendChild( noResult );
							return;
						}

						groups.forEach( function( group ) {
							var li = document.createElement( 'li' );
							li.className = 'bb-rl-group-result-item';
							li.textContent = group.name;
							li.setAttribute( 'data-group-id', group.id );
							li.addEventListener( 'click', function() {
								state.rsvp_group_id   = parseInt( group.id, 10 );
								state._rsvp_group_name = group.name;
								if ( groupIdInput )  { groupIdInput.value = group.id; }
								if ( groupSelected ) { groupSelected.textContent = group.name; }
								if ( groupSearch )   { groupSearch.value = ''; }
								groupResults.innerHTML = '';
							} );
							groupResults.appendChild( li );
						} );
					} )
					.catch( function() {
						// Silently ignore search errors.
						if ( groupResults ) { groupResults.innerHTML = ''; }
					} );
				}, 300 );
			} );
		}
	}

	/**
	 * Bind events for the Categories & Tags step.
	 *
	 * - Category checkboxes update state.category_ids.
	 * - Tag input: comma or Enter converts the typed text into a tag token.
	 * - Tag remove buttons delete tokens from state.tags and re-render tokens.
	 */
	function bindCategoriesStepEvents() {
		// Category checkboxes.
		var categoryChecks = document.querySelectorAll( '.bb-rl-category-check' );
		categoryChecks.forEach( function( chk ) {
			chk.addEventListener( 'change', function() {
				var id  = parseInt( this.value, 10 );
				var idx = state.category_ids.indexOf( id );
				if ( this.checked && idx === -1 ) {
					state.category_ids.push( id );
				} else if ( ! this.checked && idx !== -1 ) {
					state.category_ids.splice( idx, 1 );
				}
			} );
		} );

		// Tag input.
		var tagInput  = document.getElementById( 'bb-event-tags-input' );
		var tagTokens = document.getElementById( 'bb-event-tag-tokens' );

		function renderTagTokens() {
			if ( ! tagTokens ) {
				return;
			}
			tagTokens.innerHTML = '';
			state.tags.forEach( function( tag ) {
				var span = document.createElement( 'span' );
				span.className = 'bb-rl-tag-token';

				var text = document.createTextNode( tag );
				span.appendChild( text );

				var btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'bb-rl-tag-remove';
				btn.setAttribute( 'data-tag', tag );
				btn.innerHTML = '&times;';
				btn.addEventListener( 'click', function() {
					var removeTag = this.getAttribute( 'data-tag' );
					state.tags = state.tags.filter( function( t ) { return t !== removeTag; } );
					renderTagTokens();
				} );

				span.appendChild( btn );
				tagTokens.appendChild( span );
			} );
		}

		if ( tagInput ) {
			tagInput.addEventListener( 'keydown', function( e ) {
				if ( e.key === 'Enter' || e.key === ',' ) {
					e.preventDefault();
					addTag( this.value );
					this.value = '';
					renderTagTokens();
				}
			} );

			tagInput.addEventListener( 'blur', function() {
				if ( this.value.trim() ) {
					addTag( this.value );
					this.value = '';
					renderTagTokens();
				}
			} );
		}

		// Wire remove buttons for any tokens already in the DOM (pre-rendered state).
		if ( tagTokens ) {
			tagTokens.querySelectorAll( '.bb-rl-tag-remove' ).forEach( function( btn ) {
				btn.addEventListener( 'click', function() {
					var removeTag = this.getAttribute( 'data-tag' );
					state.tags = state.tags.filter( function( t ) { return t !== removeTag; } );
					renderTagTokens();
				} );
			} );
		}
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------

	/**
	 * Validate the current step before allowing advancement.
	 * Returns true if valid, false otherwise (shows inline error).
	 *
	 * @returns {boolean}
	 */
	function validateCurrentStep() {
		clearError();

		if ( state.step === 2 ) {
			if ( ! state.title.trim() ) {
				showError( 'Event title is required.' );
				var titleInput = document.getElementById( 'bb-rl-event-title' );
				if ( titleInput ) {
					titleInput.focus();
				}
				return false;
			}
		}

		if ( state.step === 3 ) {
			if ( ! state.start_date ) {
				showError( 'Start date and time is required.' );
				var startInput = document.getElementById( 'bb-rl-start-date' );
				if ( startInput ) {
					startInput.focus();
				}
				return false;
			}
		}

		// RSVP Settings step: if restriction enabled, a group must be selected.
		if ( state.step === 6 ) {
			if ( state.rsvp_restrict && ! ( state.rsvp_group_id > 0 ) ) {
				showError( 'Please select a group to restrict RSVP to.' );
				var groupSearch = document.getElementById( 'bb-rl-rsvp-group-search' );
				if ( groupSearch ) {
					groupSearch.focus();
				}
				return false;
			}
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Error display
	// -------------------------------------------------------------------------

	/**
	 * Show an error message in the wizard error container.
	 *
	 * @param {string} message
	 */
	function showError( message ) {
		var errEl = document.getElementById( 'bb-rl-wizard-error' );
		if ( errEl ) {
			errEl.textContent = message;
			errEl.style.display = 'block';
		}
	}

	/**
	 * Clear any displayed error message.
	 */
	function clearError() {
		var errEl = document.getElementById( 'bb-rl-wizard-error' );
		if ( errEl ) {
			errEl.textContent = '';
			errEl.style.display = 'none';
		}
	}

	// -------------------------------------------------------------------------
	// Submit
	// -------------------------------------------------------------------------

	/**
	 * Submit the wizard state to the REST API.
	 *
	 * @param {string} status  'draft' | 'published'
	 */
	function submitWizard( status ) {
		state.status = status;
		clearError();

		if ( ! window.bpEventsCreate || ! bpEventsCreate.restUrl ) {
			showError( 'Configuration error: REST URL not found.' );
			return;
		}

		// Disable buttons during submission.
		var draftBtn   = document.getElementById( 'bb-rl-wizard-draft' );
		var publishBtn = document.getElementById( 'bb-rl-wizard-publish' );
		if ( draftBtn )   { draftBtn.disabled = true; }
		if ( publishBtn ) { publishBtn.disabled = true; }

		// Build clean payload — only include rsvp_group_id when restriction is enabled.
		var payload = {
			title:           state.title,
			description:     state.description,
			type:            state.type,
			start_date:      state.start_date,
			end_date:        state.end_date,
			timezone:        state.timezone,
			venue_name:      state.venue_name,
			venue_address:   state.venue_address,
			virtual_url:     state.virtual_url,
			virtual_type:    state.virtual_type,
			capacity:        state.capacity,
			status:          state.status,
			recurrence_rule: state.recurrence_rule,
			category_ids:    state.category_ids,
			tags:            state.tags
		};

		if ( state.rsvp_restrict && state.rsvp_group_id > 0 ) {
			payload.rsvp_group_id = state.rsvp_group_id;
		}

		fetch( bpEventsCreate.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce':   bpEventsCreate.nonce
			},
			body: JSON.stringify( payload )
		} )
		.then( function( response ) {
			return response.json().then( function( data ) {
				return { ok: response.ok, status: response.status, data: data };
			} );
		} )
		.then( function( result ) {
			if ( result.ok && result.data && result.data.permalink ) {
				window.location.href = result.data.permalink;
			} else {
				var msg = ( result.data && result.data.message )
					? result.data.message
					: 'An error occurred. Please try again.';
				showError( msg );
				if ( draftBtn )   { draftBtn.disabled = false; }
				if ( publishBtn ) { publishBtn.disabled = false; }
			}
		} )
		.catch( function( err ) {
			showError( 'Network error: ' + err.message );
			if ( draftBtn )   { draftBtn.disabled = false; }
			if ( publishBtn ) { publishBtn.disabled = false; }
		} );
	}

	// -------------------------------------------------------------------------
	// Navigation handlers
	// -------------------------------------------------------------------------

	/**
	 * Advance to the next step.
	 *
	 * Step sequence:
	 *   With recurrence:    1 → 2 → 3 → 4 → 5 → 6 → 7 → 8
	 *   Without recurrence: 1 → 2 → 3 → 4 → 6 → 7 → 8  (step 5 skipped)
	 */
	function goNext() {
		if ( ! validateCurrentStep() ) {
			return;
		}

		var next = state.step + 1;

		// Skip recurrence step (step 5) if not enabled.
		if ( next === 5 && ! state.showRecurrence ) {
			next = 6; // Jump to RSVP Settings.
		}

		// Step 8 is the last step (Review); do not advance past it.
		if ( next > 8 ) {
			return;
		}

		renderStep( next );
	}

	/**
	 * Go back one step.
	 *
	 * Step sequence (reverse):
	 *   With recurrence:    8 → 7 → 6 → 5 → 4 → 3 → 2 → 1
	 *   Without recurrence: 8 → 7 → 6 → 4 → 3 → 2 → 1  (step 5 skipped)
	 */
	function goPrev() {
		var prev = state.step - 1;

		// Skip recurrence step when going back if it is not shown.
		if ( prev === 5 && ! state.showRecurrence ) {
			prev = 4;
		}

		if ( prev < 1 ) {
			return;
		}

		renderStep( prev );
	}

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	/**
	 * Initialise the wizard when the DOM is ready.
	 */
	function initWizard() {
		var form = document.getElementById( 'bb-rl-event-create-form' );

		if ( ! form ) {
			return;
		}

		// Next button.
		var nextBtn = document.getElementById( 'bb-rl-wizard-next' );
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function() {
				goNext();
			} );
		}

		// Back button.
		var prevBtn = document.getElementById( 'bb-rl-wizard-prev' );
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function() {
				goPrev();
			} );
		}

		// Save Draft button.
		var draftBtn = document.getElementById( 'bb-rl-wizard-draft' );
		if ( draftBtn ) {
			draftBtn.addEventListener( 'click', function() {
				submitWizard( 'draft' );
			} );
		}

		// Publish button.
		var publishBtn = document.getElementById( 'bb-rl-wizard-publish' );
		if ( publishBtn ) {
			publishBtn.addEventListener( 'click', function() {
				submitWizard( 'published' );
			} );
		}

		// Render first step.
		renderStep( 1 );
	}

	// Script is enqueued in the footer — DOM is already ready when this runs.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initWizard );
	} else {
		initWizard();
	}

} )();
