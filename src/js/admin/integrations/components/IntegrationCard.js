/**
 * BuddyBoss Integrations marketplace — single card.
 *
 * Matches the Figma card: circular bordered logo, title, vendor subtitle,
 * 3-line-clamped description, and an orange-outline action button. Clicking the
 * title opens the detail drawer (the card body itself is not clickable).
 *
 * API FIELD CONTRACT (team populates these on the wp/v2/integrations response;
 * the card reads them and lights up automatically when present — all optional):
 *  - install_url (string URL): the "Install" button → this URL. When absent, the
 *    Install button still renders but is disabled (greyed), and "Learn More" shows beside it.
 *  - plugin_url (string URL): the plugin's site — the "Learn More ↗" destination
 *    (falls back to `link` / `link_url`). Learn More shows when there's no
 *    install_url, or alongside Install when plugin_url is provided.
 *  - vendor_name (string): author/vendor subtitle under the title.
 *  - tier ('free' | 'pro'): when 'pro', renders the PRO badge.
 * See docs/superpowers/specs/2026-06-25-integrations-api-field-contract.md.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { safeUrl } from '@bb/admin-common';

export function IntegrationCard( { item, onSelect } ) {
	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';
	// API field contract (all optional; render only when the API provides them).
	const installUrl = item?.install_url && 'string' === typeof item.install_url ? item.install_url : '';
	// "Learn More" destination: prefer the dedicated plugin_url, fall back to the
	// integration page (link / link_url) so there is always somewhere to go.
	const pluginUrl = item?.plugin_url && 'string' === typeof item.plugin_url ? item.plugin_url : '';
	const learnMoreUrl = pluginUrl || item?.link || item?.link_url || '';
	// Show "Learn More" when there's no install_url (never show a dead Install), or
	// alongside Install when the API provides a dedicated plugin_url (the pro case).
	const showLearnMore = learnMoreUrl && ( ! installUrl || pluginUrl );
	const vendorName = item?.vendor_name ? decodeEntities( item.vendor_name ) : '';
	const isPro = 'pro' === item?.tier;

	const open = () => onSelect( item.slug, title );

	return (
		<div className="bb-integrations__card">
			<div className="bb-integrations__card-body">
				<div className="bb-integrations__card-top">
					<span className="bb-integrations__card-logo">
						{ logo ? (
							<img src={ safeUrl( logo ) } alt="" />
						) : (
							<i className="bb-icons-rl bb-icons-rl-puzzle-piece" aria-hidden="true" />
						) }
					</span>
					{ isPro && (
						<span className="bb-integrations__card-badge">{ __( 'PRO', 'buddyboss' ) }</span>
					) }
				</div>

				<div className="bb-integrations__card-text">
					{ /* Only the title opens the detail drawer. */ }
					<button
						type="button"
						className="bb-integrations__card-title"
						onClick={ open }
					>
						{ title }
					</button>
					{ vendorName && (
						<span className="bb-integrations__card-vendor">{ vendorName }</span>
					) }
					<span className="bb-integrations__card-desc">{ description }</span>
				</div>
			</div>

			<div className="bb-integrations__card-actions">
				{ /* Install — primary, left. Active link when install_url exists; otherwise
				     a disabled (greyed) button so the card layout stays consistent. */ }
				{ installUrl ? (
					<a
						href={ safeUrl( installUrl ) }
						className="bb-integrations__btn bb-integrations__btn--primary"
					>
						{ __( 'Install', 'buddyboss' ) }
					</a>
				) : (
					<button
						type="button"
						className="bb-integrations__btn bb-integrations__btn--primary"
						disabled
						aria-disabled="true"
					>
						{ __( 'Install', 'buddyboss' ) }
					</button>
				) }
				{ /* Learn More — borderless + ↗. Shown when there's no install_url, or
				     alongside Install (right) when a dedicated plugin_url exists. */ }
				{ showLearnMore && (
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
		</div>
	);
}
