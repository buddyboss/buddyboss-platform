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
            const response = await fetch(window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bb_rl_should_show_onboarding',
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

    const handleContinue = (selectedOption) => {
        // Handle the continue action with the selected option
        console.log('Selected option:', selectedOption);

        // You can add additional logic here for handling the selected option
        // For now, we'll just close the modal
        setShowModal(false);
    };

    return (
        <OnboardingModal
            isOpen={showModal}
            onClose={handleModalClose}
            onContinue={handleContinue}
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
