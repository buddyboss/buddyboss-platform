/**
 * BuddyBoss Admin Settings 2.0 — TagsReferenceField Component
 *
 * Display-only field that renders an "Available Tags:" reference card listing
 * the template placeholders an author can use (e.g. {activity_title}, {site_title}).
 * Has no value and does not emit onChange calls — it exists purely to document
 * the vocabulary of a sibling template-string input.
 *
 * Mirrors the legacy Site SEO admin's inline help block from
 * `buddyboss-sharing/includes/Admin/Admin.php::render_*_og_title_template_field()`.
 *
 * Field config keys consumed (from PHP `bb_register_feature_field()`):
 *   - `tags` (array) Required. Array of `{ tag, description }` rows to render.
 *   - `heading` (string) Optional override for the card heading. Defaults to
 *     "Available Tags:".
 *
 * Used by:
 *   - Appearance → Site SEO → Activity / Group / Member title templates.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';

/**
 * TagsReferenceField component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props       Component props.
 * @param {Object} props.field Field config (includes `tags` and optional `heading`).
 * @returns {JSX.Element|null} Reference card, or null when no tags are supplied.
 */
export function TagsReferenceField( { field } ) {
	var tags = Array.isArray( field && field.tags ) ? field.tags : [];
	if ( 0 === tags.length ) {
		return null;
	}

	var heading = field.heading || __( 'Available Tags:', 'buddyboss-platform' );

	return (
		<div className="bb-admin-tags-reference" role="note">
			<p className="bb-admin-tags-reference__heading">{ heading }</p>
			<ul className="bb-admin-tags-reference__list">
				{ tags.map( function ( row, index ) {
					if ( ! row || ! row.tag ) {
						return null;
					}
					return (
						<li key={ row.tag + '-' + index } className="bb-admin-tags-reference__item">
							<code className="bb-admin-tags-reference__tag">{ row.tag }</code>
							{ row.description && (
								<span className="bb-admin-tags-reference__description">
									{ ' - ' + row.description }
								</span>
							) }
						</li>
					);
				} ) }
			</ul>
		</div>
	);
}
