import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

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
    rightPanelContent = null
}) => {
    const { title, description, image } = stepData;
    
    // Get the image URL from the assets
    const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl 
        ? `${window.bbRlOnboarding.assets.assetsUrl}${image}`
        : '';

    const progressPercentage = totalSteps > 0 ? ((currentStep + 1) / totalSteps) * 100 : 0;

    return (
        <div className="bb-rl-step-layout">
            {/* Header with progress */}
            <div className="bb-rl-step-header">
                <div className="bb-rl-step-progress">
                    <div className="bb-rl-progress-bar">
                        <div 
                            className="bb-rl-progress-fill" 
                            style={{ width: `${progressPercentage}%` }}
                        />
                    </div>
                    <div className="bb-rl-progress-text">
                        {__('Step', 'buddyboss')} {currentStep + 1} {__('of', 'buddyboss')} {totalSteps}
                    </div>
                </div>
                
                <div className="bb-rl-step-actions">
                    <Button 
                        className="bb-rl-skip-button"
                        onClick={onSkip}
                    >
                        {__('Skip for now', 'buddyboss')}
                    </Button>
                </div>
            </div>

            {/* Main content area with left and right panels */}
            <div className="bb-rl-step-content">
                {/* Left Panel - Options and Controls */}
                <div className="bb-rl-left-panel">
                    <div className="bb-rl-step-info">
                        <h1 className="bb-rl-step-title">{title}</h1>
                        <p className="bb-rl-step-description">{description}</p>
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
                                {__('Previous', 'buddyboss')}
                            </Button>
                        )}
                        
                        <Button 
                            className="bb-rl-nav-button bb-rl-next-button"
                            onClick={onNext}
                            variant="primary"
                        >
                            {isLastStep ? __('Finish', 'buddyboss') : __('Next', 'buddyboss')}
                        </Button>
                    </div>
                </div>

                {/* Right Panel - Preview/Visual */}
                <div className="bb-rl-right-panel">
                    {rightPanelContent ? (
                        rightPanelContent
                    ) : (
                        <div className="bb-rl-step-preview">
                            {imageUrl && (
                                <div className="bb-rl-preview-image">
                                    <img src={imageUrl} alt={title} />
                                </div>
                            )}
                            <div className="bb-rl-preview-placeholder">
                                <p>{__('Live preview will appear here', 'buddyboss')}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}; 