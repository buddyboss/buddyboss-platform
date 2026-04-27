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
import { useState, useRef, useEffect, useCallback } from '@wordpress/element';
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
	var toastTimerRef = useRef( null );
	var lastDisabledStateRef = useRef( null );
	var dragIndexRef = useRef( null );
	var dragOverIndexRef = useRef( null );
	var dragOverState = useState( null );
	var dragOverId = dragOverState[ 0 ];
	var setDragOverId = dragOverState[ 1 ];

	// Cleanup toast debounce timer on unmount.
	useEffect( function () {
		return function () {
			clearTimeout( toastTimerRef.current );
		};
	}, [] );

	/**
	 * Intercept SSO modal close/cancel clicks to prevent legacy jQuery
	 * handler (bb-sso-admin.js) from reloading the page.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	useEffect( function () {
		var handler = function ( e ) {
			var target = e.target;
			if (
				! target.closest( '#bb-hello-container' )
			) {
				return;
			}
			if (
				target.matches( '.close-modal' ) ||
				target.closest( '.close-modal' ) ||
				target.matches( '#sso_cancel' ) ||
				target.closest( '#sso_cancel' )
			) {
				e.stopImmediatePropagation();
				e.preventDefault();

				var container = document.getElementById( 'bb-hello-container' );
				var backdrop = document.getElementById( 'bb-hello-backdrop' );
				if ( container ) {
					container.style.display = 'none';
				}
				if ( backdrop ) {
					backdrop.style.display = 'none';
				}
				document.body.classList.remove( 'bp-disable-scroll' );
			}
		};

		// Capture phase runs before jQuery delegated handlers.
		document.addEventListener( 'click', handler, true );
		return function () {
			document.removeEventListener( 'click', handler, true );
		};
	}, [] );

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

		// Show "Saving changes..." toast immediately.
		window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
			detail: { status: 'saving', message: __( 'Saving changes...', 'buddyboss' ) },
		} ) );

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
					// Debounce toast — show one "saved" after rapid toggles settle.
					clearTimeout( toastTimerRef.current );
					toastTimerRef.current = setTimeout( function () {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
							detail: { status: 'success', message: __( 'Settings saved.', 'buddyboss' ) },
						} ) );
					}, 500 );

					// Check if only Twitter/X is enabled — disable additional data fields.
					// Skip dispatch if state hasn't changed to avoid unnecessary re-renders.
					setProviders( function ( current ) {
						var enabledIds = current.filter( function ( p ) { return 'enabled' === p.state; } ).map( function ( p ) { return p.id; } );
						var onlyTwitter = 1 === enabledIds.length && 'twitter' === enabledIds[0];
						var shouldDisable = onlyTwitter || 0 === enabledIds.length;

						if ( lastDisabledStateRef.current !== shouldDisable ) {
							lastDisabledStateRef.current = shouldDisable;
							window.dispatchEvent( new CustomEvent( BB_EVENTS.FIELD_DISABLED_UPDATE, {
								detail: {
									fields: [ 'bb-additional-sso-name', 'bb-additional-sso-profile-picture' ],
									disabled: shouldDisable,
								},
							} ) );
						}

						return current;
					} );
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

	/**
	 * Handle drag start — store the dragged index.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} index Index of the dragged provider.
	 */
	var handleDragStart = useCallback( function ( index ) {
		dragIndexRef.current = index;
	}, [] );

	/**
	 * Handle drag over — track which card we're hovering.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Event}  e     Drag event.
	 * @param {number} index Index of the hovered provider.
	 */
	var handleDragOver = useCallback( function ( e, index, providerId ) {
		e.preventDefault();
		dragOverIndexRef.current = index;
		setDragOverId( providerId );
	}, [] );

	/**
	 * Handle drop — reorder providers and save via AJAX.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleDrop = useCallback( function () {
		var fromIndex = dragIndexRef.current;
		var toIndex = dragOverIndexRef.current;

		dragIndexRef.current = null;
		dragOverIndexRef.current = null;
		setDragOverId( null );

		if ( null === fromIndex || null === toIndex || fromIndex === toIndex ) {
			return;
		}

		// Compute new order before setting state so AJAX can use it synchronously.
		var newProviders = providers.slice();
		var moved = newProviders.splice( fromIndex, 1 )[ 0 ];
		newProviders.splice( toIndex, 0, moved );

		setProviders( newProviders );

		var ordering = newProviders.map( function ( p ) {
			return p.id;
		} );

		var ssoNonce = window.bbSSOAdminVars ? window.bbSSOAdminVars.nonce : '';
		var formData = new FormData();
		formData.append( 'action', 'bb-social-login' );
		formData.append( 'nonce', ssoNonce );
		formData.append( 'view', 'orderProviders' );
		ordering.forEach( function ( id ) {
			formData.append( 'ordering[]', id );
		} );

		// Show "Saving changes..." toast immediately.
		window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
			detail: { status: 'saving', message: __( 'Saving changes...', 'buddyboss' ) },
		} ) );

		fetch( window.bbAdminData.ajaxUrl, {
			method: 'POST',
			body: formData,
		} ).then( function ( response ) {
			return response.json();
		} ).then( function ( result ) {
			if ( result.success ) {
				window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
					detail: { status: 'success', message: __( 'Settings saved.', 'buddyboss' ) },
				} ) );
			} else {
				window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
					detail: { status: 'error', message: result.data && result.data.message ? result.data.message : __( 'Failed to save order.', 'buddyboss' ) },
				} ) );
			}
		} ).catch( function () {
			window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
				detail: { status: 'error', message: __( 'Failed to save order.', 'buddyboss' ) },
			} ) );
		} );
	}, [ providers ] );

	return (
		<div className="bb-admin-sso-providers bb-sso-list">
			{ /* Provider Cards — draggable for reordering */ }
			<div className="bb-admin-sso-providers__grid">
				{ providers.map( function ( provider, index ) {
					var checked = isProviderChecked( provider );
					var isMenuOpen = openMenu === provider.id;

					return (
						<div
							key={ provider.id }
							className={ 'bb-admin-sso-providers__card bb-sso-item' + ( ! checked ? ' bb-admin-sso-providers__card--disabled is-disabled' : '' ) + ( dragOverId === provider.id && dragIndexRef.current !== index ? ' bb-admin-sso-providers__card--drag-over' : '' ) }
							draggable={ ! disabled }
							onDragStart={ function () {
								handleDragStart( index );
							} }
							onDragOver={ function ( e ) {
								handleDragOver( e, index, provider.id );
							} }
							onDrop={ handleDrop }
							onDragEnd={ function () {
								dragIndexRef.current = null;
								dragOverIndexRef.current = null;
								setDragOverId( null );
							} }
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
												<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
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
