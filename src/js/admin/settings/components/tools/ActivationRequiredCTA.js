/**
 * Activation Required empty state for Sample Data + Migration Tools panels.
 *
 * Per Figma `migration-tool-if-plugin-not-activate.png`: a centered icon with
 * a title, description, and an Install Now / Activate Now action button.
 *
 * Detects via AJAX whether the buddyboss-tools plugin is installed but inactive
 * vs not installed at all, and shows the appropriate CTA button. After a
 * successful install/activate, reloads the page so the Tools React bundle can
 * pick up and register its own `bb_admin_settings_custom_field` filter.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function ActivationRequiredCTA() {
	const [ pluginState, setPluginState ] = useState( 'loading' );
	const [ isWorking, setIsWorking ] = useState( false );
	const [ error, setError ] = useState( null );
	const abortRef = useRef( null );

	useEffect( function () {
		const controller = new AbortController();
		abortRef.current = controller;

		const formData = new FormData();
		formData.append( 'action', 'bb_tools_check_plugin_state' );
		formData.append( '_ajax_nonce', ( window.bbAdminData && window.bbAdminData.ajaxNonce ) || '' );

		fetch( window.ajaxurl, {
			method: 'POST',
			body: formData,
			signal: controller.signal,
			credentials: 'same-origin',
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				if ( res && res.success && res.data ) {
					setPluginState( res.data.state );
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
	}, [] );

	const handleAction = useCallback( function () {
		if ( isWorking ) {
			return;
		}
		setIsWorking( true );
		setError( null );

		// Cancel any in-flight request from the initial state-probe useEffect
		// (or from a prior handleAction call) and track the new controller so
		// the unmount-cleanup can abort it.
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		const controller = new AbortController();
		abortRef.current = controller;

		const action = 'installed' === pluginState ? 'bb_tools_activate_plugin' : 'bb_tools_install_plugin';
		const formData = new FormData();
		formData.append( 'action', action );
		formData.append( '_ajax_nonce', ( window.bbAdminData && window.bbAdminData.ajaxNonce ) || '' );

		fetch( window.ajaxurl, {
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
					// Reload so the Tools React bundle loads and registers its filter.
					window.location.reload();
				} else {
					const message = ( res && res.data && res.data.message )
						|| __( 'Operation failed. Please try again.', 'buddyboss' );
					setError( message );
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
	}, [ pluginState, isWorking ] );

	if ( 'loading' === pluginState ) {
		return (
			<div className="bb-tools-activation-cta is-loading">
				<Spinner />
			</div>
		);
	}

	if ( 'active' === pluginState ) {
		// Edge case: plugin is active but the React filter for this panel
		// isn't registered yet (Phase 0 shell scenario). Show a passive notice
		// so the user knows the panel will appear once the addon updates.
		return (
			<div className="bb-tools-activation-cta">
				<div className="bb-tools-activation-cta__icon">
					<span className="bb-icons-rl bb-icons-rl-info"></span>
				</div>
				<h2 className="bb-tools-activation-cta__title">
					{ __( 'Coming Soon', 'buddyboss' ) }
				</h2>
				<p className="bb-tools-activation-cta__description">
					{ __( 'The BuddyBoss Tools addon is installed, but this feature is not yet available. Update the addon to use it.', 'buddyboss' ) }
				</p>
			</div>
		);
	}

	const buttonLabel = 'installed' === pluginState
		? __( 'Activate Now', 'buddyboss' )
		: __( 'Install Now', 'buddyboss' );

	return (
		<div className="bb-tools-activation-cta">
			<div className="bb-tools-activation-cta__icon">
				<span className="bb-icons-rl-info"></span>
			</div>
			<h2 className="bb-tools-activation-cta__title">
				{ __( 'Activation Required', 'buddyboss' ) }
			</h2>
			<p className="bb-tools-activation-cta__description">
				{ __( 'Please activate the migration addon to use the migration features.', 'buddyboss' ) }
			</p>
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			<Button
				variant="primary"
				onClick={ handleAction }
				isBusy={ isWorking }
				disabled={ isWorking }
			>
				{ buttonLabel }
			</Button>
		</div>
	);
}
