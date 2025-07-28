import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { PreviewPages } from './previewPages';

export const BaseStepLayout = ({ 
    stepData, 
    children, 
    onNext, 
    onPrevious, 
    onSkip, 
    isFirstStep = false, 
    isLastStep = false,
    currentStep = 0,
    totalSteps = 0,
    formData = {},
    allStepData = {},
    page = 'activity'
}) => {
    const { title, description, image } = stepData;

    // Get the image URL from the assets
    const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl 
        ? `${window.bbRlOnboarding.assets.assetsUrl}${image}`
        : '';

    const stepsProgress = totalSteps > 0 ? Array.from({ length: totalSteps - 2 }, (_, index) => (
        <div className={`bb-rl-progress-step ${index + 1 < currentStep ? 'bb-rl-step-active' : ''}`} key={index}></div>
    )) : null;

    // Merge all step data with current form data for preview
    // Current step's formData takes precedence over saved allStepData
    const mergedFormData = {
        ...Object.values(allStepData).reduce((acc, stepData) => ({ ...acc, ...stepData }), {}),
        ...formData
    };

    console.log('All Step Data:', allStepData);
    console.log('Current Form Data:', formData);
    console.log('Merged Form Data:', mergedFormData);

    return (
        <div className="bb-rl-step-layout">

            {/* Main content area with left and right panels */}
            <div className="bb-rl-step-content">
                {/* Left Panel - Options and Controls */}
                <div className="bb-rl-left-panel">
                    <div className="bb-rl-step-header">
                        <a href={window.bbRlOnboarding?.dashboardUrl} className="bb-rl-step-back-button">
                            <span className="bb-icons-rl-caret-left"></span>
                            {__('WP Admin', 'buddyboss')}
                        </a>
                        <div className="bb-rl-logo">
                            <img src={window.bbRlOnboarding?.assets?.logo || ''} alt="BuddyBoss" />
                        </div>
                    </div>

                    <div className="bb-rl-left-panel-content">

                        <div className="bb-rl-step-info">
                            <h1 className="bb-rl-step-title">{title}</h1>
                            <p className="bb-rl-step-description">{description}</p>
                        </div>

                        <div className='bb-rl-step-progress'>
                            { stepsProgress }
                        </div>
                        
                        <div className="bb-rl-step-options">
                            {children}
                        </div>
                        
                        {/* Navigation buttons */}
                        <div className="bb-rl-step-navigation">
                            {!isFirstStep && (
                                <Button 
                                    className="bb-rl-nav-button bb-rl-previous-button"
                                    onClick={onPrevious}
                                >
                                    <span className="bb-icons-rl-arrow-left"></span>
                                    {__('Back', 'buddyboss')}
                                </Button>
                            )}
                            
                            <Button 
                                className="bb-rl-nav-button bb-rl-next-button"
                                onClick={onNext}
                                variant="primary"
                            >
                                {isLastStep ? __('Finish', 'buddyboss') : __('Next', 'buddyboss')}
                                {!isLastStep && <span className="bb-icons-rl-arrow-right"></span>}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Right Panel - Preview/Visual */}
                <div className="bb-rl-right-panel">
                    <div className="bb-rl-preview-pages">
                        <PreviewPages page={page} formData={mergedFormData} />
                    </div>
                </div>
            </div>
        </div>
    );
}; 