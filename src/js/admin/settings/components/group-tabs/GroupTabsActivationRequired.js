/**
 * Custom Group Tabs — Activation Required empty state.
 *
 * Rendered in the Social Groups → Custom Group Tabs panel when BuddyBoss Platform
 * Pro is inactive. Probes Pro's state and offers the matching action — Install,
 * Activate, or Activate License — reloading on success so Pro's own field renderer
 * replaces this CTA.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Render the Activation Required CTA for Custom Group Tabs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {JSX.Element} The CTA element.
 */
export default function GroupTabsActivationRequired() {
	const [ pluginState, setPluginState ] = useState( 'loading' );
	const [ isWorking, setIsWorking ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ licenseUrl, setLicenseUrl ] = useState( '' );
	const abortRef = useRef( null );

	const ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl ) || window.ajaxurl;
	const ajaxNonce = ( window.bbAdminData && window.bbAdminData.ajaxNonce ) || '';

	useEffect( function () {
		const controller = new AbortController();
		abortRef.current = controller;

		const formData = new FormData();
		formData.append( 'action', 'bb_group_tabs_addon_check_state' );
		formData.append( '_ajax_nonce', ajaxNonce );

		fetch( ajaxUrl, {
			method:      'POST',
			body:        formData,
			signal:      controller.signal,
			credentials: 'same-origin',
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( res && res.success && res.data ) {
					setPluginState( res.data.state );
					if ( res.data.license_url ) {
						setLicenseUrl( res.data.license_url );
					}
				} else {
					setPluginState( 'not-installed' );
				}
			} )
			.catch( function ( e ) {
				if ( 'AbortError' !== e.name ) {
					setPluginState( 'not-installed' );
				}
			} );

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [ ajaxUrl, ajaxNonce ] );

	const handleAction = useCallback( function () {
		if ( isWorking ) {
			return;
		}
		setIsWorking( true );
		setError( null );

		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		const controller = new AbortController();
		abortRef.current = controller;

		const action = 'installed' === pluginState ? 'bb_group_tabs_addon_activate' : 'bb_group_tabs_addon_install';
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( '_ajax_nonce', ajaxNonce );

		fetch( ajaxUrl, {
			method:      'POST',
			body:        formData,
			credentials: 'same-origin',
			signal:      controller.signal,
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( res && res.success ) {
					window.location.reload();
				} else {
					const message = ( res && res.data && res.data.message )
						|| __( 'Operation failed. Please try again.', 'buddyboss' );
					setError( message );
					if ( res && res.data && res.data.license_url ) {
						setLicenseUrl( res.data.license_url );
						setPluginState( 'needs-license' );
					}
					setIsWorking( false );
				}
			} )
			.catch( function ( err ) {
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setError( __( 'Network error. Please try again.', 'buddyboss' ) );
				setIsWorking( false );
			} );
	}, [ pluginState, isWorking, ajaxUrl, ajaxNonce ] );

	if ( 'loading' === pluginState ) {
		return (
			<div className="bb-tools-activation-cta is-loading">
				<Spinner />
			</div>
		);
	}

	if ( 'active' === pluginState ) {
		return (
			<div className="bb-tools-activation-cta">
				<div className="bb-tools-activation-cta__icon">
					<span className="bb-icons-rl bb-icons-rl-info"></span>
				</div>
				<h2 className="bb-tools-activation-cta__title">
					{ __( 'Coming Soon', 'buddyboss' ) }
				</h2>
				<p className="bb-tools-activation-cta__description">
					{ __( 'BuddyBoss Platform Pro is active, but this feature is not yet available. Update BuddyBoss Platform Pro to use it.', 'buddyboss' ) }
				</p>
			</div>
		);
	}

	if ( 'needs-license' === pluginState ) {
		return (
			<div className="bb-tools-activation-cta">
				<div className="bb-tools-activation-cta__icon">
					<span className="bb-icons-rl bb-icons-rl-info"></span>
				</div>
				<h2 className="bb-tools-activation-cta__title">
					{ __( 'License Required', 'buddyboss' ) }
				</h2>
				<p className="bb-tools-activation-cta__description">
					{ __( 'Please activate your BuddyBoss license to install and use this feature.', 'buddyboss' ) }
				</p>
				<Button variant="primary" href={ licenseUrl || '#' }>
					{ __( 'Activate License', 'buddyboss' ) }
				</Button>
			</div>
		);
	}

	const buttonLabel = 'installed' === pluginState
		? __( 'Activate Now', 'buddyboss' )
		: __( 'Install Now', 'buddyboss' );

	return (
		<div className="bb-tools-activation-cta">
			<div className="bb-tools-activation-cta__icon">
				<span className="bb-icons-rl bb-icons-rl-info"></span>
			</div>
			<h2 className="bb-tools-activation-cta__title">
				{ __( 'Activation Required', 'buddyboss' ) }
			</h2>
			<p className="bb-tools-activation-cta__description">
				{ __( 'Please activate the BuddyBoss Platform Pro addon to use this feature.', 'buddyboss' ) }
			</p>
			{ error && (
				<div className="bb-admin-notice bb-admin-notice--error">
					{ error }
				</div>
			) }
			<Button
				variant="primary"
				onClick={ handleAction }
				disabled={ isWorking }
			>
				{ buttonLabel }
			</Button>
		</div>
	);
}
