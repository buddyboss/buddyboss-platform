/**
 * BuddyBoss Admin Settings 2.0 — Appearance Welcome Banner
 *
 * Renders above the Site Name section on the Appearance → General panel.
 * Provides a link to leave feedback on the public roadmap and a Setup Wizard
 * button that re-launches the ReadyLaunch onboarding flow.
 *
 * The Setup Wizard opens in-place: the rl-onboarding JS/CSS bundle is
 * lazy-loaded on first click, and subsequent clicks re-mount the already
 * parsed bundle via `window.bbRlOnboarding.mount()`. Keeps the Settings 2.0
 * React tree alive, so form edits aren't lost. Falls back to a full-page
 * redirect with `bb_wizard_activation=rl_onboarding` when the bootstrap
 * payload isn't available.
 *
 * The Setup Wizard button is hidden once the wizard has been completed
 * (either via `window.bbAdminData.rlOnboardingCompleted` on first render
 * or via the `bb_rl_onboarding_completed` / `_skipped` events afterwards).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

const FEEDBACK_URL  = 'https://roadmap.buddyboss.com/p/new-ready-launch-buddyboss-platform-templates-Y8mV6D';
// `youtube-nocookie.com` is YouTube's enhanced-privacy embed domain — no
// tracking cookies are set until the viewer actually plays the video. Pairs
// with `loading="lazy"` on the iframe below to keep EU/GDPR admins from
// pinging YouTube before they interact.
const VIDEO_EMBED   = 'https://www.youtube-nocookie.com/embed/3-JhzDr1gLc';
const WIZARD_PARAM  = 'bb_wizard_activation';
const WIZARD_VALUE  = 'rl_onboarding';
const CSS_LINK_ID   = 'bb-rl-onboarding-css';
const JS_SCRIPT_ID  = 'bb-rl-onboarding-js';

/**
 * Fallback to a full-page redirect when the bootstrap payload is missing
 * (e.g. the Appearance feature is disabled, or a filter stripped the
 * localised data). Mirrors the legacy behaviour so the button always does
 * *something*.
 *
 * @since BuddyBoss [BBVERSION]
 */
function redirectToWizard() {
	if ( ! window.location ) {
		return;
	}
	var url = new URL( window.location.href );
	url.searchParams.set( WIZARD_PARAM, WIZARD_VALUE );
	window.location.href = url.toString();
}

/**
 * WelcomeBanner component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Welcome banner markup.
 */
