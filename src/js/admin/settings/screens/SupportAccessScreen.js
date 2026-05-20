/**
 * BuddyBoss Admin Settings 2.0 - Support Access Screen
 *
 * Sub-page of the Help tab. Reached from the "Open Access" button on the
 * Support Access card. Provides a master toggle for support-team access,
 * a live countdown until expiry, a note textarea, recent session log, and
 * a "Create Support Ticket" CTA.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect } from '@wordpress/element';
import { Button, ToggleControl, TextareaControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ModifyDurationModal } from '../components/modals/ModifyDurationModal';
import { AddTicketModal } from '../components/modals/AddTicketModal';

/**
 * Initial mock countdown for the design — wired here as local state so the
 * tiles tick. Replace with a real expiry timestamp from PHP once the AJAX
 * endpoint is in place.
 */
var INITIAL_COUNTDOWN = {
	days: 9,
	hours: 23,
	minutes: 35,
	seconds: 51,
};

/**
 * Pad a number with leading zero.
 *
 * @param {number} n Number to pad.
 * @returns {string} Two-digit string.
 */
function pad2( n ) {
	n = parseInt( n, 10 ) || 0;
	return ( n < 10 ? '0' : '' ) + n;
}

/**
 * Decrement a {days, hours, minutes, seconds} object by one second.
 *
 * @param {Object} t Current countdown state.
 * @returns {Object} Next countdown state, clamped at zero.
 */
function tick( t ) {
	var total = ( ( t.days * 24 + t.hours ) * 60 + t.minutes ) * 60 + t.seconds - 1;
	if ( total <= 0 ) {
		return { days: 0, hours: 0, minutes: 0, seconds: 0 };
	}
	var s = total % 60;
	var m = Math.floor( total / 60 ) % 60;
	var h = Math.floor( total / 3600 ) % 24;
	var d = Math.floor( total / 86400 );
	return { days: d, hours: h, minutes: m, seconds: s };
}

/**
 * Support Access Screen.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation callback.
 * @returns {JSX.Element} Support Access screen.
 */
