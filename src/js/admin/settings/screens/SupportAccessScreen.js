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
import { Button, ToggleControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { ajaxFetch } from '../utils/ajax';
import { ModifyDurationModal } from '../components/modals/ModifyDurationModal';
import { AddTicketModal } from '../components/modals/AddTicketModal';
import { Toast, useAutoDismissToast } from '../components/Toast';

/**
 * Convert a number of remaining seconds to a {days, hours, minutes, seconds}
 * countdown object, clamped at zero.
 *
 * @param {number} totalSeconds Remaining seconds.
 * @returns {Object} Countdown parts.
 */
function secondsToCountdown( totalSeconds ) {
	var total = parseInt( totalSeconds, 10 ) || 0;
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
	return secondsToCountdown( total );
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
	var enabledState = useState( false );
	var enabled = enabledState[ 0 ];
	var setEnabled = enabledState[ 1 ];

	// Whether the initial state fetch is still in flight.
	var loadingState = useState( true );
	var isLoading = loadingState[ 0 ];
	var setIsLoading = loadingState[ 1 ];

	// Whether a toggle/extend/ticket request is currently saving.
	var savingState = useState( false );
	var isSaving = savingState[ 0 ];
	var setIsSaving = savingState[ 1 ];

	var countdownState = useState( { days: 0, hours: 0, minutes: 0, seconds: 0 } );
	var countdown = countdownState[ 0 ];
	var setCountdown = countdownState[ 1 ];

	// The login URL is captured once (after enable) and forwarded to the
	// support system note; it is intentionally not rendered in the admin UI.
	var loginUrlState = useState( '' );
	var loginUrl = loginUrlState[ 0 ];
	var setLoginUrl = loginUrlState[ 1 ];

	var modalOpenState = useState( false );
	var isModalOpen = modalOpenState[ 0 ];
	var setIsModalOpen = modalOpenState[ 1 ];

	// The same login URL can cover several support tickets, so this is a list.
	var ticketsState = useState( [] );
	var tickets = ticketsState[ 0 ];
	var setTickets = ticketsState[ 1 ];

	var ticketModalOpenState = useState( false );
	var isTicketModalOpen = ticketModalOpenState[ 0 ];
	var setIsTicketModalOpen = ticketModalOpenState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];

	useAutoDismissToast( toast, setToast );

	/**
	 * Apply a server response payload to local component state.
	 *
	 * @param {Object} data Response data from a support-access AJAX endpoint.
	 */
	var applyState = function ( data ) {
		if ( ! data ) {
			return;
		}
		setEnabled( !! data.enabled );
		setCountdown( secondsToCountdown( data.remaining ) );
		setTickets( Array.isArray( data.ticket_numbers ) ? data.ticket_numbers : [] );

		// login_url is only present right after a fresh token is minted. It is
		// not displayed; it's held in state purely to forward to the support system
		// note when a ticket is added or the duration is changed.
		if ( data.has_login_url && data.login_url ) {
			setLoginUrl( data.login_url );
		} else if ( ! data.enabled ) {
			setLoginUrl( '' );
		}
	};

	// Load the real state from the server on mount.
	useEffect( function () {
		var cancelled = false;
		ajaxFetch( 'bb_admin_support_access_get' )
			.then( function ( res ) {
				if ( cancelled ) {
					return;
				}
				if ( res && res.success ) {
					applyState( res.data );
				}
			} )
			.catch( function () {
				// Leave defaults (disabled) on error.
			} )
			.finally( function () {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			} );
		return function () {
			cancelled = true;
		};
	}, [] );

	// Tick the countdown each second while access is enabled.
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

	/**
	 * Toggle support access on/off via the server.
	 *
	 * @param {boolean} value Desired enabled state.
	 */
	var handleToggle = function ( value ) {
		if ( isSaving ) {
			return;
		}
		setIsSaving( true );
		ajaxFetch( 'bb_admin_support_access_toggle', { enabled: value ? '1' : '0' } )
			.then( function ( res ) {
				if ( res && res.success ) {
					applyState( res.data );
					setToast( {
						status: 'success',
						message: value
							? __( 'Support access enabled', 'buddyboss' )
							: __( 'Support access disabled', 'buddyboss' ),
					} );
				} else {
					setToast( {
						status: 'error',
						message: ( res && res.data && res.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
					} );
				}
			} )
			.catch( function ( err ) {
				setToast( { status: 'error', message: err.message || __( 'Something went wrong.', 'buddyboss' ) } );
			} )
			.finally( function () {
				setIsSaving( false );
			} );
	};

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
							disabled={ isLoading || isSaving }
							onChange={ handleToggle }
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
								disabled={ isSaving }
								onClick={ function () { setIsModalOpen( true ); } }
							>
								{ __( 'Modify Duration', 'buddyboss' ) }
							</Button>
						</div>

						<div className="bb-admin-support-access__divider" aria-hidden="true"></div>

						{ tickets.length > 0 && (
							<div className="bb-admin-support-access__tickets">
								<p className="bb-admin-support-access__tickets-label">
									{ __( 'Attached tickets:', 'buddyboss' ) }
								</p>
								<ul className="bb-admin-support-access__tickets-list">
									{ tickets.map( function ( ticket ) {
										return (
											<li key={ ticket } className="bb-admin-support-access__tickets-item">
												{ sprintf(
													/* translators: %s: ticket number. */
													__( 'Ticket #%s', 'buddyboss' ),
													ticket
												) }
											</li>
										);
									} ) }
								</ul>
							</div>
						) }

						<div className="bb-admin-support-access__actions">
							<Button
								variant="secondary"
								className="bb-admin-support-access__ticket"
								disabled={ isSaving }
								onClick={ function () { setIsTicketModalOpen( true ); } }
							>
								{ tickets.length > 0
									? sprintf(
										/* translators: %d: number of attached tickets. */
										__( 'Add Another Ticket (%d)', 'buddyboss' ),
										tickets.length
									)
									: __( 'Add Ticket Number', 'buddyboss' ) }
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
				value="5"
				onClose={ function () { setIsModalOpen( false ); } }
				onSave={ function ( value ) {
					// "Extend support access by" — persist on the server, then
					// re-seed the countdown from the server-returned expiry so the
					// timer always reflects the authoritative remaining window. The
					// login URL is forwarded so the server can re-post an updated-
					// expiry note to every attached ticket. The response `notice`
					// reflects that outcome (shown as a bottom-right toast).
					setIsModalOpen( false );
					setIsSaving( true );
					ajaxFetch( 'bb_admin_support_access_extend', { days: parseInt( value, 10 ) || 0, login_url: loginUrl } )
						.then( function ( res ) {
							if ( res && res.success ) {
								applyState( res.data );
								var notice = res.data && res.data.notice;
								setToast( {
									status: ( notice && notice.status ) || 'success',
									message: ( notice && notice.message ) || __( 'Access duration updated.', 'buddyboss' ),
								} );
							} else {
								setToast( {
									status: 'error',
									message: ( res && res.data && res.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
								} );
							}
						} )
						.catch( function ( err ) {
							setToast( { status: 'error', message: err.message || __( 'Something went wrong.', 'buddyboss' ) } );
						} )
						.finally( function () {
							setIsSaving( false );
						} );
				} }
			/>

			<AddTicketModal
				isOpen={ isTicketModalOpen }
				value=""
				onClose={ function () { setIsTicketModalOpen( false ); } }
				onSave={ function ( value ) {
					// Append the ticket number; the server adds it to the grant's
					// ticket list (deduped) and posts an internal note to the
					// support system conversation. The response carries a `notice`
					// object reflecting the real support system outcome (success or a
					// specific error such as "ticket not found"), shown as a
					// bottom-right toast.
					setIsTicketModalOpen( false );
					setIsSaving( true );
					// Pass the login URL the browser is holding (shown once after
					// enable) so the server can embed it in the support system note.
					// It is never persisted server-side; if the page was reloaded
					// and the URL is no longer in state, the note omits it.
					ajaxFetch( 'bb_admin_support_access_set_ticket', { ticket_number: value, login_url: loginUrl } )
						.then( function ( res ) {
							if ( res && res.success ) {
								applyState( res.data );
								var notice = res.data && res.data.notice;
								setToast( {
									status: ( notice && notice.status ) || 'success',
									message: ( notice && notice.message ) || __( 'Ticket added to Support Access.', 'buddyboss' ),
								} );
							} else {
								setToast( {
									status: 'error',
									message: ( res && res.data && res.data.message ) || __( 'Something went wrong.', 'buddyboss' ),
								} );
							}
						} )
						.catch( function ( err ) {
							setToast( { status: 'error', message: err.message || __( 'Something went wrong.', 'buddyboss' ) } );
						} )
						.finally( function () {
							setIsSaving( false );
						} );
				} }
			/>

			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function () { setToast( null ); } }
					/>
				</div>
			) }
		</div>
	);
}

export default SupportAccessScreen;
