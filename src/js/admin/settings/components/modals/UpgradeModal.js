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
 * Resolve the media object for an upgrade entry.
 *
 * Field-level entries arrive with a PHP-built `upgrade_media` object;
 * placeholder feature cards from `bb-features.json` arrive with the
 * legacy `upgrade_image_url` (and may also carry `upgrade_media` once
 * the placeholder PHP is updated). This helper coalesces both shapes so
 * the renderer below has one input contract.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature Modal feature payload.
 * @returns {Object|null}  { type, url, poster } or null when nothing to show.
 */
function resolveUpgradeMedia( feature ) {
	if ( feature.upgrade_media && feature.upgrade_media.type ) {
		return feature.upgrade_media;
	}
	if ( feature.upgrade_image_url ) {
		return { type: 'image', url: feature.upgrade_image_url, poster: '' };
	}
	return null;
}

/**
 * Render the media slot for the modal.
 *
 * `youtube` and `vimeo` use an iframe; `mp4` uses native HTML5 `<video>`
 * (with the catalog image as poster when available); `image` renders a
 * plain `<img>`. The wrapper class differs slightly by type so SCSS can
 * apply aspect-ratio rules to video without forcing a min-height on
 * static images.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} media { type, url, poster }
 * @param {string} alt   Alt text / iframe title.
 */
function UpgradeMedia( { media, alt } ) {
	if ( ! media || ! media.url ) {
		return null;
	}

	var safeMediaUrl = safeUrl( media.url );
	var safePoster   = media.poster ? safeUrl( media.poster ) : '';
	var wrapperClass = 'bb-upgrade-modal__image-wrapper';
	if ( 'youtube' === media.type || 'vimeo' === media.type || 'mp4' === media.type ) {
		wrapperClass += ' bb-upgrade-modal__image-wrapper--video';
	}

	if ( 'youtube' === media.type || 'vimeo' === media.type ) {
		// Native embed only. Embed-URL query params suppress as much chrome
		// as the providers permit. The iframe receives clicks directly so
		// the player's default play/pause-on-click behavior works.
		return (
			<div className={ wrapperClass }>
				<iframe
					src={ safeMediaUrl }
					title={ alt }
					className="bb-upgrade-modal__video"
					frameBorder="0"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					allowFullScreen
				/>
			</div>
		);
	}

	if ( 'mp4' === media.type ) {
		// Match the YouTube/Vimeo behavior: autoplay muted + loop so no
		// play button is needed and the video starts immediately when the
		// modal opens. `muted` is required for browsers to honor `autoPlay`.
		return (
			<div className={ wrapperClass }>
				<video
					src={ safeMediaUrl }
					poster={ safePoster || undefined }
					className="bb-upgrade-modal__video"
					autoPlay
					muted
					loop
					playsInline
					disablePictureInPicture
					controlsList="nodownload nofullscreen noremoteplayback noplaybackrate"
				/>
			</div>
		);
	}

	// type === 'image' (or anything unknown — render as image fallback).
	return (
		<div className={ wrapperClass }>
			<img
				src={ safeMediaUrl }
				alt={ alt }
				className="bb-upgrade-modal__image"
			/>
		</div>
	);
}

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
	var media    = resolveUpgradeMedia( feature );

	return (
		<Modal
			title={ feature.label }
			onRequestClose={ onClose }
			className="bb-upgrade-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ true }
		>
			<div className="bb-upgrade-modal__body">
				<UpgradeMedia media={ media } alt={ feature.label } />

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