export function SupportAccessScreen( { onNavigate } ) {
	var enabledState = useState( true );
	var enabled = enabledState[ 0 ];
	var setEnabled = enabledState[ 1 ];

	var noteState = useState( '' );
	var note = noteState[ 0 ];
	var setNote = noteState[ 1 ];

	var countdownState = useState( INITIAL_COUNTDOWN );
	var countdown = countdownState[ 0 ];
	var setCountdown = countdownState[ 1 ];

	var durationState = useState( '5' );
	var duration = durationState[ 0 ];
	var setDuration = durationState[ 1 ];

	var modalOpenState = useState( false );
	var isModalOpen = modalOpenState[ 0 ];
	var setIsModalOpen = modalOpenState[ 1 ];

	var ticketState = useState( '' );
	var ticket = ticketState[ 0 ];
	var setTicket = ticketState[ 1 ];

	var ticketModalOpenState = useState( false );
	var isTicketModalOpen = ticketModalOpenState[ 0 ];
	var setIsTicketModalOpen = ticketModalOpenState[ 1 ];

	useEffect( function () {
		if ( ! enabled ) {
			return;
		}
		var id = setInterval( function () {
			setCountdown( function ( prev ) {
				return tick( prev );
			} );
		}, 1000 );
		return function () {
			clearInterval( id );
		};
	}, [ enabled ] );

	var handleBack = function () {
		if ( 'function' === typeof onNavigate ) {
			onNavigate( '/settings/help' );
		}
	};

	var sessions = [
		{ id: 's1', label: __( 'Yesterday, 9:34 PM – Support session for billing inquiry', 'buddyboss' ) },
		{ id: 's2', label: __( 'June 17, 2025, 10:28 PM – Performance troubleshooting', 'buddyboss' ) },
	];

	var tiles = [
		{ key: 'days',    value: pad2( countdown.days ),    label: __( 'Days', 'buddyboss' ) },
		{ key: 'hours',   value: pad2( countdown.hours ),   label: __( 'Hours', 'buddyboss' ) },
		{ key: 'minutes', value: pad2( countdown.minutes ), label: __( 'Minutes', 'buddyboss' ) },
		{ key: 'seconds', value: pad2( countdown.seconds ), label: __( 'Seconds', 'buddyboss' ) },
	];

	return (
		<div className="bb-admin-help-screen bb-admin-support-access">
			<div className="bb-admin-help-wrapper">
				<button
					type="button"
					className="bb-admin-support-access__back"
					onClick={ handleBack }
				>
					<i
						className="bb-icons-rl bb-icons-rl-arrow-left bb-admin-support-access__back-icon"
						aria-hidden="true"
					></i>
					<span className="bb-admin-support-access__back-label">
						{ __( 'Back', 'buddyboss' ) }
					</span>
				</button>

				<section className={ 'bb-admin-support-access__enable-card ' + (!enabled ? 'bb-admin-support-access__enable-card--disabled' : '')}>
					<div className="bb-admin-support-access__enable-text">
						<h2 className="bb-admin-support-access__enable-title">
							{ __( 'Enable Support Access', 'buddyboss' ) }
						</h2>
						<p className="bb-admin-support-access__enable-desc">
							{ __( 'Allow our support team to securely access your site using temporary credentials to troubleshoot issues. All access is logged and automatically expires based on your settings.', 'buddyboss' ) }
						</p>
					</div>
					<div className="bb-admin-support-access__enable-toggle">
						<ToggleControl
							className="components-form-toggle--is-big"
							label=""
							checked={ enabled }
							onChange={ function ( value ) { setEnabled( value ); } }
							__nextHasNoMarginBottom
						/>
					</div>
				</section>

				{ enabled ? (
					<section className="bb-admin-support-access__panel">
						<div className="bb-admin-support-access__row">
							<div className="bb-admin-support-access__countdown">
								<p className="bb-admin-support-access__countdown-label">
									{ __( 'Support access expires in:', 'buddyboss' ) }
								</p>
								<div className="bb-admin-support-access__countdown-tiles" role="timer" aria-live="off">
									{ tiles.map( function ( tile ) {
										return (
											<div key={ tile.key } className="bb-admin-support-access__tile">
												<span className="bb-admin-support-access__tile-value">{ tile.value }</span>
												<span className="bb-admin-support-access__tile-label">{ tile.label }</span>
											</div>
										);
									} ) }
								</div>
							</div>
							<Button
								variant="secondary"
								className="bb-admin-support-access__modify"
								onClick={ function () { setIsModalOpen( true ); } }
							>
								{ __( 'Modify Duration', 'buddyboss' ) }
							</Button>
						</div>

						<div className="bb-admin-support-access__divider" aria-hidden="true"></div>

						<div className="bb-admin-support-access__actions">
							<Button
								variant="secondary"
								className="bb-admin-support-access__ticket"
								onClick={ function () { setIsTicketModalOpen( true ); } }
							>
								{ __( 'Add Ticket Number', 'buddyboss' ) }
							</Button>
						</div>
					</section>
				) : (
					<section
						className="bb-admin-support-access__panel bb-admin-support-access__panel--disabled"
						aria-labelledby="bb-admin-support-access-disabled-title"
					>
						<div className="bb-admin-support-access__disabled-icon" aria-hidden="true">
							<i className="bb-icons-rl bb-icons-rl-lock-simple"></i>
						</div>
						<div className="bb-admin-support-access__disabled-text">
							<h2
								id="bb-admin-support-access-disabled-title"
								className="bb-admin-support-access__disabled-title"
							>
								{ __( 'Access is disabled', 'buddyboss' ) }
							</h2>
							<p className="bb-admin-support-access__disabled-desc">
								{ __( 'Enable support access to set a time limit and track all support activity.', 'buddyboss' ) }
							</p>
						</div>
					</section>
				) }
			</div>

			<ModifyDurationModal
				isOpen={ isModalOpen }
				value={ duration }
				onClose={ function () { setIsModalOpen( false ); } }
				onSave={ function ( value ) {
					// "Extend support access by" — add the selected days to the
					// live countdown. Client-side placeholder; once the AJAX
					// endpoint exists, persist there and re-seed the countdown
					// from the server-returned expiry instead.
					var addDays = parseInt( value, 10 ) || 0;
					setDuration( value );
					setCountdown( function ( prev ) {
						return {
							days: prev.days + addDays,
							hours: prev.hours,
							minutes: prev.minutes,
							seconds: prev.seconds,
						};
					} );
					setIsModalOpen( false );
				} }
			/>

			<AddTicketModal
				isOpen={ isTicketModalOpen }
				value={ ticket }
				onClose={ function () { setIsTicketModalOpen( false ); } }
				onSave={ function ( value ) {
					setTicket( value );
					// Do the saving ticket logic here.
					setIsTicketModalOpen( false );
				} }
			/>
		</div>
	);
}

export default SupportAccessScreen;
