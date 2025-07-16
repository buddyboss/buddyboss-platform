import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl, SelectControl } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const CommunitySetupStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        site_title: '',
        privacy_mode: 'public'
    });

    const [errors, setErrors] = useState({});

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.community_setup || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.community_setup || {};
        if (Object.keys(savedData).length > 0) {
            setFormData(prev => ({ ...prev, ...savedData }));
        }
    }, []);

    const handleInputChange = (field, value) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));

        // Clear error for this field
        if (errors[field]) {
            setErrors(prev => ({
                ...prev,
                [field]: null
            }));
        }
    };

    const validateForm = () => {
        const newErrors = {};

        // Check required fields
        if (stepOptions.site_title?.required && !formData.site_title.trim()) {
            newErrors.site_title = __('Community name is required', 'buddyboss');
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleNext = async () => {
        if (!validateForm()) {
            return;
        }

        // Save step data
        if (onSaveStep) {
            await onSaveStep({
                step: 'community_setup',
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
                {/* Site Title Field */}
                {stepOptions.site_title && (
                    <div className="bb-rl-field-group">
                        <TextControl
                            label={stepOptions.site_title.label}
                            help={stepOptions.site_title.description}
                            value={formData.site_title}
                            onChange={(value) => handleInputChange('site_title', value)}
                            placeholder={__('Enter your community name', 'buddyboss')}
                            className={errors.site_title ? 'bb-rl-field-error' : ''}
                        />
                        {errors.site_title && (
                            <p className="bb-rl-error-message">{errors.site_title}</p>
                        )}
                    </div>
                )}

                {/* Privacy Mode Field */}
                {stepOptions.privacy_mode && (
                    <div className="bb-rl-field-group">
                        <SelectControl
                            label={stepOptions.privacy_mode.label}
                            help={stepOptions.privacy_mode.description}
                            value={formData.privacy_mode}
                            onChange={(value) => handleInputChange('privacy_mode', value)}
                            options={Object.entries(stepOptions.privacy_mode.options || {}).map(([value, label]) => ({
                                value,
                                label
                            }))}
                        />
                    </div>
                )}

                {/* Additional community setup options */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-info-section">
                        <h4>{__('What you\'ll get:', 'buddyboss')}</h4>
                        <ul className="bb-rl-feature-list">
                            <li>
                                <span className="bb-rl-check-icon">✓</span>
                                {__('Member profiles and directories', 'buddyboss')}
                            </li>
                            <li>
                                <span className="bb-rl-check-icon">✓</span>
                                {__('Activity feeds and social interactions', 'buddyboss')}
                            </li>
                            <li>
                                <span className="bb-rl-check-icon">✓</span>
                                {__('Private messaging system', 'buddyboss')}
                            </li>
                            <li>
                                <span className="bb-rl-check-icon">✓</span>
                                {__('Groups and forums', 'buddyboss')}
                            </li>
                        </ul>
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
            isFirstStep={currentStep === 0}
            isLastStep={currentStep === totalSteps - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
        >
            {renderFormFields()}
        </BaseStepLayout>
    );
}; 