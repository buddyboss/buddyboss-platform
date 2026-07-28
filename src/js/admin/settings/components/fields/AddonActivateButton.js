/**
 * Add-on activate/install button for empty-state fields.
 *
 * Triggers an add-on install/activate AJAX flow in place of a full-page
 * plugins.php redirect, so an installed-but-inactive add-on can be activated
 * without leaving the Settings screen. On success the page reloads so the panel
 * re-renders with the now-active add-on's real settings instead of the upsell
 * placeholder.
 *
 * Two handler families are supported and the server picks which one applies via
 * `nonceKey`:
 *
 * - Mothership (`mosh_addon_activate` / `mosh_addon_install`) — verifies the
 *   `mosh_addons` nonce, exposed as `bbAdminData.addonNonce` (the default).
 *   These resolve the product from the licensed add-on server first, so they
 *   only work on a site with an activated license.
 * - Platform-owned handlers (e.g. `bb_member_blogging_activate_plugin`) —
 *   verify the `bb_admin_settings` nonce, exposed as `bbAdminData.ajaxNonce`.
 *   Pass nonceKey="ajaxNonce" for these.
 *
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @param {Object} props
 * @param {string} props.action      AJAX action name.
 * @param {string} props.slug        Plugin folder slug (e.g. 'buddyboss-member-blogging').
 * @param {string} props.label       Button label.
 * @param {string} [props.className] Optional CSS class for the button.
 * @param {string} [props.nonceKey]  `window.bbAdminData` key holding the nonce for `action`.
 *                                   Defaults to 'addonNonce' (Mothership handlers).
 * @param {string} [props.busyLabel] Label shown while the request is in flight.
 *                                   Defaults to 'Activating…'.
 */
export function AddonActivateButton( { action, slug, label, className, nonceKey, busyLabel } ) {
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	// Hold the in-flight request's AbortController so the fetch can be
	// cancelled if the component unmounts before it resolves.
	const abortRef = useRef( null );

	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [] );

	const handleClick = () => {
		if ( busy ) {
			return;
		}

		const adminData = window.bbAdminData || {};
		const nonce = adminData[ nonceKey || 'addonNonce' ];

		if ( ! slug || ! action || ! nonce || ! adminData.ajaxUrl ) {
			return;
		}

		setBusy( true );
		setError( '' );

		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( '_ajax_nonce', nonce );
		formData.append( 'slug', slug );
		formData.append( 'extension_type', 'plugin' );

		const controller = new AbortController();
		abortRef.current = controller;

		fetch( adminData.ajaxUrl, { method: 'POST', body: formData, signal: controller.signal } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( response ) {
				if ( response && response.success ) {
					window.location.reload();
					return;
				}

				const message =
					response && response.data && response.data.message
						? response.data.message
						: __( 'Activation failed. Please try again.', 'buddyboss' );

				setError( message );
				setBusy( false );
			} )
			.catch( function ( err ) {
				// Ignore aborted requests (component unmounted).
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setError( __( 'Activation failed. Please try again.', 'buddyboss' ) );
				setBusy( false );
			} );
	};

	return (
		<>
			<button
				type="button"
				className={ className }
				onClick={ handleClick }
				disabled={ busy }
				aria-busy={ busy ? 'true' : undefined }
			>
				{ busy ? ( busyLabel || __( 'Activating…', 'buddyboss' ) ) : label }
			</button>
			{ error && (
				<p className="bb-admin-empty-state__error" role="alert">
					{ error }
				</p>
			) }
		</>
	);
}
