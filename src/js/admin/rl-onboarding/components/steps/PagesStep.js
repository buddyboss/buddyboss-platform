import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CheckboxControl, SelectControl } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const PagesStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        create_essential_pages: true,
        homepage_layout: 'activity'
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.pages || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.pages || {};
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
                step: 'pages',
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
                {/* Create Essential Pages */}
                {stepOptions.create_essential_pages && (
                    <div className="bb-rl-field-group">
                        <CheckboxControl
                            label={stepOptions.create_essential_pages.label}
                            help={stepOptions.create_essential_pages.description}
                            checked={formData.create_essential_pages}
                            onChange={(value) => handleInputChange('create_essential_pages', value)}
                        />
                        
                        {formData.create_essential_pages && (
                            <div className="bb-rl-pages-list">
                                <p className="bb-rl-pages-info">
                                    {__('The following pages will be created:', 'buddyboss')}
                                </p>
                                <ul className="bb-rl-essential-pages">
                                    <li>📄 {__('Privacy Policy', 'buddyboss')}</li>
                                    <li>📄 {__('Terms of Service', 'buddyboss')}</li>
                                    <li>📄 {__('About Us', 'buddyboss')}</li>
                                    <li>📄 {__('Contact', 'buddyboss')}</li>
                                </ul>
                            </div>
                        )}
                    </div>
                )}

                {/* Homepage Layout */}
                {stepOptions.homepage_layout && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.homepage_layout.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.homepage_layout.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-homepage-options">
                            {Object.entries(stepOptions.homepage_layout.options || {}).map(([value, label]) => (
                                <div 
                                    key={value}
                                    className={`bb-rl-homepage-option ${formData.homepage_layout === value ? 'bb-rl-selected' : ''}`}
                                    onClick={() => handleInputChange('homepage_layout', value)}
                                >
                                    <div className={`bb-rl-homepage-preview bb-rl-${value}`}>
                                        {value === 'activity' && (
                                            <>
                                                <div className="bb-rl-activity-item"></div>
                                                <div className="bb-rl-activity-item"></div>
                                                <div className="bb-rl-activity-item"></div>
                                            </>
                                        )}
                                        {value === 'custom' && (
                                            <div className="bb-rl-custom-content">
                                                <div className="bb-rl-custom-block"></div>
                                                <div className="bb-rl-custom-block"></div>
                                            </div>
                                        )}
                                        {value === 'landing' && (
                                            <>
                                                <div className="bb-rl-hero-section"></div>
                                                <div className="bb-rl-features-section"></div>
                                            </>
                                        )}
                                    </div>
                                    <span className="bb-rl-homepage-label">{label}</span>
                                    {formData.homepage_layout === value && (
                                        <span className="bb-rl-selected-icon">✓</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Page Structure Preview */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-page-structure">
                        <h4>{__('Your Site Structure', 'buddyboss')}</h4>
                        <div className="bb-rl-site-tree">
                            <div className="bb-rl-page-node bb-rl-homepage">
                                🏠 {__('Homepage', 'buddyboss')} 
                                <span className="bb-rl-layout-type">
                                    ({stepOptions.homepage_layout?.options?.[formData.homepage_layout] || formData.homepage_layout})
                                </span>
                            </div>
                            <div className="bb-rl-page-children">
                                <div className="bb-rl-page-node">👥 {__('Members', 'buddyboss')}</div>
                                <div className="bb-rl-page-node">👥 {__('Groups', 'buddyboss')}</div>
                                <div className="bb-rl-page-node">💬 {__('Forums', 'buddyboss')}</div>
                                <div className="bb-rl-page-node">📱 {__('Activity', 'buddyboss')}</div>
                                {formData.create_essential_pages && (
                                    <>
                                        <div className="bb-rl-page-node">📄 {__('Privacy Policy', 'buddyboss')}</div>
                                        <div className="bb-rl-page-node">📄 {__('Terms of Service', 'buddyboss')}</div>
                                        <div className="bb-rl-page-node">📄 {__('About Us', 'buddyboss')}</div>
                                        <div className="bb-rl-page-node">📄 {__('Contact', 'buddyboss')}</div>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Page Tips */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-tips-section">
                        <h4>{__('Page Setup Tips', 'buddyboss')}</h4>
                        <ul className="bb-rl-tips-list">
                            <li>{__('Essential pages help with legal compliance and user trust', 'buddyboss')}</li>
                            <li>{__('Activity feed homepage encourages community engagement', 'buddyboss')}</li>
                            <li>{__('Landing page is great for marketing and conversions', 'buddyboss')}</li>
                            <li>{__('You can always change these settings later', 'buddyboss')}</li>
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
            isFirstStep={false}
            isLastStep={currentStep === totalSteps - 1}
            currentStep={currentStep}
            totalSteps={totalSteps}
        >
            {renderFormFields()}
        </BaseStepLayout>
    );
}; 