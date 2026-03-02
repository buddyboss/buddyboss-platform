import { render, useState, useEffect } from '@wordpress/element';
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

// Initialize the onboarding when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Check if we're on the correct admin page and have the onboarding data
    if (window.bbRlOnboarding) {
        const container = document.createElement('div');
        container.id = 'bb-rl-onboarding-root';
        document.body.appendChild(container);

        render(<OnboardingApp />, container);
    }
});

// Also export for potential manual initialization
export { OnboardingApp };
