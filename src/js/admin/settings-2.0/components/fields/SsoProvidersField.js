/**
 * BuddyBoss Admin Settings 2.0 - SSO Providers Field Component
 *
 * Renders social login provider cards with icons, labels, and checkboxes.
 * The "..." menu shows a dropdown with "Edit" — clicking Edit triggers the
 * legacy SSO jQuery popup (bb-sso-admin.js).
 *
 * The component renders a `.bb-sso-list` wrapper with hidden inputs and
 * modal containers matching the legacy DOM structure so the existing
 * jQuery handler can find provider data and render the edit popup.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { CheckboxControl, Popover } from '@wordpress/components';
import { useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { safeUrl } from '../../utils/sanitize';
import { BB_EVENTS } from '../../utils/constants';

// Default brand icons — used as fallback when Pro doesn't provide icon URLs.
import googleIcon from '../../images/sso/google.png';
import facebookIcon from '../../images/sso/facebook.png';
import twitterIcon from '../../images/sso/twitter.png';
import linkedinIcon from '../../images/sso/linkedin.png';
import appleIcon from '../../images/sso/apple.png';

var DEFAULT_ICONS = {
	google: googleIcon,
	facebook: facebookIcon,
	twitter: twitterIcon,
	linkedin: linkedinIcon,
	apple: appleIcon,
};

/**
 * SSO Providers Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.field    Field configuration with providers array.
 * @param {*}        props.value    Current field value.
 * @param {Function} props.onChange Change handler.
 * @param {boolean}  props.disabled Whether the field is disabled.
 * @returns {JSX.Element} SSO providers field.
 */