export function WelcomeBanner() {
	var bbAdminData = window.bbAdminData || {};
	var [ completed, setCompleted ] = useState( !! bbAdminData.rlOnboardingCompleted );

	useEffect( function () {
		function stripWizardParam() {
			if ( ! window.history || typeof window.history.replaceState !== 'function' ) {
				return;
			}
			var url = new URL( window.location.href );
			if ( ! url.searchParams.has( WIZARD_PARAM ) ) {
				return;
			}
			url.searchParams.delete( WIZARD_PARAM );
			window.history.replaceState( {}, '', url.toString() );
		}

		function handleCompleted() {
			setCompleted( true );
			stripWizardParam();
		}

		function handleClosed() {
			stripWizardParam();
		}

		function handlePopstate() {
			if ( ! window.location ) {
				return;
			}
			var params = new URLSearchParams( window.location.search );
			if ( params.get( WIZARD_PARAM ) !== WIZARD_VALUE ) {
				// User navigated back past the wizard URL — close the modal.
				if ( window.bbRlOnboarding && typeof window.bbRlOnboarding.unmount === 'function' ) {
					window.bbRlOnboarding.unmount();
				}
			}
		}

		// The wizard fires these DOM events from its complete/skip AJAX
		// flow — hide the Setup Wizard button in-place without waiting for
		// the next full page load. `_closed` fires when the admin dismisses
		// via the X/Cancel affordance, so we strip the URL param there too.
		document.addEventListener( 'bb_rl_onboarding_completed', handleCompleted );
		document.addEventListener( 'bb_rl_onboarding_skipped', handleCompleted );
		document.addEventListener( 'bb_rl_onboarding_closed', handleClosed );
		window.addEventListener( 'popstate', handlePopstate );

		return function () {
			document.removeEventListener( 'bb_rl_onboarding_completed', handleCompleted );
			document.removeEventListener( 'bb_rl_onboarding_skipped', handleCompleted );
			document.removeEventListener( 'bb_rl_onboarding_closed', handleClosed );
			window.removeEventListener( 'popstate', handlePopstate );
		};
	}, [] );

	function handleSetupWizardClick() {
		var bootstrap = bbAdminData.rlOnboardingBootstrap;
		if ( ! bootstrap || ! bootstrap.assets || ! bootstrap.assets.js ) {
			redirectToWizard();
			return;
		}

		// 1. Push the wizard URL param so deep-links + the back button
		//    behave the same as a full reload would.
		if ( window.history && typeof window.history.pushState === 'function' ) {
			var newUrl = new URL( window.location.href );
			newUrl.searchParams.set( WIZARD_PARAM, WIZARD_VALUE );
			window.history.pushState( {}, '', newUrl.toString() );
		}

		// 2. Hydrate `window.bbRlOnboarding` BEFORE the bundle parses (on
		//    first click) or before we re-mount (on subsequent clicks).
		//    The wizard entry does `window.bbRlOnboarding = window.bbRlOnboarding || {}`
		//    so these fields survive the bundle's own initialisation.
		window.bbRlOnboarding = Object.assign(
			{},
			window.bbRlOnboarding || {},
			bootstrap.wizardData || {},
			{ shouldShow: true }
		);

		// 3. If the bundle is already parsed, just remount.
		if ( window.bbRlOnboarding && typeof window.bbRlOnboarding.mount === 'function' ) {
			window.bbRlOnboarding.mount();
			return;
		}

		// 4. Lazy-load CSS once — guard by element ID so rapid double
		//    clicks don't stack duplicate <link> tags.
		if ( bootstrap.assets.css && ! document.getElementById( CSS_LINK_ID ) ) {
			var link  = document.createElement( 'link' );
			link.id   = CSS_LINK_ID;
			link.rel  = 'stylesheet';
			link.href = bootstrap.assets.css;
			document.head.appendChild( link );
		}

		// 5. Lazy-load the JS bundle. Its init block attaches `mount()` to
		//    `window.bbRlOnboarding` and auto-mounts because we set
		//    `shouldShow: true` above.
		if ( ! document.getElementById( JS_SCRIPT_ID ) ) {
			var script    = document.createElement( 'script' );
			script.id     = JS_SCRIPT_ID;
			script.src    = bootstrap.assets.js;
			script.onload = function () {
				// Safety net — if the auto-mount path didn't run for any
				// reason, call mount() explicitly.
				if ( window.bbRlOnboarding && typeof window.bbRlOnboarding.mount === 'function' ) {
					window.bbRlOnboarding.mount();
				}
			};
			script.onerror = function () {
				// Network or CSP failure — fall back to a full reload so
				// the admin still gets the wizard via the legacy path.
				redirectToWizard();
			};
			document.body.appendChild( script );
		}
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
							href={ FEEDBACK_URL }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Leave Feedback', 'buddyboss' ) }
						</Button>
						{ ! completed && (
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
					{/*
					  * Sandbox restricts the YouTube embed to only what the
					  * player needs:
					  *  - allow-scripts + allow-same-origin: player runtime
					  *  - allow-presentation: fullscreen support
					  *  - allow-popups + allow-popups-to-escape-sandbox:
					  *    "Watch on YouTube" button opens in a new tab
					  * Omits allow-top-navigation / allow-forms / allow-modals
					  * — none of which a passive tutorial embed should need.
					  */}
					<iframe
						title={ __( 'BuddyBoss ReadyLaunch tutorial', 'buddyboss' ) }
						src={ VIDEO_EMBED }
						loading="lazy"
						sandbox="allow-scripts allow-same-origin allow-presentation allow-popups allow-popups-to-escape-sandbox"
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
