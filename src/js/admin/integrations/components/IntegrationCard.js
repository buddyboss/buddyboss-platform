/**
 * BuddyBoss Integrations marketplace — single card.
 *
 * Matches the Figma card: circular bordered logo, title, vendor subtitle,
 * 3-line-clamped description, and an orange-outline action button. Clicking the
 * card body opens the detail drawer.
 *
 * PENDING TEAM (placeholders, isolated):
 *  - Install vs Learn More rule (Q4): no install field exists in the API yet, so
 *    the action is a neutral "Learn More" deep-link to `link` for now, styled as
 *    the Figma primary button.
 *  - Vendor subtitle (Q8.2): the API exposes no vendor/author field, so the line
 *    is omitted until one exists.
 *  - PRO badge + Free/Pro (Q5): no free/pro field exists yet, so the badge slot
 *    is left out.
 * See docs/superpowers/specs/2026-06-24-integrations-marketplace-design.md.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { safeUrl } from '../../settings/utils/sanitize';

export function IntegrationCard( { item, onSelect } ) {
	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';
	const learnMoreUrl = item?.link || item?.link_url || '';

	const open = () => onSelect( item.slug );

	const handleKeyDown = ( e ) => {
		if ( 'Enter' === e.key || ' ' === e.key ) {
			e.preventDefault();
			open();
		}
	};

	return (
		<div className="bb-integrations__card">
			<button
				type="button"
				className="bb-integrations__card-body"
				onClick={ open }
				onKeyDown={ handleKeyDown }
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
					{ /* PENDING Q5: PRO badge slot (gradient crown + "UPGRADE PRO") goes here. */ }
				</div>

				<div className="bb-integrations__card-text">
					<span className="bb-integrations__card-title">{ title }</span>
					{ /* PENDING Q8.2: vendor subtitle — no API field, omitted. */ }
					<span className="bb-integrations__card-desc">{ description }</span>
				</div>
			</button>

			<div className="bb-integrations__card-actions">
				{ /* PENDING Q4: replace with Install/Activate when the install path is defined. */ }
				{ learnMoreUrl ? (
					<a
						href={ safeUrl( learnMoreUrl ) }
						className="bb-integrations__btn bb-integrations__btn--primary"
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __( 'Learn More', 'buddyboss' ) }
					</a>
				) : (
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
