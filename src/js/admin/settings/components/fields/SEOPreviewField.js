/**
 * BuddyBoss Admin Settings 2.0 — SEOPreviewField Component
 *
 * Renders a search-result preview card that reflects the current
 * SEO title and description values being edited. Used by
 * Appearance → Site SEO → SEO.
 *
 * Layout (per Figma):
 *   - Site icon (brand circle) + site name (bold) / site URL stacked to the right
 *   - Blue SERP-style title below
 *   - Gray description (clamped with ellipsis)
 *
 * Field config keys (from PHP):
 *   - `preview_config.site_name`        (string) Site name shown next to the icon.
 *   - `preview_config.site_url`         (string) Site URL shown under the name.
 *   - `preview_config.site_icon`        (string) Site icon URL (optional — brand fallback with first letter).
 *   - `preview_config.title_key`        (string) Field name to read title from (default: "buddyboss_seo_title").
 *   - `preview_config.description_key`  (string) Field name to read description from (default: "buddyboss_seo_description").
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { safeUrl } from '../../utils/sanitize';

/**
 * SEOPreviewField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props        Component props.
 * @param {Object} props.field  Field config (includes preview_config).
 * @param {Object} props.values Current form values map — used to read title/description.
 * @returns {JSX.Element} Preview card.
 */
export function SEOPreviewField( { field, values } ) {
	var config         = field.preview_config || {};
	var titleKey       = config.title_key || 'buddyboss_seo_title';
	var descriptionKey = config.description_key || 'buddyboss_seo_description';

	var title       = values && values[ titleKey ]       ? String( values[ titleKey ] )       : '';
	var description = values && values[ descriptionKey ] ? String( values[ descriptionKey ] ) : '';

	var siteName = config.site_name || '';
	var siteUrl  = config.site_url  || '';
	var siteIcon = config.site_icon || '';

	var displayTitle = title || __( 'Your SEO title will appear here', 'buddyboss' );
	var displayDesc  = description || __( 'Your SEO description will appear here in search results.', 'buddyboss' );
	var placeholderLetter = siteName ? siteName.charAt( 0 ).toUpperCase() : 'B';

	return (
		<div className="bb-admin-seo-preview">
			<div className="bb-admin-seo-preview__site-row">
				{ siteIcon ? (
					<img
						className="bb-admin-seo-preview__icon"
						src={ safeUrl( siteIcon ) }
						alt=""
					/>
				) : (
					<span className="bb-admin-seo-preview__icon bb-admin-seo-preview__icon--placeholder" aria-hidden="true">
						{ placeholderLetter }
					</span>
				) }
				<div className="bb-admin-seo-preview__site-info">
					{ siteName && (
						<span className="bb-admin-seo-preview__site-name">{ siteName }</span>
					) }
					{ siteUrl && (
						<span className="bb-admin-seo-preview__site-url">{ siteUrl }</span>
					) }
				</div>
			</div>
			<div className="bb-admin-seo-preview__title">{ displayTitle }</div>
			<div className="bb-admin-seo-preview__description">{ displayDesc }</div>
		</div>
	);
}
