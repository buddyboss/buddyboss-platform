import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseStepLayout } from '../BaseStepLayout';
import { DynamicStepRenderer } from '../DynamicStepRenderer';

export const SideMenusStep = ({ 
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
        enable_primary_menu: true,
        enable_member_menu: true,
        menu_style: 'horizontal',
        ...savedData
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.side_menus || {};

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
                step: 'side_menus',
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
                stepKey="side_menus"
                stepOptions={stepOptions}
                initialData={formData}
                onChange={handleFormChange}
                onAutoSave={onAutoSave}
            />
        </BaseStepLayout>
    );
}; 