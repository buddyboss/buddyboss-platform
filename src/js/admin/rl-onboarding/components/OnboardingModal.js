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

        // Handle special cases for components that don't use BaseStepLayout
        if (currentStep.component === 'SplashScreen') {
            return (
                <StepComponent
                    stepData={currentStep}
                    onNext={handleNext}
                    onSkip={handleSkip}
                />
            );
        }

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

    // Special handling for splash screen and finish screen (full modal)
    if (currentStep.component === 'SplashScreen' || currentStep.component === 'FinishScreen') {
        return (
            <div className="bb-rl-onboarding-overlay">
                <div className="bb-rl-onboarding-modal bb-rl-special-step">
                    <div className="bb-rl-modal-header">
                        <div className="bb-rl-logo">
                            <img src={window.bbRlOnboarding?.assets?.logo || ''} alt="BuddyBoss" />
                        </div>
                        {currentStep.component === 'SplashScreen' && (
                            <Button 
                                className="bb-rl-close-button"
                                onClick={onClose}
                                label={__('Close', 'buddyboss')}
                                disabled={isProcessing}
                            >
                                <span className="dashicons dashicons-no-alt"></span>
                            </Button>
                        )}
                    </div>
                    
                    <div className="bb-rl-modal-content">
                        {renderCurrentStep()}
                    </div>
                </div>
            </div>
        );
    }

    // Standard step layout (left/right panels)
    return (
        <div className="bb-rl-onboarding-overlay">
            <div className="bb-rl-onboarding-modal bb-rl-step-modal">
                <div className="bb-rl-modal-header">
                    <div className="bb-rl-logo">
                        <img src={window.bbRlOnboarding?.assets?.logo || ''} alt="BuddyBoss" />
                    </div>
                    <Button 
                        className="bb-rl-close-button"
                        onClick={onClose}
                        label={__('Close', 'buddyboss')}
                        disabled={isProcessing}
                    >
                        <span className="dashicons dashicons-no-alt"></span>
                    </Button>
                </div>
                
                <div className="bb-rl-modal-content">
                    {renderCurrentStep()}
                </div>
            </div>
        </div>
    );
}; 