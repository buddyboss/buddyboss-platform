import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { SelectControl, RadioControl } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const SiteAppearanceStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        color_scheme: 'default',
        site_layout: 'fullwidth'
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.site_appearance || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.site_appearance || {};
        if (Object.keys(savedData).length > 0) {
            setFormData(prev => ({ ...prev, ...savedData }));
        }
    }, []);

    const handleInputChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleNext = async () => {
        // Save step data
        if (onSaveStep) {
            await onSaveStep({
                step: 'site_appearance',
                data: formData,
                timestamp: new Date().toISOString()
            });
        }

        if (onNext) {
            onNext(formData);
        }
    };

    const renderFormFields = () => {
        return (
            <div className="bb-rl-form-fields">
                {/* Color Scheme Field */}
                {stepOptions.color_scheme && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.color_scheme.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.color_scheme.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-color-scheme-options">
                            {Object.entries(stepOptions.color_scheme.options || {}).map(([value, label]) => (
                                <div 
                                    key={value}
                                    className={`bb-rl-color-option ${formData.color_scheme === value ? 'bb-rl-selected' : ''}`}
                                    onClick={() => handleInputChange('color_scheme', value)}
                                >
                                    <div className={`bb-rl-color-preview bb-rl-color-${value}`}>
                                        <div className="bb-rl-color-swatch bb-rl-primary"></div>
                                        <div className="bb-rl-color-swatch bb-rl-secondary"></div>
                                        <div className="bb-rl-color-swatch bb-rl-accent"></div>
                                    </div>
                                    <span className="bb-rl-color-label">{label}</span>
                                    {formData.color_scheme === value && (
                                        <span className="bb-rl-selected-icon">✓</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Site Layout Field */}
                {stepOptions.site_layout && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.site_layout.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.site_layout.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-layout-options">
                            {Object.entries(stepOptions.site_layout.options || {}).map(([value, label]) => (
                                <div 
                                    key={value}
                                    className={`bb-rl-layout-option ${formData.site_layout === value ? 'bb-rl-selected' : ''}`}
                                    onClick={() => handleInputChange('site_layout', value)}
                                >
                                    <div className={`bb-rl-layout-preview bb-rl-layout-${value}`}>
                                        <div className="bb-rl-layout-header"></div>
                                        <div className="bb-rl-layout-content">
                                            <div className="bb-rl-layout-main"></div>
                                            {value === 'boxed' && <div className="bb-rl-layout-sidebar"></div>}
                                        </div>
                                    </div>
                                    <span className="bb-rl-layout-label">{label}</span>
                                    {formData.site_layout === value && (
                                        <span className="bb-rl-selected-icon">✓</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Preview section */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-appearance-preview">
                        <h4>{__('Preview', 'buddyboss')}</h4>
                        <div className="bb-rl-preview-container">
                            <div className={`bb-rl-site-preview bb-rl-${formData.color_scheme} bb-rl-${formData.site_layout}`}>
                                <div className="bb-rl-preview-header">
                                    <div className="bb-rl-preview-logo"></div>
                                    <div className="bb-rl-preview-nav"></div>
                                </div>
                                <div className="bb-rl-preview-content">
                                    <div className="bb-rl-preview-sidebar">
                                        <div className="bb-rl-preview-widget"></div>
                                        <div className="bb-rl-preview-widget"></div>
                                    </div>
                                    <div className="bb-rl-preview-main">
                                        <div className="bb-rl-preview-activity"></div>
                                        <div className="bb-rl-preview-activity"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    return (
        <BaseStepLayout
            stepData={stepData}
            onNext={handleNext}
            onPrevious={onPrevious}
            onSkip={onSkip}
            isFirstStep={false}
            isLastStep={currentStep === totalSteps - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
        >
            {renderFormFields()}
        </BaseStepLayout>
    );
}; 