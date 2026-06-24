/**
 * BuddyBoss Admin Settings 2.0 - Admin Notice
 *
 * Shared dismissible notice component with auto-dismiss after 5 seconds.
 * Used by all list screens for success/error feedback.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Admin Notice Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.notice    Notice object with `type` ('success'|'error') and `message` string.
 * @param {Function} props.onDismiss Dismiss handler (sets notice to null).
 * @param {number}   props.autoDismiss Auto-dismiss timeout in ms. Default 5000. Set 0 to disable.
 * @returns {JSX.Element|null} Notice element or null.
 */
export function AdminNotice( { notice, onDismiss, autoDismiss } ) {
	var timeout = 'undefined' !== typeof autoDismiss ? autoDismiss : 5000;

	// Auto-dismiss after timeout.
	useEffect( function () {
		if ( notice && timeout > 0 ) {
			var timer = setTimeout( function () {
				onDismiss();
			}, timeout );
			return function () {
				clearTimeout( timer );
			};
		}
	}, [ notice, timeout, onDismiss ] );

	if ( ! notice ) {
		return null;
	}

	return (
		<div className={ 'bb-admin-notice bb-admin-notice--' + notice.type } role={ 'error' === notice.type ? 'alert' : 'status' } aria-live={ 'error' === notice.type ? 'assertive' : 'polite' }>
			<span>{ notice.message }</span>
			<button
				className="bb-admin-notice--dismiss"
				onClick={ onDismiss }
				aria-label={ __( 'Dismiss notice', 'buddyboss-platform' ) }
			>
				<i className="bb-icons-rl bb-icons-rl-x"></i>
			</button>
		</div>
	);
}
