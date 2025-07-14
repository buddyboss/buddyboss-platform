import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const OnboardingModal = ({ isOpen, onClose, onContinue }) => {
    const [currentStep, setCurrentStep] = useState(0);

    // Modal steps content
    const steps = [
        {
            title: __('Welcome to BuddyBoss', 'buddyboss'),
            subtitle: __('Let\'s bring your community to life by choose the look and feel that matches your vision.', 'buddyboss'),
            content: (
                <div className="bb-rl-onboarding-step">
                    <div className="bb-rl-onboarding-options">
                        <div className="bb-rl-option-card">
                            <div className="bb-rl-option-header">
                                <h3>{__('BuddyBoss Theme', 'buddyboss')}</h3>
                                <p className="bb-rl-option-subtitle">{__('Customizable WordPress theme', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-option-preview">
                                <img src={window.bbRlOnboarding?.assets?.buddybossThemePreview || ''} alt="BuddyBoss Theme Preview" />
                            </div>
                            <div className="bb-rl-option-description">
                                {__('Our crafted theme made just for the BuddyBoss Platform giving you full control to design a community that feels truly yours.', 'buddyboss')}
                            </div>
                            <div className="bb-rl-option-features">
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Advanced customization', 'buddyboss')}
                                </div>
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Deep integration support', 'buddyboss')}
                                </div>
                            </div>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-secondary"
                                onClick={() => handleOptionSelect('buddyboss-theme')}
                            >
                                {__('Configure BuddyBoss Theme', 'buddyboss')}
                            </Button>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-primary"
                                onClick={() => handleOptionSelect('buddyboss-theme-buy')}
                            >
                                {__('Buy Theme', 'buddyboss')}
                            </Button>
                        </div>

                        <div className="bb-rl-option-card">
                            <div className="bb-rl-option-header">
                                <h3>{__('ReadyLaunch', 'buddyboss')}</h3>
                                <p className="bb-rl-option-subtitle">{__('Community features for any WordPress theme', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-option-preview">
                                <img src={window.bbRlOnboarding?.assets?.currentThemePreview || ''} alt="ReadyLaunch Preview" />
                            </div>
                            <div className="bb-rl-option-description">
                                {__('Get your community up and running in no time with our easy to use template system.', 'buddyboss')}
                            </div>
                            <div className="bb-rl-option-features">
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Minimal configuration', 'buddyboss')}
                                </div>
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Supports any WordPress Theme', 'buddyboss')}
                                </div>
                            </div>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-primary"
                                onClick={() => handleOptionSelect('readylaunch')}
                            >
                                {__('Configure ReadyLaunch', 'buddyboss')}
                            </Button>
                        </div>
                    </div>
                </div>
            )
        }
    ];

    const handleOptionSelect = (option) => {
        // Store the selected option
        window.bbRlOnboarding.selectedOption = option;
        
        // Continue to next step or complete onboarding
        if (onContinue) {
            onContinue(option);
        }
    };

    const handleClose = () => {
        if (onClose) {
            onClose();
        }
    };

    const handleSkip = () => {
        // Mark onboarding as completed but skipped
        completeOnboarding(true);
    };

    const completeOnboarding = async (skipped = false) => {
        try {
            const response = await fetch(window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'bb_rl_complete_onboarding',
                    nonce: window.bbRlOnboarding?.nonce || '',
                    skipped: skipped ? '1' : '0',
                    selected_option: window.bbRlOnboarding?.selectedOption || '',
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                handleClose();
            }
        } catch (error) {
            console.error('Error completing onboarding:', error);
        }
    };

    if (!isOpen) {
        return null;
    }

    const currentStepData = steps[currentStep];

    return (
        <div className="bb-rl-onboarding-overlay">
            <div className="bb-rl-onboarding-modal">
                <div className="bb-rl-onboarding-header">
                    <div className="bb-rl-logo">
                        <img src={window.bbRlOnboarding?.assets?.logo || ''} alt="BuddyBoss" />
                    </div>
                    <Button 
                        className="bb-rl-close-button"
                        onClick={handleClose}
                        label={__('Close', 'buddyboss')}
                    >
                        <span className="dashicons dashicons-no-alt"></span>
                    </Button>
                </div>

                <div className="bb-rl-onboarding-content">
                    <div className="bb-rl-onboarding-title">
                        <h1>{currentStepData.title}</h1>
                        {currentStepData.subtitle && (
                            <p className="bb-rl-subtitle">{currentStepData.subtitle}</p>
                        )}
                    </div>

                    <div className="bb-rl-onboarding-body">
                        {currentStepData.content}
                    </div>
                </div>
            </div>
        </div>
    );
}; 