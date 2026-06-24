/**
 * BuddyBoss Admin Settings 2.0 — SocialPreviewField Component
 *
 * Renders a Facebook/X-style Open Graph share preview that reflects the
 * current OG title / description / image values being edited. Used by
 * Appearance → Site SEO → Social.
 *
 * Layout (per Figma):
 *   - Optional OG image (top, full-width, aspect 1.91:1)
 *   - URL (small, gray)
 *   - Bold title (black)
 *   - Description (2-line clamp)
 *
 * Falls back to SEO title/description when "Use same as SEO …" is checked
 * (i.e. the OG value is blank).
 *
 * Field config keys (from PHP):
 *   - `preview_config.site_url`             (string) Domain shown above the title.
 *   - `preview_config.title_key`            (string) Primary field to read title (e.g. "buddyboss_og_title").
 *   - `preview_config.description_key`      (string) Primary field to read description (e.g. "buddyboss_og_description").
 *   - `preview_config.image_key`            (string) Field to read image URL (e.g. "buddyboss_og_image").
 *   - `preview_config.fallback_title_key`   (string) Fallback field when primary is empty (e.g. "buddyboss_seo_title").
 *   - `preview_config.fallback_description_key` (string) Fallback field when primary is empty.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { safeUrl } from '../../utils/sanitize';

/**
 * Format a URL for display (strip protocol, trailing slash).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} url URL to format.
 * @returns {string} Display-friendly URL.
 */
function formatDisplayUrl( url ) {
	if ( ! url ) {
		return '';
	}
	return String( url )
		.replace( /^https?:\/\//, '' )
		.replace( /\/$/, '' );
}

/**
 * Read a value from the form map, handling media-picker objects ({ url }).
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} values Form values map.
 * @param {string} key    Field name.
 * @returns {string} Resolved string value.
 */
function readString( values, key ) {
	if ( ! values || ! key ) {
		return '';
	}
	var raw = values[ key ];
	if ( ! raw ) {
		return '';
	}
	if ( 'object' === typeof raw && raw.url ) {
		return String( raw.url );
	}
	return String( raw );
}

/**
 * SocialPreviewField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props        Component props.
 * @param {Object} props.field  Field config (includes preview_config).
 * @param {Object} props.values Current form values map — used to read OG fields.
 * @returns {JSX.Element} Preview card.
 */
export function SocialPreviewField( { field, values } ) {
	var config                  = field.preview_config || {};
	var titleKey                = config.title_key || 'buddyboss_og_title';
	var descriptionKey          = config.description_key || 'buddyboss_og_description';
	var imageKey                = config.image_key || 'buddyboss_og_image';
	var fallbackTitleKey        = config.fallback_title_key || 'buddyboss_seo_title';
	var fallbackDescKey         = config.fallback_description_key || 'buddyboss_seo_description';

	var title       = readString( values, titleKey )       || readString( values, fallbackTitleKey );
	var description = readString( values, descriptionKey ) || readString( values, fallbackDescKey );
	var imageUrl    = readString( values, imageKey );

	var siteUrl    = config.site_url || '';
	var displayUrl = formatDisplayUrl( siteUrl );

	var displayTitle = title || __( 'Your OG title will appear here', 'buddyboss-platform' );
	var displayDesc  = description || __( 'Your OG description will appear here when this page is shared on social platforms.', 'buddyboss-platform' );

	return (
		<div className="bb-admin-social-preview">
			{ imageUrl && (
				<div className="bb-admin-social-preview__image">
					<img src={ safeUrl( imageUrl ) } alt="" />
				</div>
			) }
			<div className="bb-admin-social-preview__body">
				{ displayUrl && (
					<span className="bb-admin-social-preview__url">{ displayUrl }</span>
				) }
				<span className="bb-admin-social-preview__title">{ displayTitle }</span>
				<span className="bb-admin-social-preview__description">{ displayDesc }</span>
			</div>
		</div>
	);
}
