/**
 * BuddyBoss Integrations marketplace — single card.
 *
 * Matches the Figma card: circular bordered logo, title, vendor subtitle,
 * 3-line-clamped description, and an orange-outline action button. Clicking the
 * card body opens the detail drawer.
 *
 * API FIELD CONTRACT (team populates these on the wp/v2/integrations response;
 * the card reads them and lights up automatically when present — all optional):
 *  - install_url (string URL): when set, renders a primary "Install" button → this URL.
 *  - plugin_url (string URL): the plugin's site — the "Learn More" button destination
 *    (falls back to `link` / `link_url` when not provided).
 *  - vendor_name (string): author/vendor subtitle under the title.
 *  - tier ('free' | 'pro'): when 'pro', renders the PRO badge.
 * Install + Learn More can both show when both URLs exist.
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
	// "Learn More" → the plugin's plugin_url (team contract), falling back to the
	// existing link / link_url so it keeps working until plugin_url is populated.
	const learnMoreUrl = item?.plugin_url || item?.link || item?.link_url || '';

	// API field contract (all optional; render only when the API provides them).
	const installUrl = item?.install_url && 'string' === typeof item.install_url ? item.install_url : '';
	const vendorName = item?.vendor_name ? decodeEntities( item.vendor_name ) : '';
	const isPro = 'pro' === item?.tier;

	const open = () => onSelect( item.slug, title );

	return (
		<div className="bb-integrations__card">
			<button
				type="button"
				className="bb-integrations__card-body"
				onClick={ open }
				aria-label={ title }
			>
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
					<span className="bb-integrations__card-title">{ title }</span>
					{ vendorName && (
						<span className="bb-integrations__card-vendor">{ vendorName }</span>
					) }
					<span className="bb-integrations__card-desc">{ description }</span>
				</div>
			</button>

			<div className="bb-integrations__card-actions">
				{ /* install_url → "Install" (primary); plugin_url → "Learn More". Both
				     can show; if neither URL exists, the button opens the drawer. */ }
				{ installUrl && (
					<a
						href={ safeUrl( installUrl ) }
						className="bb-integrations__btn bb-integrations__btn--primary"
					>
						{ __( 'Install', 'buddyboss' ) }
					</a>
				) }
				{ learnMoreUrl && (
					<a
						href={ safeUrl( learnMoreUrl ) }
						className={ 'bb-integrations__btn' + ( installUrl ? '' : ' bb-integrations__btn--primary' ) }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Learn More', 'buddyboss' ) }
					</a>
				) }
				{ ! installUrl && ! learnMoreUrl && (
					<button
						type="button"
						className="bb-integrations__btn bb-integrations__btn--primary"
						onClick={ open }
					>
						{ __( 'Learn More', 'buddyboss' ) }
					</button>
				) }
			</div>
		</div>
	);
}
