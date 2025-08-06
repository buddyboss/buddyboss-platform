import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseStepLayout } from '../BaseStepLayout';
import { DynamicStepRenderer } from '../DynamicStepRenderer';

export const CommunitySetupStep = ({
    stepData,
    onNext,
    onPrevious,
    onSkip,
    currentStep,
    totalSteps,
    onAutoSave,
    savedData = {},
    allStepData = {}
}) => {
    const [formData, setFormData] = useState({
        ...savedData
    });

    const [errors, setErrors] = useState({});

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.community_setup || {};

    useEffect(() => {
        // Initialize with defaults from step options and saved data
        const initialData = {}; // Set base defaults
        
        Object.entries(stepOptions).forEach(([key, config]) => {
            if (config.value !== undefined) {
                initialData[key] = config.value;
            } else if (config.default !== undefined) {
                initialData[key] = config.default;
            }
        });

        // Only use savedData for fields that have actual values (not empty strings)
        const validSavedData = {};
        Object.entries(savedData).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                validSavedData[key] = value;
            }
        });

        setFormData({
            ...initialData,
            ...validSavedData
        });
    }, [savedData, stepOptions]);

    const handleFormChange = (newFormData) => {
        setFormData(newFormData);
        
        // Clear any validation errors
        setErrors({});
    };

    const validateForm = () => {
        const newErrors = {};

        // Check required fields
        Object.entries(stepOptions).forEach(([key, config]) => {
            if (config.required && (!formData[key] || formData[key].trim() === '')) {
                newErrors[key] = config.label 
                    ? __(`${config.label} is required`, 'buddyboss')
                    : __('This field is required', 'buddyboss');
            }
        });

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleNext = async () => {
        if (!validateForm()) {
            return;
        }



        if (onNext) {
            onNext(formData);
        }
    };

    return (
        <BaseStepLayout
            stepData={stepData}
            onNext={handleNext}
            onPrevious={onPrevious}
            onSkip={onSkip}
            isFirstStep={currentStep === 1} // Skip splash screen
            isLastStep={currentStep === totalSteps - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
            formData={formData}
            allStepData={allStepData}
        >
            <DynamicStepRenderer
                stepKey="community_setup"
                stepOptions={stepOptions}
                initialData={formData}
                onChange={handleFormChange}
                onAutoSave={onAutoSave}
                allStepData={allStepData}
            />
            
            {Object.keys(errors).length > 0 && (
                <div className="bb-rl-form-errors">
                    {Object.entries(errors).map(([field, message]) => (
                        <p key={field} className="bb-rl-error-message">
                            {message}
                        </p>
                    ))}
                </div>
            )}
        </BaseStepLayout>
    );
};
