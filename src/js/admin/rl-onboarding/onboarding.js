import { createRoot, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { OnboardingModal } from './components/OnboardingModal';

// Onboarding App Component
const OnboardingApp = () => {
    const [showModal, setShowModal] = useState(false);

    useEffect(() => {
        // Check if we should show the onboarding modal
        // Since shouldShow is already determined by PHP, we can use it directly
        if (window.bbRlOnboarding && window.bbRlOnboarding.shouldShow) {
            setShowModal(true);
        }
    }, []);

    const checkShouldShowOnboarding = async () => {
        try {
            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: window.bbRlOnboarding.wizardId + '_should_show',
                    nonce: window.bbRlOnboarding?.nonce || '',
                }),
            });

            const data = await response.json();

            if (data.success && data.data.shouldShow) {
                setShowModal(true);
            }
        } catch (error) {
            console.error('Error checking onboarding status:', error);
        }
    };

    const handleModalClose = () => {
        setShowModal(false);
        // Notify the Settings 2.0 Welcome Banner so it can strip the
        // `bb_wizard_activation=rl_onboarding` URL param it pushState'd
        // when opening the wizard.
        document.dispatchEvent( new CustomEvent( 'bb_rl_onboarding_closed' ) );
    };

    const handleContinue = async (selectedOption) => {
        // Handle the continue action with the selected option

        try {
            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: window.bbRlOnboarding?.actions?.complete || window.bbRlOnboarding.wizardId + '_complete',
                    nonce: window.bbRlOnboarding?.nonce || '',
                    selectedOption: selectedOption,
                    skipped: '0',
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Trigger custom event for extensibility
                const event = new CustomEvent('bb_rl_onboarding_completed', {
                    detail: {
                        selectedOption: selectedOption,
                        data: data.data
                    }
                });
                document.dispatchEvent(event);

                // Handle different completion scenarios
                if (selectedOption === 'readylaunch') {
                    // Redirect to ReadyLaunch settings page
                    window.location.href = window.location.origin + '/wp-admin/admin.php?page=buddyboss-platform&tab=buddyboss_readylaunch';
                } else if (selectedOption === 'buddyboss-theme-buy') {
                    // Redirect to BuddyBoss theme purchase page
                    window.open('https://www.buddyboss.com/theme/', '_blank');
                } else {
                    // Just close the modal for other options
                    setShowModal(false);
                }
            } else {
                console.error('Error completing onboarding:', data.data?.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error completing onboarding:', error);
        }
    };

    const handleSkip = async () => {
        // Handle skipping the onboarding
        try {
            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: window.bbRlOnboarding?.actions?.complete || window.bbRlOnboarding.wizardId + '_complete',
                    nonce: window.bbRlOnboarding?.nonce || '',
                    selectedOption: '',
                    skipped: '1',
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Trigger custom event for extensibility
                const event = new CustomEvent('bb_rl_onboarding_skipped', {
                    detail: {
                        data: data.data
                    }
                });
                document.dispatchEvent(event);

                setShowModal(false);
            } else {
                console.error('Error skipping onboarding:', data.data?.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error skipping onboarding:', error);
        }
    };



    return (
        <OnboardingModal
            isOpen={showModal}
            onClose={handleModalClose}
            onContinue={handleContinue}
            onSkip={handleSkip}
        />
    );
};

/**
 * Mount the onboarding React tree into a dedicated container node.
 *
 * Always tears down any existing mount before rendering so the
 * `OnboardingApp` component re-initialises from scratch — its
 * `useEffect([])` hook only runs on initial mount to flip
 * `showModal` from `window.bbRlOnboarding.shouldShow`, so re-rendering
 * into an existing tree would leave the modal closed after the user
 * dismissed it once.
 *
 * Used both by the DOM-ready auto-mount path (fresh-install redirect
 * with `bb_wizard_activation=rl_onboarding`) and by the Settings 2.0
 * Appearance Welcome Banner for lazy mount-on-click without a reload.
 *
 * @since BuddyBoss [BBVERSION]
 */
// Module-scoped root reference. `createRoot()` returns an object we must
// call `.unmount()` on before discarding the container — dropping only
// the DOM node orphans the React fiber tree, leaving effect cleanups
// unrun and synthetic event handlers still bound to `document`.
let onboardingRoot = null;

function mountOnboarding() {
    if ( ! window.bbRlOnboarding ) {
        return;
    }
    // Tear down any previous mount *before* removing its container so
    // React runs unmount lifecycles against a still-attached node.
    if ( onboardingRoot ) {
        onboardingRoot.unmount();
        onboardingRoot = null;
    }
    const existing = document.getElementById( 'bb-rl-onboarding-root' );
    if ( existing ) {
        existing.remove();
    }
    const container = document.createElement( 'div' );
    container.id = 'bb-rl-onboarding-root';
    document.body.appendChild( container );
    onboardingRoot = createRoot( container );
    onboardingRoot.render( <OnboardingApp /> );
}

/**
 * Unmount the onboarding React tree.
 *
 * Called by the Welcome Banner's popstate listener when the user
 * backs out of the wizard URL so the modal closes cleanly.
 *
 * @since BuddyBoss [BBVERSION]
 */
function unmountOnboarding() {
    // Unmount React *before* DOM removal so effect cleanups (fetch
    // aborts, timers, document-level listeners) get the chance to run.
    if ( onboardingRoot ) {
        onboardingRoot.unmount();
        onboardingRoot = null;
    }
    const container = document.getElementById( 'bb-rl-onboarding-root' );
    if ( container ) {
        container.remove();
    }
}

// Expose imperative API on `window.bbRlOnboarding` so the Settings 2.0
// Welcome Banner can mount the wizard on demand. Preserves any localised
// data that WordPress injected via `wp_localize_script()` ahead of this
// bundle (fresh-install path) or that the Welcome Banner hydrated from
// `bbAdminData.rlOnboardingBootstrap` (lazy-load path).
window.bbRlOnboarding = window.bbRlOnboarding || {};
window.bbRlOnboarding.mount   = mountOnboarding;
window.bbRlOnboarding.unmount = unmountOnboarding;

// Auto-mount when `shouldShow` is truthy. Handles both loads that arrive
// before DOMContentLoaded (fresh install) and loads that arrive after
// (dynamic `<script>` injection from the Welcome Banner).
function maybeAutoMount() {
    if ( window.bbRlOnboarding && window.bbRlOnboarding.shouldShow ) {
        mountOnboarding();
    }
}

if ( 'loading' === document.readyState ) {
    document.addEventListener( 'DOMContentLoaded', maybeAutoMount );
} else {
    maybeAutoMount();
}

// Also export for potential manual initialization
export { OnboardingApp };