export function SsoProvidersField( { field, value, onChange, disabled } ) {
	var providersState = useState( field.providers || [] );
	var providers = providersState[ 0 ];
	var setProviders = providersState[ 1 ];
	var openMenuState = useState( null );
	var openMenu = openMenuState[ 0 ];
	var setOpenMenu = openMenuState[ 1 ];
	var menuButtonRefs = useRef( {} );
	var savingRef = useRef( {} );

	/**
	 * Check if a provider is enabled.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} provider Provider data.
	 * @returns {boolean} Whether provider is enabled.
	 */
	var isProviderChecked = function ( provider ) {
		if ( undefined !== provider.enabled ) {
			return !! provider.enabled;
		}
		return false;
	};

	/**
	 * Toggle provider enabled/disabled via Pro SSO AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} provider Provider data.
	 */
	var handleProviderToggle = function ( provider ) {
		if ( savingRef.current[ provider.id ] || ! provider.tested ) {
			return;
		}
		savingRef.current[ provider.id ] = true;

		var oldState = provider.state;
		var newState = 'enabled' === oldState ? 'disabled' : 'enabled';

		// Optimistic update — toggle instantly.
		setProviders( function ( prev ) {
			return prev.map( function ( p ) {
				if ( p.id === provider.id ) {
					return Object.assign( {}, p, {
						enabled: 'enabled' === newState,
						state: newState,
					} );
				}
				return p;
			} );
		} );

		var ssoVars = window.bbSSOAdminVars || {};
		var formData = new FormData();
		formData.append( 'action', 'bb_sso_enable_provider' );
		formData.append( 'nonce', ssoVars.nonce || '' );
		formData.append( 'provider', provider.id );
		formData.append( 'state', oldState );

		fetch( ssoVars.ajax_url || window.bbAdminData.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( response ) {
				if ( response.success ) {
					window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
						detail: { status: 'success', message: __( 'Setting saved.', 'buddyboss' ) },
					} ) );
				} else {
					// Revert on failure.
					setProviders( function ( prev ) {
						return prev.map( function ( p ) {
							if ( p.id === provider.id ) {
								return Object.assign( {}, p, {
									enabled: 'enabled' === oldState,
									state: oldState,
								} );
							}
							return p;
						} );
					} );
					window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
						detail: { status: 'error', message: __( 'Failed to save.', 'buddyboss' ) },
					} ) );
				}
			} )
			.catch( function () {
				// Revert on error.
				setProviders( function ( prev ) {
					return prev.map( function ( p ) {
						if ( p.id === provider.id ) {
							return Object.assign( {}, p, {
								enabled: 'enabled' === oldState,
								state: oldState,
							} );
						}
						return p;
					} );
				} );
				window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
					detail: { status: 'error', message: __( 'Failed to save.', 'buddyboss' ) },
				} ) );
			} )
			.finally( function () {
				delete savingRef.current[ provider.id ];
			} );
	};

	/**
	 * Handle Edit click — trigger legacy SSO jQuery popup.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} providerId Provider ID.
	 */
	var handleEdit = function ( providerId ) {
		setOpenMenu( null );

		// Find the hidden edit trigger inside the .bb-sso-list wrapper and click it.
		var card = document.querySelector( '.bb-sso-list .bb-sso-item[data-provider="' + providerId + '"]' );
		if ( card ) {
			var trigger = card.querySelector( '.bb-box-item-edit--sso' );
			if ( trigger ) {
				trigger.click();
			}
		}
	};

	return (
		<div className="bb-admin-sso-providers bb-sso-list">
			{ /* Provider Cards */ }
			<div className="bb-admin-sso-providers__grid">
				{ providers.map( function ( provider ) {
					var checked = isProviderChecked( provider );
					var isMenuOpen = openMenu === provider.id;

					return (
						<div
							key={ provider.id }
							className={ 'bb-admin-sso-providers__card bb-sso-item' + ( ! checked ? ' bb-admin-sso-providers__card--disabled is-disabled' : '' ) }
							data-provider={ provider.id }
							data-state={ provider.state || '' }
						>
							{ /* Hidden trigger for legacy jQuery handler — visible to jQuery event delegation */ }
							<button
								type="button"
								className="bb-box-item-edit bb-box-item-edit--sso bb-admin-sso-providers__edit-trigger"
								aria-hidden="true"
								tabIndex={ -1 }
							>
								<i className="bb-icon-l bb-icon-pencil"></i>
							</button>

							<div className="bb-admin-sso-providers__card-icon">
								{ provider.icon ? (
									<img
										src={ safeUrl( provider.icon ) }
										alt={ provider.label }
									/>
								) : DEFAULT_ICONS[ provider.id ] ? (
									<img
										src={ DEFAULT_ICONS[ provider.id ] }
										alt={ provider.label }
									/>
								) : (
									<span className="bb-admin-sso-providers__card-icon-placeholder">
										{ provider.label.charAt( 0 ) }
									</span>
								) }
								<CheckboxControl
								checked={ checked }
								className="bb-admin-sso-providers__card-checkbox"
								disabled={ disabled || ! provider.tested }
								onChange={ function () {
									handleProviderToggle( provider );
								} }
								__nextHasNoMarginBottom
							/>
							</div>

							<div className="bb-admin-sso-providers__card-footer">
								<span className="bb-admin-sso-providers__card-label">
									{ provider.label }
								</span>
								<button
									type="button"
									ref={ function ( el ) {
										menuButtonRefs.current[ provider.id ] = el;
									} }
									className="bb-admin-sso-providers__card-menu"
									disabled={ disabled }
									aria-label={ provider.label + ' ' + __( 'options', 'buddyboss' ) }
									onClick={ function () {
										setOpenMenu( isMenuOpen ? null : provider.id );
									} }
								>
									<i className="bb-icons-rl-dots-three"></i>
								</button>

								{ isMenuOpen && menuButtonRefs.current[ provider.id ] && (
									<Popover
										anchor={ menuButtonRefs.current[ provider.id ] }
										position="bottom right"
										onClose={ function () {
											setOpenMenu( null );
										} }
										className="bb-admin-sso-providers__menu-popover"
									>
										<div className="bb-admin-sso-providers__menu">
											<button
												type="button"
												className="bb-admin-sso-providers__menu-item"
												onClick={ function () {
													handleEdit( provider.id );
												} }
											>
												<i className="bb-icon-l bb-icon-pencil"></i>
												{ __( 'Edit', 'buddyboss' ) }
											</button>
										</div>
									</Popover>
								) }
							</div>
						</div>
					);
				} ) }
			</div>

			{ /* Hidden inputs per provider — legacy jQuery reads data-hidden-attr for popup */ }
			{ providers.map( function ( provider ) {
				var hiddenAttr = JSON.stringify( {
					url: provider.test_url || '',
					width: provider.popup_width || 800,
					height: provider.popup_height || 600,
					test_status: provider.tested || 0,
					state: provider.state || 'not-configured',
				} );

				return (
					<input
						key={ 'hidden_' + provider.id }
						type="hidden"
						id={ 'sso_validate_popup_' + provider.id + '_data' }
						name={ 'sso_validate_popup_' + provider.id + '_data' }
						defaultValue=""
						data-hidden-attr={ hiddenAttr }
					/>
				);
			} ) }

			{ /* Modal backdrop + container — legacy jQuery renders content here */ }
			<div
				id="bb-hello-backdrop"
				className="bb-hello-backdrop-sso bb-modal-backdrop"
				style={ { display: 'none' } }
			></div>
			<div
				id="bb-hello-container"
				className="bb-hello-sso bb-modal-panel bb-modal-panel--sso"
				role="dialog"
				aria-labelledby="bb-hello-title"
				style={ { display: 'none' } }
			></div>
		</div>
	);
}
