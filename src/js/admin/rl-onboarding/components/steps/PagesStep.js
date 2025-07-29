import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseStepLayout } from '../BaseStepLayout';
import { DynamicStepRenderer } from '../DynamicStepRenderer';

export const PagesStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep,
    onAutoSave,
    savedData = {},
    allStepData = {}
}) => {
    const [formData, setFormData] = useState({
        create_essential_pages: true,
        homepage_layout: 'activity',
        ...savedData
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.pages || {};

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
                step: 'pages',
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
            formData={formData}
            allStepData={allStepData}
            page="all"
        >
            <DynamicStepRenderer
                stepKey="pages"
                stepOptions={stepOptions}
                initialData={formData}
                onChange={handleFormChange}
                onAutoSave={onAutoSave}
                allStepData={allStepData}
            />
        </BaseStepLayout>
    );
}; 