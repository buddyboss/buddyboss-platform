/**
 * BuddyBoss Admin Settings 2.0 — Appearance Welcome Banner.
 *
 * Renders above the Site Name section on the Appearance → General panel.
 * Variant is driven by the **draft** value of the Site Layout field
 * (`settings.bb_rl_enabled`) so flipping the radio in the form swaps the
 * banner live, before the auto-save round-trip completes. Falls back to
 * `bbAdminData.isReadyLaunch` (server-persisted state) on first render
 * before the form has populated.
 *
 *   - ReadyLaunch (`bb_rl_enabled === '1'`): ReadyLaunch preview image
 *     and a single Setup Wizard button that re-opens the in-place
 *     onboarding flow. The button hides once the wizard has been
 *     completed (`bbAdminData.isRlOnboardingCompleted`) — matches the
 *     legacy ReadyLaunchSettings.js gate so returning admins get a
 *     cleaner banner. Tweaks happen via the form fields directly.
 *
 *   - BuddyBoss Theme (`bb_rl_enabled === '0'`): Theme preview and a
 *     Use ReadyLaunch outline button. The primary CTA depends on
 *     install/active state of the BuddyBoss Theme:
 *       * Theme not installed → "Buy Theme" → marketing pricing page.
 *       * Theme installed, not active → "Activate Theme" → AJAX call to
 *         `bb_admin_activate_buddyboss_theme` which delegates to core
 *         `switch_theme()`. Disabled with tooltip when the current user
 *         lacks `switch_themes`.
 *       * Theme already active → "Customize Theme" → links to the
 *         theme's own options page (admin.php?page=buddyboss_theme_options),
 *         same URL the rl-onboarding splash popup uses for its
 *         "Configure BuddyBoss Theme" button.
 *
 * The Setup Wizard click (ReadyLaunch variant only) opens the wizard
 * in-place: the rl-onboarding JS/CSS bundle is lazy-loaded on first
 * click, and subsequent clicks re-mount the already parsed bundle via
 * `window.bbRlOnboarding.mount()`. Keeps the Settings 2.0 React tree
 * alive, so form edits aren't lost. Falls back to a full-page redirect
 * with `bb_wizard_activation=rl_onboarding` when the bootstrap payload
 * isn't available.
 *
 * The Theme variant's "Use ReadyLaunch" button is **not** the wizard —
 * it's a 1-click layout flip via `props.onFieldChange('bb_rl_enabled',
 * '1')`. Per the design call: when an admin already on Theme decides to
 * switch to ReadyLaunch they shouldn't have to walk through the choose-
 * layout wizard step they've effectively just answered.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import readylaunchPreview from '../../images/appearance/readylaunch.jpg';
import themePreview from '../../images/appearance/theme.jpg';
import { ajaxFetch } from '../../utils/ajax';
import { BB_EVENTS } from '../../utils/constants';

const WIZARD_PARAM   = 'bb_wizard_activation';
const WIZARD_VALUE   = 'rl_onboarding';
const CSS_LINK_ID    = 'bb-rl-onboarding-css';
const JS_SCRIPT_ID   = 'bb-rl-onboarding-js';
const BUY_THEME_URL  = 'https://buddyboss.com/pricing/';
const ACTIVATE_THEME_ACTION = 'bb_admin_activate_buddyboss_theme';

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
 * @param {Object}   props                 Component props.
 * @param {Object}   [props.settings]      Live form state from
 *                                         FeatureSettingsScreen. Used to read
 *                                         the draft `bb_rl_enabled` value so
 *                                         the banner swaps variants the moment
 *                                         the Site Layout radio changes,
 *                                         before the auto-save round-trip
 *                                         lands.
 * @param {Function} [props.onFieldChange] Same `handleSettingChange` used by
 *                                         every other field — `(name, value)`.
 *                                         The Theme banner's "Use ReadyLaunch"
 *                                         button calls this with
 *                                         `('bb_rl_enabled', '1')` to flip
 *                                         the layout in one click (the
 *                                         banner + side panels then react to
 *                                         the form state change; auto-save
 *                                         persists).
 * @returns {JSX.Element} Welcome banner markup.
 */
