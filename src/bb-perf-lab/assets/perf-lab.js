/**
 * Performance Lab -- admin screen behaviour.
 *
 * TEMPORARY diagnostic tooling. See `bb-perf-lab.php` for removal instructions.
 *
 * @since BuddyBoss [BBVERSION]
 */
( function () {
	'use strict';

	/**
	 * Post to one of the lab's AJAX endpoints.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string}   action Action suffix, e.g. 'bench'.
	 * @param {Object}   data   Payload.
	 * @param {Function} done   Called with ( error, payload ).
	 *
	 * @return {void}
	 */
	function post( action, data, done ) {
		var body = new FormData();
		var key;

		body.append( 'action', 'bb_perf_lab_' + action );
		body.append( 'nonce', window.bbPerfLab.nonce );

		for ( key in data ) {
			if ( Object.prototype.hasOwnProperty.call( data, key ) ) {
				body.append( key, data[ key ] );
			}
		}

		window.fetch( window.bbPerfLab.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( response ) {
			return response.json();
		} ).then( function ( payload ) {
			if ( ! payload || ! payload.success ) {
				done( ( payload && payload.data && payload.data.message ) || 'Request failed.', null );
				return;
			}

			done( null, payload.data );
		} ).catch( function ( error ) {
			done( error.message || 'Request failed.', null );
		} );
	}

	/**
	 * Shorthand for getElementById.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} id Element id.
	 *
	 * @return {HTMLElement|null} The element.
	 */
	function el( id ) {
		return document.getElementById( id );
	}

	/**
	 * Format a number for display.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} value    Value.
	 * @param {number} decimals Decimal places.
	 *
	 * @return {string} Formatted value.
	 */
	function num( value, decimals ) {
		return Number( value || 0 ).toFixed( undefined === decimals ? 1 : decimals );
	}

	/**
	 * Escape text destined for markup.
	 *
	 * The benchmark's notes and error messages are server-authored, but an error
	 * can carry a route the operator typed, so nothing goes into markup unescaped.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} text Text to escape.
	 *
	 * @return {string} Escaped text.
	 */
	function esc( text ) {
		var node = document.createElement( 'span' );

		node.textContent = String( text === undefined || text === null ? '' : text );

		return node.innerHTML;
	}

	/* -----------------------------------------------------------------
	 * Tabs
	 * -------------------------------------------------------------- */

	document.querySelectorAll( '.nav-tab[data-tab]' ).forEach( function ( tab ) {
		tab.addEventListener( 'click', function ( event ) {
			event.preventDefault();

			document.querySelectorAll( '.nav-tab[data-tab]' ).forEach( function ( other ) {
				other.classList.remove( 'nav-tab-active' );
			} );

			tab.classList.add( 'nav-tab-active' );

			document.querySelectorAll( '.bb-perf-panel' ).forEach( function ( panel ) {
				panel.hidden = panel.getAttribute( 'data-panel' ) !== tab.getAttribute( 'data-tab' );
			} );
		} );
	} );

	/* -----------------------------------------------------------------
	 * Benchmark
	 * -------------------------------------------------------------- */

	/**
	 * Render one metric row of the comparison table.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} label    Row label.
	 * @param {Object} arms     The three arms.
	 * @param {string} metric   Metric key.
	 * @param {number} decimals Decimal places.
	 *
	 * @return {string} Table row markup.
	 */
	function metricRow( label, arms, metric, decimals ) {
		var full = arms.full[ metric ].median;
		var sel = arms.selective[ metric ].median;
		var floor = arms.floor[ metric ].median;
		var delta = full > 0 ? ( ( 1 - sel / full ) * 100 ) : 0;
		var cls = delta > 1 ? 'bb-perf-good' : ( delta < -1 ? 'bb-perf-bad' : '' );

		return '<tr>' +
			'<th scope="row">' + label + '</th>' +
			'<td>' + num( full, decimals ) + '</td>' +
			'<td>' + num( sel, decimals ) + '</td>' +
			'<td>' + num( floor, decimals ) + '</td>' +
			'<td class="' + cls + '">' + ( delta >= 0 ? '−' : '+' ) + num( Math.abs( delta ), 1 ) + '%</td>' +
			'</tr>';
	}

	/**
	 * Render the benchmark result.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} result Benchmark payload.
	 *
	 * @return {void}
	 */
	function renderBench( result ) {
		var arms = result.arms;
		var verdict = result.verdict;
		var html = '';
		var i;

		html += '<h3>Result — median of ' + esc( result.runs ) + ' runs per arm</h3>';

		html += '<table class="widefat striped bb-perf-result"><thead><tr>' +
			'<th>Metric</th><th>Full</th><th>Selective</th><th>Floor (<code>_fields=id</code>)</th><th>Selective vs full</th>' +
			'</tr></thead><tbody>';

		html += metricRow( 'Server time (ms)', arms, 'wall_ms', 1 );
		html += metricRow( 'Database time (ms)', arms, 'db_ms', 1 );
		html += metricRow( 'Queries', arms, 'queries', 0 );
		html += metricRow( 'Payload (KB)', arms, 'payload_kb', 1 );
		html += metricRow( 'Peak memory (KB)', arms, 'mem_kb', 0 );

		html += '</tbody></table>';

		html += '<div class="bb-perf-verdict">';
		html += '<p><strong>Irreducible cost: ' + num( verdict.irreducible_pct ) + '%</strong> — the share of the full request that is spent before any field is built. No selection can go below that.</p>';
		html += '<p><strong>Headroom: ' + num( verdict.headroom_ms ) + ' ms</strong> — the most any selection could save here. This one took ' + num( verdict.captured_of_headroom ) + '% of it (' + num( verdict.server_saving_ms ) + ' ms, ' + num( verdict.server_saving_pct ) + '% of the whole request).</p>';
		html += '<p><strong>Payload: −' + num( verdict.payload_saving_pct ) + '%</strong>, queries saved: ' + verdict.queries_saved + ', database time saved: ' + num( verdict.db_ms_saved ) + ' ms.</p>';

		if ( verdict.notes && verdict.notes.length ) {
			html += '<ul class="bb-perf-notes">';

			for ( i = 0; i < verdict.notes.length; i++ ) {
				html += '<li>' + esc( verdict.notes[ i ] ) + '</li>';
			}

			html += '</ul>';
		}

		html += '</div>';

		el( 'bb-perf-bench-result' ).innerHTML = html;
	}

	if ( el( 'bb-perf-run' ) ) {
		el( 'bb-perf-run' ).addEventListener( 'click', function () {
			var button = el( 'bb-perf-run' );
			var status = el( 'bb-perf-bench-status' );

			button.disabled = true;
			status.textContent = 'Running…';
			el( 'bb-perf-bench-result' ).innerHTML = '';

			post( 'bench', {
				route: el( 'bb-perf-route' ).value,
				query: el( 'bb-perf-query' ).value,
				fields: el( 'bb-perf-fields' ).value,
				embed: el( 'bb-perf-embed' ).value,
				embed_fields: el( 'bb-perf-embed-fields' ).value,
				runs: el( 'bb-perf-runs' ).value,
				flush: el( 'bb-perf-flush' ).checked ? 1 : 0,
				user_id: el( 'bb-perf-user' ).value
			}, function ( error, data ) {
				button.disabled = false;
				status.textContent = error ? '' : 'Done.';

				if ( error ) {
					el( 'bb-perf-bench-result' ).innerHTML = '<div class="notice notice-error inline"><p>' + esc( error ) + '</p></div>';
					return;
				}

				renderBench( data );
			} );
		} );
	}

	/* -----------------------------------------------------------------
	 * Seeder
	 * -------------------------------------------------------------- */

	var seeding = false;

	/**
	 * Show seeding progress.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} progress Progress payload.
	 *
	 * @return {void}
	 */
	function showProgress( progress ) {
		var wrap = el( 'bb-seed-progress' );

		wrap.hidden = false;
		wrap.querySelector( '.bb-perf-bar span' ).style.width = progress.percent + '%';

		el( 'bb-seed-status' ).textContent = progress.finished
			? 'Finished — ' + progress.created.users + ' members, ' + progress.created.groups + ' groups, ' + progress.created.activities + ' activities.'
			: 'Phase: ' + progress.phase + ' — ' + progress.done + ' of ' + progress.total + ' (' + progress.percent + '%)';
	}

	/**
	 * Keep asking the server to do another chunk until the job is done.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {void}
	 */
	function pump() {
		post( 'seed_tick', {}, function ( error, progress ) {
			if ( error ) {
				seeding = false;
				el( 'bb-seed-status' ).textContent = error;
				el( 'bb-seed-start' ).disabled = false;
				return;
			}

			showProgress( progress );

			if ( progress.finished ) {
				seeding = false;
				el( 'bb-seed-start' ).disabled = false;
				el( 'bb-seed-purge' ).disabled = false;
				return;
			}

			pump();
		} );
	}

	if ( el( 'bb-seed-start' ) ) {
		el( 'bb-seed-start' ).addEventListener( 'click', function () {
			if ( seeding ) {
				return;
			}

			seeding = true;
			el( 'bb-seed-start' ).disabled = true;

			post( 'seed_start', {
				users: el( 'bb-seed-users' ).value,
				groups: el( 'bb-seed-groups' ).value,
				activities: el( 'bb-seed-activities' ).value,
				comments: el( 'bb-seed-comments' ).value,
				reactions: el( 'bb-seed-reactions' ).value,
				follows: el( 'bb-seed-follows' ).value,
				friends: el( 'bb-seed-friends' ).value,
				meta: el( 'bb-seed-meta' ).value
			}, function ( error, progress ) {
				if ( error ) {
					seeding = false;
					el( 'bb-seed-start' ).disabled = false;
					el( 'bb-seed-status' ).textContent = error;
					return;
				}

				showProgress( progress );
				pump();
			} );
		} );
	}

	if ( el( 'bb-seed-resume' ) ) {
		el( 'bb-seed-resume' ).addEventListener( 'click', function () {
			if ( seeding ) {
				return;
			}

			seeding = true;
			el( 'bb-seed-start' ).disabled = true;
			pump();
		} );
	}

	if ( el( 'bb-seed-purge' ) ) {
		el( 'bb-seed-purge' ).addEventListener( 'click', function () {
			if ( ! window.confirm( 'Remove everything the last generate run created?' ) ) {
				return;
			}

			el( 'bb-seed-purge' ).disabled = true;
			el( 'bb-seed-status' ).textContent = 'Removing…';
			el( 'bb-seed-progress' ).hidden = false;

			post( 'seed_purge', {}, function ( error, removed ) {
				el( 'bb-seed-status' ).textContent = error
					? error
					: 'Removed ' + removed.activities + ' activities, ' + removed.users + ' members, ' + removed.groups + ' groups.';
			} );
		} );
	}

	/* -----------------------------------------------------------------
	 * Settings and log
	 * -------------------------------------------------------------- */

	if ( el( 'bb-perf-save' ) ) {
		el( 'bb-perf-save' ).addEventListener( 'click', function () {
			post( 'save_settings', {
				monitor_enabled: el( 'bb-set-enabled' ).checked ? 1 : 0,
				monitor_deep: el( 'bb-set-deep' ).checked ? 1 : 0,
				monitor_routes: el( 'bb-set-routes' ).value,
				monitor_user_id: el( 'bb-set-user' ).value,
				monitor_max_rows: el( 'bb-set-max' ).value
			}, function ( error ) {
				el( 'bb-perf-settings-status' ).textContent = error || 'Saved.';
			} );
		} );
	}

	if ( el( 'bb-perf-clear-log' ) ) {
		el( 'bb-perf-clear-log' ).addEventListener( 'click', function () {
			post( 'clear_log', {}, function () {
				window.location.reload();
			} );
		} );
	}

	if ( el( 'bb-perf-uninstall' ) ) {
		el( 'bb-perf-uninstall' ).addEventListener( 'click', function () {
			if ( ! window.confirm( 'Drop the log table and every setting this tool created?' ) ) {
				return;
			}

			post( 'uninstall', {}, function () {
				window.location.reload();
			} );
		} );
	}
}() );
