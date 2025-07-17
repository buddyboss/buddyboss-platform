import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { getStepComponent, hasStepComponent } from './StepRegistry';

export const OnboardingModal = ({ isOpen, onClose, onContinue, onSkip, onSaveStep }) => {
    const [currentStepIndex, setCurrentStepIndex] = useState(0);
    const [stepData, setStepData] = useState({});
    const [isProcessing, setIsProcessing] = useState(false);

    // Get steps from window.bbRlOnboarding
    const steps = window.bbRlOnboarding?.steps || [];
    const totalSteps = steps.length;
    const currentStep = steps[currentStepIndex] || {};

    useEffect(() => {
        // Initialize step data from saved preferences
        const savedData = {};
        if (window.bbRlOnboarding?.preferences) {
            Object.keys(window.bbRlOnboarding.preferences).forEach(key => {
                savedData[key] = window.bbRlOnboarding.preferences[key];
            });
        }
        setStepData(savedData);
    }, []);

    // Enable/disable fullscreen mode based on current step
    useEffect(() => {
        if (isOpen && currentStep.component && currentStep.component !== 'SplashScreen') {
            enableFullscreenMode();
        } else {
            disableFullscreenMode();
        }

        // Cleanup on unmount
        return () => {
            disableFullscreenMode();
        };
    }, [isOpen, currentStep.component]);

    const enableFullscreenMode = () => {
        // Hide WordPress admin elements for fullscreen experience
        document.body.classList.add('bb-rl-fullscreen-mode');

        // Hide admin bar
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            adminBar.style.display = 'none';
        }

        // Hide admin menu
        const adminMenu = document.getElementById('adminmenumain');
        if (adminMenu) {
            adminMenu.style.display = 'none';
        }

        // Hide admin footer
        const adminFooter = document.getElementById('wpfooter');
        if (adminFooter) {
            adminFooter.style.display = 'none';
        }

        // Adjust main content area
        const wpwrap = document.getElementById('wpwrap');
        if (wpwrap) {
            wpwrap.style.marginLeft = '0';
        }
    };

    const disableFullscreenMode = () => {
        // Restore WordPress admin elements
        document.body.classList.remove('bb-rl-fullscreen-mode');

        // Restore admin bar
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            adminBar.style.display = '';
        }

        // Restore admin menu
        const adminMenu = document.getElementById('adminmenumain');
        if (adminMenu) {
            adminMenu.style.display = '';
        }

        // Restore admin footer
        const adminFooter = document.getElementById('wpfooter');
        if (adminFooter) {
            adminFooter.style.display = '';
        }

        // Restore main content area
        const wpwrap = document.getElementById('wpwrap');
        if (wpwrap) {
            wpwrap.style.marginLeft = '';
        }
    };

    const handleNext = async (formData = {}) => {
        setIsProcessing(true);

        try {
            // Save current step data if provided
            if (formData && currentStep.key) {
                setStepData(prev => ({
                    ...prev,
                    [currentStep.key]: formData
                }));

                // Save step data via callback
                if (onSaveStep) {
                    await onSaveStep({
                        step: currentStep.key,
                        data: formData,
                        timestamp: new Date().toISOString()
                    });
                }
            }

            // Check if this is the last step
            if (currentStepIndex >= totalSteps - 1) {
                // This is the finish step
                handleComplete();
            } else {
                // Move to next step
                setCurrentStepIndex(prev => Math.min(prev + 1, totalSteps - 1));
            }
        } catch (error) {
            console.error('Error proceeding to next step:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handlePrevious = () => {
        setCurrentStepIndex(prev => Math.max(prev - 1, 0));
    };

    const handleSkip = async () => {
        setIsProcessing(true);

        try {
            // Skip the entire onboarding
            if (onSkip) {
                await onSkip();
            }
        } catch (error) {
            console.error('Error skipping onboarding:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleComplete = async () => {
        setIsProcessing(true);

        try {
            // Complete the onboarding with all collected data
            if (onContinue) {
                await onContinue(stepData);
            }

            // Trigger completion event
            const event = new CustomEvent('bb_rl_onboarding_completed', {
                detail: {
                    stepData: stepData,
                    completedAt: new Date().toISOString()
                }
            });
            document.dispatchEvent(event);

            // Close the modal
            if (onClose) {
                onClose();
            }
        } catch (error) {
            console.error('Error completing onboarding:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleStepSkip = () => {
        // Skip current step and move to next
        if (currentStepIndex < totalSteps - 1) {
            setCurrentStepIndex(prev => prev + 1);
        } else {
            handleComplete();
        }
    };

    const handleClose = () => {
        disableFullscreenMode();
        if (onClose) {
            onClose();
        }
    };

    const renderCurrentStep = () => {
        if (!currentStep || !currentStep.component) {
            return (
                <div className="bb-rl-error-state">
                    <h2>{__('Step Not Found', 'buddyboss')}</h2>
                    <p>{__('The current step could not be loaded. Please try refreshing the page.', 'buddyboss')}</p>
                </div>
            );
        }

        // Check if component exists in registry
        if (!hasStepComponent(currentStep.component)) {
            return (
                <div className="bb-rl-error-state">
                    <h2>{__('Component Not Found', 'buddyboss')}</h2>
                    <p>
                        {__('Component', 'buddyboss')} "{currentStep.component}" {__('not found in registry.', 'buddyboss')}
                    </p>
                </div>
            );
        }

        // Get the component from registry
        const StepComponent = getStepComponent(currentStep.component);

        if (!StepComponent) {
            return (
                <div className="bb-rl-error-state">
                    <h2>{__('Component Load Error', 'buddyboss')}</h2>
                    <p>{__('Failed to load the step component.', 'buddyboss')}</p>
                </div>
            );
        }

        // Handle special case for SplashScreen (doesn't use BaseStepLayout)
        if (currentStep.component === 'SplashScreen') {
            return (
                <StepComponent
                    stepData={currentStep}
                    onNext={handleNext}
                    onSkip={handleSkip}
                />
            );
        }

        // Handle FinishScreen (custom fullscreen layout)
        if (currentStep.component === 'FinishScreen') {
            return (
                <StepComponent
                    stepData={currentStep}
                    onFinish={handleComplete}
                    onViewSite={() => {
                        window.open(window.bbRlOnboarding?.readylaunch?.site_url || window.location.origin, '_blank');
                    }}
                />
            );
        }

        // For all other steps that use BaseStepLayout
        return (
            <StepComponent
                stepData={currentStep}
                onNext={handleNext}
                onPrevious={handlePrevious}
                onSkip={handleStepSkip}
                currentStep={currentStepIndex}
                totalSteps={totalSteps}
                onSaveStep={onSaveStep}
                isProcessing={isProcessing}
            />
        );
    };

    if (!isOpen) {
        return null;
    }

    // Special handling for splash screen only (modal popup)
    if (currentStep.component === 'SplashScreen') {
        return (
            <div className="bb-rl-onboarding-overlay">
                <div className="bb-rl-onboarding-modal bb-rl-special-step">
                    <div className="bb-rl-modal-header">
                        <div className="bb-rl-modal-header-content">
                            <h2 className="bb-rl-modal-title">
	                            { currentStep?.title }
							</h2>
                            <p className="bb-rl-modal-description">
	                            { currentStep?.description }
							</p>
                        </div>
                        <Button
                            className="bb-rl-close-button"
                            onClick={handleClose}
                            label={__('Close', 'buddyboss')}
                            disabled={isProcessing}
                        >
                            <span className="bb-icons-rl-x"></span>
                        </Button>
                    </div>

                    <div className="bb-rl-modal-content">
                        {renderCurrentStep()}
                    </div>
                </div>
            </div>
        );
    }

    // Full screen layout for all step-based components (including FinishScreen)
    return (
        <div className="bb-rl-onboarding-overlay bb-rl-fullscreen">
            <div className="bb-rl-onboarding-modal bb-rl-fullscreen-modal">
                <div className="bb-rl-fullscreen-content">
                    {renderCurrentStep()}
                </div>
            </div>
        </div>
    );
};
