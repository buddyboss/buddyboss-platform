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
        // Initialize step data and restore progress from backend
        initializeOnboarding();
    }, []);

    // Initialize onboarding data and restore progress
    const initializeOnboarding = async () => {
        // Load saved preferences
        const savedData = {};
        if (window.bbRlOnboarding?.preferences) {
            Object.keys(window.bbRlOnboarding.preferences).forEach(key => {
                savedData[key] = window.bbRlOnboarding.preferences[key];
            });
        }
        setStepData(savedData);

        // Restore step progress
        const progress = window.bbRlOnboarding?.progress;
        if (progress && progress.current_step > 0 && progress.current_step < totalSteps) {
            setCurrentStepIndex(progress.current_step);
        }
    };

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
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
        
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
        
        // Restore body scroll
        document.body.style.overflow = '';
        
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

    // Save step progress and data to backend
    const saveStepProgress = async (stepIndex, formData = {}) => {
        try {
            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'rl_onboarding_save_step_progress',
                    nonce: window.bbRlOnboarding?.nonce || '',
                    step: stepIndex,
                    data: JSON.stringify({
                        step_key: currentStep.key,
                        form_data: formData,
                        timestamp: new Date().toISOString()
                    }),
                }),
            });

            const data = await response.json();
            
            if (data.success) {
                // Update local progress data
                if (window.bbRlOnboarding?.progress) {
                    window.bbRlOnboarding.progress = data.data.progress;
                }
                return true;
            } else {
                console.error('Error saving step progress:', data.data?.message || 'Unknown error');
                return false;
            }
        } catch (error) {
            console.error('Error saving step progress:', error);
            return false;
        }
    };

    // Auto-save preferences for dynamic options
    const autoSavePreferences = async (preferences) => {
        try {
            // Debug the request parameters
            const ajaxUrl = window.bbRlOnboarding?.ajaxUrl || window.ajaxurl;
            const nonce = window.bbRlOnboarding?.nonce || '';
            const wizardId = window.bbRlOnboarding?.wizardId || '';
            const action = wizardId + '_save_preferences';
            
            console.log('AutoSave Debug:', {
                ajaxUrl,
                nonce,
                wizardId,
                action,
                preferences
            });

            if (!ajaxUrl) {
                console.error('AJAX URL not available');
                return false;
            }

            if (!nonce) {
                console.error('Nonce not available');
                return false;
            }

            const response = await fetch(ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: action,
                    nonce: nonce,
                    preferences: JSON.stringify(preferences),
                }),
            });

            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                // Update local preferences
                if (window.bbRlOnboarding?.preferences) {
                    window.bbRlOnboarding.preferences = { ...window.bbRlOnboarding.preferences, ...preferences };
                }
                return true;
            } else {
                console.error('Error saving preferences:', data.data?.message || 'Unknown error');
                return false;
            }
        } catch (error) {
            console.error('Error saving preferences:', error);
            console.error('Error details:', error.message);
            return false;
        }
    };

    const handleNext = async (formData = {}) => {
        setIsProcessing(true);

        try {
            // Save current step progress
            const saveSuccess = await saveStepProgress(currentStepIndex, formData);
            
            if (saveSuccess) {
                // Update step data
                const stepKey = currentStep.key;
                setStepData(prev => ({
                    ...prev,
                    [stepKey]: formData
                }));

                // Auto-save preferences
                await autoSavePreferences({ [stepKey]: formData });

                // Check if this is the last step
                if (currentStepIndex >= totalSteps - 1) {
                    await handleComplete(formData);
                } else {
                    setCurrentStepIndex(prev => prev + 1);
                }
            }
        } catch (error) {
            console.error('Error handling next step:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handlePrevious = () => {
        if (currentStepIndex > 0) {
            setCurrentStepIndex(prev => prev - 1);
        }
    };

    const handleStepSkip = async () => {
        setIsProcessing(true);

        try {
            // Save step as skipped
            await saveStepProgress(currentStepIndex, { skipped: true });
            
            // Move to next step
            if (currentStepIndex < totalSteps - 1) {
                setCurrentStepIndex(prev => prev + 1);
            } else {
                await handleComplete();
            }
        } catch (error) {
            console.error('Error skipping step:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleComplete = async (finalData = {}) => {
        setIsProcessing(true);

        try {
            // Collect all step data for final settings
            const finalSettings = { ...stepData };
            if (finalData && Object.keys(finalData).length > 0) {
                const stepKey = currentStep.key;
                finalSettings[stepKey] = finalData;
            }

            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'rl_onboarding_complete',
                    nonce: window.bbRlOnboarding?.nonce || '',
                    finalSettings: JSON.stringify(finalSettings),
                }),
            });

            const data = await response.json();

            if (data.success) {
                console.log('Onboarding completed successfully:', data.data);
                
                // Trigger completion event
                const event = new CustomEvent('bb_rl_onboarding_completed', {
                    detail: { data: data.data, finalSettings }
                });
                document.dispatchEvent(event);

                // Close modal and redirect to dashboard
                if (onClose) {
                    onClose();
                }
                
                // Redirect to dashboard after brief delay
                setTimeout(() => {
                    window.location.href = window.bbRlOnboarding?.dashboardUrl || '/wp-admin/';
                }, 1000);
            } else {
                console.error('Error completing onboarding:', data.data?.message || 'Unknown error');
            }
        } catch (error) {
            console.error('Error completing onboarding:', error);
        } finally {
            setIsProcessing(false);
        }
    };

    const handleClose = () => {
        disableFullscreenMode();
        if (onClose) {
            onClose();
        }
    };

    // Enhanced step component render with auto-save support
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
                    onSkip={handleStepSkip}
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
                onAutoSave={autoSavePreferences}
                isProcessing={isProcessing}
                savedData={stepData[currentStep.key] || {}}
                allStepData={stepData}
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
