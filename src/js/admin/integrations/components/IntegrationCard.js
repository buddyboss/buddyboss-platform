/**
 * BuddyBoss Integrations marketplace — single card.
 *
 * Matches the Figma card: circular bordered logo, title, category subtitle,
 * 3-line-clamped description, and the action buttons. Clicking the title opens
 * the detail drawer (the card body itself is not clickable).
 *
 * The primary action (Install / Activate / Deactivate / disabled) is the shared
 * <PluginActionButton>; "Learn More ↗" (secondary, right) → acf.plugin_link.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { memo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { safeUrl, safeImageUrl } from '@bb/admin-common';
import { PluginActionButton } from './PluginActionButton';

function IntegrationCardComponent( { item, categoryMap, plugins, onSelect } ) {
	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';
	// safeImageUrl returns '' for a non-http(s) URL — fall back to the placeholder.
	const logoSrc = logo ? safeImageUrl( logo ) : '';

	// Plan — "free" (case-insensitive) is free; anything else non-empty is paid.
	const planLabel = ( item?.acf?.type_label || '' ).trim().toLowerCase();
	const isPaid = '' !== planLabel && 'free' !== planLabel;

	// "Learn More ↗" → the integration's site URL (acf.integration_site_url).
	const learnMoreUrl = item?.acf?.integration_site_url || '';

	// Subtitle = the integration's category (integrations_category term ID → name).
	const categoryId = Array.isArray( item?.integrations_category ) ? item.integrations_category[ 0 ] : null;
	const categoryName = categoryId && categoryMap && categoryMap[ categoryId ] ? decodeEntities( categoryMap[ categoryId ] ) : '';

	const open = () => onSelect( item.slug, title );

	return (
		<div className="bb-integrations__card">
			<div className="bb-integrations__card-body">
				<div className="bb-integrations__card-top">
					<span className="bb-integrations__card-logo">
						{ logoSrc ? (
							<img src={ logoSrc } alt="" />
						) : (
							<i className="bb-icons-rl bb-icons-rl-puzzle-piece" aria-hidden="true" />
						) }
					</span>
					{ isPaid && (
						<span className="bb-integrations__card-badge">
							<i className="bb-icons-rl bb-icons-rl-crown-simple" aria-hidden="true" />
							<span>{ __( 'PRO', 'buddyboss-platform' ) }</span>
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
							aria-haspopup="dialog"
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
				<PluginActionButton item={ item } plugins={ plugins } />
				{ learnMoreUrl && (
					<a
						href={ safeUrl( learnMoreUrl ) }
						className="bb-integrations__btn bb-integrations__btn--link"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Learn More', 'buddyboss-platform' ) }
						<i className="bb-icons-rl bb-icons-rl-arrow-up-right" aria-hidden="true" />
					</a>
				) }
			</div>
		</div>
	);
}

// Memoized: the grid re-renders on every App state change (e.g. after a plugin
// action updates the installed map), but a card only needs to re-render when its
// own props change.
export const IntegrationCard = memo( IntegrationCardComponent );
