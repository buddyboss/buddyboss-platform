/**
 * BuddyBoss Integrations marketplace — plugin action button.
 *
 * Shared by the card and the detail drawer so both stay in sync. Renders the
 * state-driven primary button:
 *  - Free + acf.plugin_link is a wordpress.org URL → Install → Activate →
 *    Deactivate, from the localized installed-plugin map. Install uses core
 *    wp.updates; activate/deactivate hit our guarded AJAX. Gated on the user's
 *    install_plugins / activate_plugins capabilities.
 *  - Pro/Premium, or free with a non-wordpress.org link → disabled "Install"
 *    (or nothing at all when `hideUnavailable` is set — e.g. the drawer).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { wporgSlug } from '../../utils/pluginActions';

export function PluginActionButton( { item, plugins, className, hideUnavailable } ) {
	const planLabel = ( item?.acf?.type_label || '' ).trim().toLowerCase();
	const isPaid = '' !== planLabel && 'free' !== planLabel;
	const pluginLink = item?.acf?.plugin_link && 'string' === typeof item.acf.plugin_link ? item.acf.plugin_link : '';
	const slug = isPaid ? null : wporgSlug( pluginLink );

	const installedMap = ( plugins && plugins.installed ) || {};
	const entry = slug ? installedMap[ slug ] : null;
	const isInstalled = !! entry;
	const isActive = !! ( entry && entry.active );
	const canInstall = !! ( plugins && plugins.canInstall );
	const canActivate = !! ( plugins && plugins.canActivate );

	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	const run = useCallback( async ( handler ) => {
		if ( ! slug || ! handler ) {
			return;
		}
		setBusy( true );
		setError( '' );
		try {
			await handler( slug );
		} catch ( e ) {
			setError( ( e && e.message ) || __( 'Something went wrong. Please try again.', 'buddyboss' ) );
		} finally {
			setBusy( false );
		}
	}, [ slug ] );

	// No wordpress.org slug (pro plugins, or free plugins whose link isn't a wp.org
	// URL) → a disabled "Install" (matches Figma); a wp.org slug → the action flow.
	let primary;
	if ( ! slug ) {
		primary = { kind: 'disabled', label: __( 'Install', 'buddyboss' ) };
	} else if ( ! isInstalled ) {
		primary = { kind: 'action', label: __( 'Install', 'buddyboss' ), busyLabel: __( 'Installing…', 'buddyboss' ), onClick: () => run( plugins.onInstall ), disabled: ! canInstall };
	} else if ( ! isActive ) {
		primary = { kind: 'action', label: __( 'Activate', 'buddyboss' ), busyLabel: __( 'Activating…', 'buddyboss' ), onClick: () => run( plugins.onActivate ), disabled: ! canActivate };
	} else {
		primary = { kind: 'action', label: __( 'Deactivate', 'buddyboss' ), busyLabel: __( 'Deactivating…', 'buddyboss' ), onClick: () => run( plugins.onDeactivate ), disabled: ! canActivate };
	}

	// In contexts that shouldn't surface a dead control (the drawer), render nothing
	// when the plugin isn't installable in-place (pro, or a non-wordpress.org link).
	if ( ! slug && hideUnavailable ) {
		return null;
	}

	return (
		<span className="bb-integrations__action">
			<button
				type="button"
				className={ className || 'bb-integrations__btn bb-integrations__btn--primary' }
				onClick={ primary.onClick }
				disabled={ 'disabled' === primary.kind || primary.disabled || busy }
				aria-busy={ busy ? 'true' : undefined }
			>
				{ busy ? primary.busyLabel : primary.label }
			</button>
			{ error && (
				<span className="bb-integrations__action-error" role="alert">{ error }</span>
			) }
		</span>
	);
}
