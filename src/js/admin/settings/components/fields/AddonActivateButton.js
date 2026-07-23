/**
 * Add-on activate/install button for empty-state fields.
 *
 * Triggers the Mothership AJAX flow (mosh_addon_activate / mosh_addon_install)
 * in place of a full-page plugins.php redirect, so an installed-but-inactive
 * add-on can be activated without leaving the Settings screen. On success the
 * page reloads so the panel re-renders with the now-active add-on's real
 * settings instead of the upsell placeholder.
 *
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * @param {Object} props
 * @param {string} props.action    Mothership AJAX action ('mosh_addon_activate' | 'mosh_addon_install').
 * @param {string} props.slug      Plugin folder slug (e.g. 'buddyboss-member-blogging').
 * @param {string} props.label     Button label.
 * @param {string} [props.className] Optional CSS class for the button.
 */
export function AddonActivateButton( { action, slug, label, className } ) {
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	const handleClick = () => {
		if ( busy ) {
			return;
		}

		const adminData = window.bbAdminData || {};

		if ( ! slug || ! action || ! adminData.addonNonce || ! adminData.ajaxUrl ) {
			return;
		}

		setBusy( true );
		setError( '' );

		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( '_ajax_nonce', adminData.addonNonce );
		formData.append( 'slug', slug );
		formData.append( 'extension_type', 'plugin' );

		fetch( adminData.ajaxUrl, { method: 'POST', body: formData } )
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
			.catch( function () {
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
				{ busy ? __( 'Activating…', 'buddyboss' ) : label }
			</button>
			{ error && (
				<p className="bb-admin-empty-state__error" role="alert">
					{ error }
				</p>
			) }
		</>
	);
}
