import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseStepLayout } from '../BaseStepLayout';
import { DynamicStepRenderer } from '../DynamicStepRenderer';
import { getInitialFormData } from '../../../utils/formDefaults';

export const SiteAppearanceStep = ({
    stepData,
    onNext,
    onPrevious,
    onSkip,
    currentStep,
    totalSteps,
    skipProgressCount = 0,
    onAutoSave,
    savedData = {},
    allStepData = {}
}) => {
    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.site_appearance || {};
    
    const [formData, setFormData] = useState(() => getInitialFormData(stepOptions, savedData));

    const handleFormChange = (newFormData) => {
        setFormData(newFormData);
    };

    const handleNext = async () => {


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
            isLastStep={currentStep === totalSteps - skipProgressCount - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
            formData={formData}
            allStepData={allStepData}
        >
            <DynamicStepRenderer
                stepKey="site_appearance"
                stepOptions={stepOptions}
                initialData={formData}
                onChange={handleFormChange}
                onAutoSave={onAutoSave}
                allStepData={allStepData}
            />
        </BaseStepLayout>
    );
};