export function WelcomeBanner( props ) {
	var bbAdminData = window.bbAdminData || {};
	var settings    = ( props && props.settings ) || {};

	// Draft value wins over the server-persisted `isReadyLaunch` so flipping
	// the Site Layout radio swaps the banner immediately. Coerce to string
	// because the form stores radio values as '0' / '1'.
	var isReadyLaunch;
	if ( Object.prototype.hasOwnProperty.call( settings, 'bb_rl_enabled' ) ) {
		isReadyLaunch = '1' === String( settings.bb_rl_enabled );
	} else {
		isReadyLaunch = !! bbAdminData.isReadyLaunch;
	}

	var isThemeInstalled       = !! bbAdminData.isBuddyBossThemeInstalled;
	var canSwitchThemes        = !! bbAdminData.canSwitchThemes;
	var themeOptionsUrl        = bbAdminData.themeOptionsUrl || '';
	// Hide the Setup Wizard button after the wizard has been completed —
	// matches the legacy ReadyLaunchSettings.js:1252 gate.
	var isRlOnboardingCompleted = !! bbAdminData.isRlOnboardingCompleted;

	// `themeActiveOverride` is the in-session winner — once the AJAX
	// activation succeeds, `bbAdminData.isBuddyBossThemeActive` (set at
	// PHP localize time) is stale, so we mirror the new state in React
	// state AND mutate the global so a panel re-mount inside the same
	// page session still sees the post-activation state.
	var [ themeActiveOverride, setThemeActiveOverride ] = useState( false );
	var [ activating, setActivating ]                   = useState( false );

	var isThemeActive = themeActiveOverride || !! bbAdminData.isBuddyBossThemeActive;

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

		// The wizard fires these DOM events from its complete/skip/close AJAX
		// flow — strip the URL param so a back-button navigation past the
		// wizard URL doesn't reopen the modal on the next admin load.
		document.addEventListener( 'bb_rl_onboarding_completed', stripWizardParam );
		document.addEventListener( 'bb_rl_onboarding_skipped', stripWizardParam );
		document.addEventListener( 'bb_rl_onboarding_closed', stripWizardParam );
		window.addEventListener( 'popstate', handlePopstate );

		return function () {
			document.removeEventListener( 'bb_rl_onboarding_completed', stripWizardParam );
			document.removeEventListener( 'bb_rl_onboarding_skipped', stripWizardParam );
			document.removeEventListener( 'bb_rl_onboarding_closed', stripWizardParam );
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
		//
		//    Skip the SplashScreen step (index 0) when launched from the
		//    banner — admins reaching this code path have already chosen
		//    "ReadyLaunch" via the Site Layout dropdown, so re-asking them
		//    is friction. Lift `progress.current_step` to at least 1 (the
		//    Site Name step). If the user already has higher progress
		//    (paused mid-wizard), keep their place. The activation-popup
		//    auto-mount path doesn't go through this handler, so the
		//    splash still shows there exactly as before.
		var bootstrapProgress = ( bootstrap.wizardData && bootstrap.wizardData.progress ) || {};
		var existingStep      = Number( bootstrapProgress.current_step ) || 0;
		var bannerProgress    = Object.assign( {}, bootstrapProgress, {
			current_step: Math.max( existingStep, 1 ),
		} );

		window.bbRlOnboarding = Object.assign(
			{},
			window.bbRlOnboarding || {},
			bootstrap.wizardData || {},
			{ shouldShow: true, progress: bannerProgress }
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

	function handleUseReadyLaunch() {
		// 1-click flip from Theme variant back to ReadyLaunch — same
		// `handleSettingChange` path as the Site Layout radio itself, so
		// auto-save + side-panel re-render fire identically. We do NOT
		// open the wizard here: per the design call, the wizard is for
		// users who haven't decided yet, but a Theme-banner admin who
		// clicks this button has already decided ("encourage them to
		// use ready launch again"). One click → layout flips → banner
		// swaps to ReadyLaunch variant → side panels reload.
		if ( typeof props.onFieldChange === 'function' ) {
			props.onFieldChange( 'bb_rl_enabled', '1' );
		}
	}

	function dispatchToast( status, message ) {
		if ( typeof window.CustomEvent !== 'function' ) {
			return;
		}
		window.dispatchEvent( new window.CustomEvent( BB_EVENTS.TOAST, {
			detail: { status: status, message: message },
		} ) );
	}

	function handleActivateTheme() {
		if ( activating ) {
			return;
		}
		setActivating( true );

		ajaxFetch( ACTIVATE_THEME_ACTION ).then( function ( response ) {
			if ( response && response.success ) {
				// Mirror the post-activation state both in React state and
				// on `window.bbAdminData` so a re-mount of this component
				// (e.g. user navigates to another panel and back) still
				// sees the theme as active without a page reload.
				if ( window.bbAdminData ) {
					window.bbAdminData.isBuddyBossThemeActive = true;
				}
				setThemeActiveOverride( true );
				dispatchToast(
					'success',
					( response.data && response.data.message ) || __( 'BuddyBoss Theme activated.', 'buddyboss' )
				);
			} else {
				var errMsg = ( response && response.data && response.data.message )
					|| __( 'Activation failed. Please try again.', 'buddyboss' );
				dispatchToast( 'error', errMsg );
			}
		} ).catch( function ( err ) {
			dispatchToast(
				'error',
				( err && err.message ) || __( 'Activation failed. Please try again.', 'buddyboss' )
			);
		} ).then( function () {
			setActivating( false );
		} );
	}

	if ( isReadyLaunch ) {
		return (
			<div className="bb-admin-welcome-banner bb-admin-welcome-banner--readylaunch">
				<div className="bb-admin-welcome-banner__content">
					<div className="bb-admin-welcome-banner__text">
						<h2 className="bb-admin-welcome-banner__title">
							{ __( 'Welcome to ReadyLaunch', 'buddyboss' ) }
						</h2>
						<div className="bb-admin-welcome-banner__intro">
							<h3 className="bb-admin-welcome-banner__subtitle">
								{ __( 'Theme-free community interface', 'buddyboss' ) }
							</h3>
							<div className="bb-admin-welcome-banner__intro-body">
								<p className="bb-admin-welcome-banner__description">
									{ __(
										'ReadyLaunch provides a complete, ready-to-use UI for your community, directly connected to the BuddyBoss Platform backend.',
										'buddyboss'
									) }
								</p>
								<ul className="bb-admin-welcome-banner__checks">
									<li className="bb-admin-welcome-banner__check">
										{ __( 'Simple management', 'buddyboss' ) }
									</li>
									<li className="bb-admin-welcome-banner__check">
										{ __( 'No theme required', 'buddyboss' ) }
									</li>
								</ul>
							</div>
						</div>
						{ ! isRlOnboardingCompleted && (
							<div className="bb-admin-welcome-banner__actions">
								<Button
									className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--secondary"
									variant="secondary"
									onClick={ handleSetupWizardClick }
								>
									{ __( 'Setup Wizard', 'buddyboss' ) }
								</Button>
							</div>
						) }
					</div>
					<div className="bb-admin-welcome-banner__preview">
						<img
							src={ readylaunchPreview }
							alt={ __( 'ReadyLaunch interface preview', 'buddyboss' ) }
							loading="lazy"
						/>
					</div>
				</div>
			</div>
		);
	}

	return (
		<div className="bb-admin-welcome-banner bb-admin-welcome-banner--theme">
			<div className="bb-admin-welcome-banner__content">
				<div className="bb-admin-welcome-banner__text">
					<h2 className="bb-admin-welcome-banner__title">
						{ __( 'Welcome to BuddyBoss Theme', 'buddyboss' ) }
					</h2>
					<div className="bb-admin-welcome-banner__intro">
						<h3 className="bb-admin-welcome-banner__subtitle">
							{ __( 'Customizable WordPress theme', 'buddyboss' ) }
						</h3>
						<div className="bb-admin-welcome-banner__intro-body">
							<p className="bb-admin-welcome-banner__description">
								{ __(
									'A premium theme designed to work with BuddyBoss Platform, offering deep design control for courses and communities.',
									'buddyboss'
								) }
							</p>
							<ul className="bb-admin-welcome-banner__checks">
								<li className="bb-admin-welcome-banner__check">
									{ __( 'Advanced customization', 'buddyboss' ) }
								</li>
								<li className="bb-admin-welcome-banner__check">
									{ __( 'BuddyBoss theme required', 'buddyboss' ) }
								</li>
							</ul>
						</div>
					</div>
					<div className="bb-admin-welcome-banner__actions">
						{ ! isThemeActive && isThemeInstalled && (
							<Button
								className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--primary"
								variant="primary"
								onClick={ canSwitchThemes ? handleActivateTheme : undefined }
								isBusy={ activating }
								disabled={ ! canSwitchThemes || activating }
								// Native title attribute surfaces the reason on
								// disabled buttons (multisite case where the
								// admin has manage_options but not
								// switch_themes — without this the button
								// would just look broken).
								title={ ! canSwitchThemes
									? __( 'Theme activation requires the switch_themes capability — contact your network administrator.', 'buddyboss' )
									: undefined
								}
							>
								{ activating
									? __( 'Activating…', 'buddyboss' )
									: __( 'Activate Theme', 'buddyboss' )
								}
							</Button>
						) }
						{ ! isThemeActive && ! isThemeInstalled && (
							<Button
								className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--primary"
								variant="primary"
								href={ BUY_THEME_URL }
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Buy Theme', 'buddyboss' ) }
							</Button>
						) }
						{ isThemeActive && themeOptionsUrl && (
							<Button
								className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--primary"
								variant="primary"
								href={ themeOptionsUrl }
							>
								{ __( 'Customize Theme', 'buddyboss' ) }
							</Button>
						) }
						<Button
							className="bb-admin-welcome-banner__btn bb-admin-welcome-banner__btn--secondary"
							variant="secondary"
							onClick={ handleUseReadyLaunch }
						>
							{ __( 'Use ReadyLaunch', 'buddyboss' ) }
						</Button>
					</div>
				</div>
				<div className="bb-admin-welcome-banner__preview">
					<img
						src={ themePreview }
						alt={ __( 'BuddyBoss Theme interface preview', 'buddyboss' ) }
						loading="lazy"
					/>
				</div>
			</div>
		</div>
	);
}
