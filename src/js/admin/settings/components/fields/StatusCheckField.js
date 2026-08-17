/**
 * BuddyBoss Admin Settings 2.0 - Status Check Field Component
 *
 * Auto-triggers an AJAX server-side check on mount and when a watched
 * field value changes (e.g., Direct Access re-checks when Symbolic Links
 * toggle is switched). Displays the result as a notice (success/warning).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { sanitizeHtml } from '../../utils/sanitize';

/**
 * Status Check Field Component
 *
 * @param {Object}  props          Component props.
 * @param {Object}  props.field    Field configuration from PHP.
 * @param {Object}  props.values   All current field values (for watching related fields).
 * @param {boolean} props.disabled Whether the field is disabled.
 * @returns {JSX.Element} Status check field component.
 */
export function StatusCheckField( { field, values, disabled } ) {
	var [ isChecking, setIsChecking ] = useState( false );
	var [ result, setResult ] = useState( null );
	var abortRef = useRef( null );
	var timeoutRef = useRef( null );
	var initialCheckDone = useRef( false );

	/**
	 * Run the AJAX status check.
	 *
	 * @param {boolean} isRecheck Whether this is a re-check triggered by a watched field change.
	 */
	var runCheck = function( isRecheck ) {
		var ajaxAction = field.ajax_action || '';
		if ( ! ajaxAction ) {
			return;
		}

		// Abort any in-flight request.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}

		var controller = new AbortController();
		abortRef.current = controller;

		setIsChecking( true );
		setResult( null );

		var formData = new FormData();
		formData.append( 'action', ajaxAction );
		formData.append( 'nonce', window.bbAdminData.ajaxNonce );

		// Send the current watched field value so the server can use it
		// instead of reading from the DB (which may not be saved yet).
		var watchFieldName = field.watch_field || '';
		if ( watchFieldName && values && undefined !== values[ watchFieldName ] ) {
			formData.append( 'watch_field', watchFieldName );
			formData.append( 'watch_value', values[ watchFieldName ] );
		}

		var doFetch = function() {
			fetch( window.bbAdminData.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
				signal: controller.signal,
			} )
				.then( function( response ) {
					return response.json();
				} )
				.then( function( data ) {
					setIsChecking( false );
					if ( data.success && data.data ) {
						setResult( {
							status: data.data.status || 'info',
							message: data.data.message || '',
						} );
					} else {
						setResult( {
							status: 'warning',
							message: ( data.data && data.data.message ) || __( 'Unable to perform check.', 'buddyboss-platform' ),
						} );
					}
				} )
				.catch( function( err ) {
					// Ignore abort errors.
					if ( err && 'AbortError' === err.name ) {
						return;
					}
					setIsChecking( false );
					setResult( {
						status: 'warning',
						message: __( 'An error occurred while checking.', 'buddyboss-platform' ),
					} );
				} );
		};

		// Clear any pending timeout from a previous re-check.
		if ( timeoutRef.current ) {
			clearTimeout( timeoutRef.current );
			timeoutRef.current = null;
		}

		// On re-checks, add a small delay to let auto-save complete first.
		// The auto-save debounce is 1s, so 1.5s ensures the DB is updated
		// before server-side checks that read from the DB (e.g., Direct Access).
		if ( isRecheck ) {
			timeoutRef.current = setTimeout( doFetch, 1500 );
		} else {
			doFetch();
		}
	};

	// Auto-check on mount.
	useEffect( function() {
		if ( ! initialCheckDone.current ) {
			initialCheckDone.current = true;
			runCheck( false );
		}

		return function() {
			if ( timeoutRef.current ) {
				clearTimeout( timeoutRef.current );
			}
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	// Re-check when watched field value changes (skip the initial render).
	var watchField = field.watch_field || '';
	var watchValue = watchField && values ? values[ watchField ] : undefined;
	var prevWatchValue = useRef( watchValue );

	useEffect( function() {
		if ( prevWatchValue.current !== watchValue && initialCheckDone.current ) {
			prevWatchValue.current = watchValue;
			runCheck( true );
		}
	}, [ watchValue ] );

	return (
		<div className="bb-admin-status-check">
			{ isChecking && (
				<div className="bb-admin-status-check__loading">
					<span className="bb-admin-status-check__spinner" />
					<span>{ __( 'Checking...', 'buddyboss-platform' ) }</span>
				</div>
			) }

			{/* Safe: message is sanitized via sanitizeHtml whitelist sanitizer before rendering. */}
			{ ! isChecking && result && result.message && (
				<div
					className={ 'bb-admin-notice bb-admin-notice--' + result.status }
					dangerouslySetInnerHTML={ { __html: sanitizeHtml( result.message ) } }
				/>
			) }
		</div>
	);
}
