/**
 * BuddyBoss Admin Settings 2.0 — Appearance Welcome Banner
 *
 * Renders above the Site Name section on the Appearance → General panel.
 * Provides a link to leave feedback on the public roadmap and a Setup Wizard
 * button that re-launches the ReadyLaunch onboarding flow by appending the
 * `bb_wizard_activation=rl_onboarding` URL param (same trigger the activation
 * redirect uses).
 *
 * The Setup Wizard button is hidden once `window.bbAdminData.rlOnboardingCompleted`
 * is true — mirrors the legacy `BP_ADMIN.rl_onboarding_completed` gate from
 * the retired admin page.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const FEEDBACK_URL  = 'https://roadmap.buddyboss.com/p/new-ready-launch-buddyboss-platform-templates-Y8mV6D';
const VIDEO_EMBED   = 'https://www.youtube.com/embed/3-JhzDr1gLc';
const WIZARD_PARAM  = 'bb_wizard_activation=rl_onboarding';

/**
 * WelcomeBanner component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Welcome banner markup.
 */
export function WelcomeBanner() {
	var onboardingCompleted = !! ( window.bbAdminData && window.bbAdminData.rlOnboardingCompleted );

	function handleSetupWizardClick() {
		if ( ! window.location ) {
			return;
		}
		// Use `URLSearchParams.set()` so re-clicks don't stack duplicate
		// `bb_wizard_activation` keys. PHP $_GET is last-wins and would work
		// either way, but this keeps the URL tidy.
		var url    = new URL( window.location.href );
		var parts  = WIZARD_PARAM.split( '=' );
		url.searchParams.set( parts[0], parts[1] );
		window.location.href = url.toString();
	}

	return (
		<div className="bb-admin-welcome-banner">
			<div className="bb-admin-welcome-banner__content">
				<div className="bb-admin-welcome-banner__text">
					<h2 className="bb-admin-welcome-banner__title">
						{ __( 'Welcome to ReadyLaunch', 'buddyboss' ) }
					</h2>
					<p className="bb-admin-welcome-banner__description">
						{ __(
							'Build powerful online communities, courses, and memberships — all on WordPress. BuddyBoss helps you launch your own branded platform where members can connect, learn, and grow. Whether you\'re an educator, coach, or community leader, our tools are designed to give you full control, flexibility, and scalability.',
							'buddyboss'
						) }
					</p>
					<div className="bb-admin-welcome-banner__actions">
						<Button
							className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--primary"
							variant="primary"
							href={ FEEDBACK_URL }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Leave Feedback', 'buddyboss' ) }
						</Button>
						{ ! onboardingCompleted && (
							<Button
								className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--secondary"
								variant="secondary"
								onClick={ handleSetupWizardClick }
							>
								{ __( 'Setup Wizard', 'buddyboss' ) }
							</Button>
						) }
					</div>
				</div>
				<div className="bb-admin-welcome-banner__video">
					<iframe
						title={ __( 'BuddyBoss ReadyLaunch tutorial', 'buddyboss' ) }
						src={ VIDEO_EMBED }
						loading="lazy"
						frameBorder="0"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						referrerPolicy="strict-origin-when-cross-origin"
						allowFullScreen
					></iframe>
				</div>
			</div>
		</div>
	);
}
