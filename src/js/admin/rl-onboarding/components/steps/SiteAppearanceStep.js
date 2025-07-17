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
                        
                        <div className="bb-rl-color-scheme-options">
                            {Object.entries(stepOptions.color_scheme.options || {}).map((option,index) => (
                                <div 
                                    key={index}
                                    className={`bb-rl-color-option ${formData.color_scheme === option[0] ? 'bb-rl-selected' : ''}`}
                                >
                                    <label className={`bb-rl-color-preview bb-rl-color-${option[0]}`}>
                                        <i className='bb-icons-rl'></i>
                                        <span className="bb-rl-color-details">
                                            <span className="bb-rl-color-label">{option[1].label}</span>
                                            <span className="bb-rl-color-description">{option[1].description}</span>
                                        </span>
                                        <div className="bb-rl-custom-radio-input">
                                            <input type="radio" name="color_scheme" value={option[0]} checked={formData.color_scheme === option[0]} onChange={() => handleInputChange('color_scheme', option[0])} />
                                            <span className="bb-rl-custom-radio-icon"></span>
                                        </div>
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
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