import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseStepLayout } from '../BaseStepLayout';
import { DynamicStepRenderer } from '../DynamicStepRenderer';

export const BrandingsStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep,
    onAutoSave,
    savedData = {}
}) => {
    const [formData, setFormData] = useState({
        site_logo: '',
        favicon: '',
        brand_colors: '#e57e3a',
        ...savedData
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.brandings || {};

    useEffect(() => {
        // Initialize with defaults from step options and saved data
        const initialData = {};
        Object.entries(stepOptions).forEach(([key, config]) => {
            if (config.default !== undefined) {
                initialData[key] = config.default;
            }
        });

        setFormData(prev => ({
            ...initialData,
            ...prev,
            ...savedData
        }));
    }, [savedData, stepOptions]);

    const handleFormChange = (newFormData) => {
        setFormData(newFormData);
    };

    const handleNext = async () => {
        // Save step data
        if (onSaveStep) {
            await onSaveStep({
                step: 'brandings',
                data: formData,
                timestamp: new Date().toISOString()
            });
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
            isFirstStep={false}
            isLastStep={currentStep === totalSteps - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
        >
            <DynamicStepRenderer
                stepKey="brandings"
                stepOptions={stepOptions}
                initialData={formData}
                onChange={handleFormChange}
                onAutoSave={onAutoSave}
            />
        </BaseStepLayout>
    );
}; 