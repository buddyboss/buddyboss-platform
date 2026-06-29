/**
 * BuddyBoss Integrations marketplace — single card.
 *
 * Matches the Figma card: circular bordered logo, title, category subtitle,
 * 3-line-clamped description, and the action buttons. Clicking the title opens
 * the detail drawer (the card body itself is not clickable).
 *
 * Action button matrix (primary, left):
 *  - Free + acf.plugin_link is a wordpress.org URL → derive the slug and show
 *    Install → Activate → Deactivate based on the localized installed-plugin map
 *    (window.bbIntegrationsPlugins). Install uses core wp.updates; activate/
 *    deactivate hit our nonce + capability-guarded AJAX. Buttons are gated on the
 *    user's install_plugins / activate_plugins capabilities.
 *  - Pro/Premium, or free with a non-wordpress.org link (e.g. buddyboss.com) →
 *    a disabled "Install" (not installable in-place; matches Figma).
 * "Learn More ↗" (secondary, right) → acf.plugin_link.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { safeUrl, safeImageUrl } from '@bb/admin-common';
import { wporgSlug } from '../../utils/pluginActions';

export function IntegrationCard( { item, categoryMap, plugins, onSelect } ) {
	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';

	// Plan — "free" (case-insensitive) is free; anything else non-empty is paid.
	const planLabel = ( item?.acf?.type_label || '' ).trim().toLowerCase();
	const isPaid = '' !== planLabel && 'free' !== planLabel;

	// Links. plugin_link = wp.org URL (free) / purchase URL (pro) / vendor site.
	// "Learn More ↗" → acf.plugin_link, falling back to the integration page so
	// there is always somewhere to go.
	const pluginLink = item?.acf?.plugin_link && 'string' === typeof item.acf.plugin_link ? item.acf.plugin_link : '';
	const learnMoreUrl = pluginLink || item?.plugin_url || item?.link || item?.link_url || '';

	// Free wordpress.org plugins are installable in-place; non-wp.org links are not.
	const slug = isPaid ? null : wporgSlug( pluginLink );
	const installedMap = ( plugins && plugins.installed ) || {};
	const entry = slug ? installedMap[ slug ] : null;
	const isInstalled = !! entry;
	const isActive = !! ( entry && entry.active );
	const canInstall = !! ( plugins && plugins.canInstall );
	const canActivate = !! ( plugins && plugins.canActivate );

	// Subtitle = the integration's category (integrations_category term ID → name).
	const categoryId = Array.isArray( item?.integrations_category ) ? item.integrations_category[ 0 ] : null;
	const categoryName = categoryId && categoryMap && categoryMap[ categoryId ] ? decodeEntities( categoryMap[ categoryId ] ) : '';

	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );

	const open = () => onSelect( item.slug, title );

	// Run a plugin action with busy + error handling.
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

	// Resolve the primary (left) action. No wordpress.org slug — pro plugins, or
	// free plugins whose link isn't a wp.org URL — render a disabled "Install"
	// (matches Figma); a wp.org slug → Install / Activate / Deactivate.
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

	return (
		<div className="bb-integrations__card">
			<div className="bb-integrations__card-body">
				<div className="bb-integrations__card-top">
					<span className="bb-integrations__card-logo">
						{ logo ? (
							<img src={ safeImageUrl( logo ) } alt="" />
						) : (
							<i className="bb-icons-rl bb-icons-rl-puzzle-piece" aria-hidden="true" />
						) }
					</span>
					{ isPaid && (
						<span className="bb-integrations__card-badge">
							<i className="bb-icons-rl bb-icons-rl-crown-simple" aria-hidden="true" />
							<span>{ __( 'PRO', 'buddyboss' ) }</span>
						</span>
					) }
				</div>

				<div className="bb-integrations__card-text">
					{ /* Title + category sit tight together; the 16px gap is before the description. */ }
					<div className="bb-integrations__card-heading">
						{ /* Only the title opens the detail drawer. */ }
						<button
							type="button"
							className="bb-integrations__card-title"
							onClick={ open }
						>
							{ title }
						</button>
						{ categoryName && (
							<span className="bb-integrations__card-category">{ categoryName }</span>
						) }
					</div>
					<span className="bb-integrations__card-desc">{ description }</span>
				</div>
			</div>

			<div className="bb-integrations__card-actions">
				<button
					type="button"
					className="bb-integrations__btn bb-integrations__btn--primary"
					onClick={ primary.onClick }
					disabled={ 'disabled' === primary.kind || primary.disabled || busy }
					aria-busy={ busy ? 'true' : undefined }
				>
					{ busy ? primary.busyLabel : primary.label }
				</button>
				{ learnMoreUrl && (
					<a
						href={ safeUrl( learnMoreUrl ) }
						className="bb-integrations__btn bb-integrations__btn--link"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Learn More', 'buddyboss' ) }
						<i className="bb-icons-rl bb-icons-rl-arrow-up-right" aria-hidden="true" />
					</a>
				) }
			</div>

			{ error && (
				<p className="bb-integrations__card-error" role="alert">{ error }</p>
			) }
		</div>
	);
}
