/**
 * BuddyBoss Admin Settings 2.0 - Upgrade Modal
 *
 * Displays a modal with product hero image, description, and upgrade CTA
 * when a user clicks the plan badge on a placeholder feature card.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { safeUrl, sanitizeHtml } from '../../utils/sanitize';

/**
 * Upgrade Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props           Component props.
 * @param {Object}   props.feature   Placeholder feature object with upgrade data.
 *                                   Reads `upgrade_title` (single-line headline)
 *                                   and `upgrade_description` (paragraph body)
 *                                   from the S3 catalog. Both are optional —
 *                                   when absent the modal falls back to the
 *                                   card-level `description` so older catalog
 *                                   entries keep rendering.
 * @param {Function} props.onClose   Close handler.
 * @returns {JSX.Element} Modal component.
 */
export function UpgradeModal( { feature, onClose } ) {
	if ( ! feature ) {
		return null;
	}

	var tierLabel = 'plus' === feature.upgrade_tier
		? __( 'UPGRADE PLUS', 'buddyboss' )
		: __( 'UPGRADE PRO', 'buddyboss' );

	// PHP wraps upgrade_description with wp_kses_post so marketing can use
	// modest emphasis (<strong>, <em>, <a>). DOMPurify (via sanitizeHtml)
	// is the belt-and-braces second pass — same project convention used by
	// FeatureSettingsScreen, ProfileTypeScreen, EmailTemplatesListScreen,
	// and ConfirmToggleModal. Never render server-supplied admin HTML
	// without this double-sanitisation layer.
	var bodyText = feature.upgrade_description || feature.description || '';

	return (
		<Modal
			title={ feature.label }
			onRequestClose={ onClose }
			className="bb-upgrade-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ true }
		>
			<div className="bb-upgrade-modal__body">
				{feature.upgrade_image_url && (
					<div className="bb-upgrade-modal__image-wrapper">
						<img
							src={ safeUrl( feature.upgrade_image_url ) }
							alt={feature.label}
							className="bb-upgrade-modal__image"
						/>
					</div>
				)}

				<div className="bb-upgrade-modal__content">
					{feature.upgrade_title && (
						<h3 className="bb-upgrade-modal__title">
							{feature.upgrade_title}
						</h3>
					)}

					<p
						className="bb-upgrade-modal__description"
						dangerouslySetInnerHTML={ { __html: sanitizeHtml( bodyText ) } }
					/>

					<a
						href={ safeUrl( feature.upgrade_url ) || 'https://www.buddyboss.com/pricing/' }
						target="_blank"
						rel="noopener noreferrer"
						className={`bb-upgrade-modal__cta bb-upgrade-modal__cta--${feature.upgrade_tier || 'plus'}`}
					>
						<i className="bb-icons-rl bb-icons-rl-crown-simple"></i>
						{tierLabel}
					</a>
				</div>
			</div>
		</Modal>
	);
}
