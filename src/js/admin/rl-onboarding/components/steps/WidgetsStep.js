import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CheckboxControl, SelectControl } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const WidgetsStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        enable_sidebar_widgets: true,
        default_widgets: true,
        widget_areas: 'all'
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.widgets || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.widgets || {};
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
                step: 'widgets',
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
                {/* Enable Sidebar Widgets */}
                {stepOptions.enable_sidebar_widgets && (
                    <div className="bb-rl-field-group">
                        <CheckboxControl
                            label={stepOptions.enable_sidebar_widgets.label}
                            help={stepOptions.enable_sidebar_widgets.description}
                            checked={formData.enable_sidebar_widgets}
                            onChange={(value) => handleInputChange('enable_sidebar_widgets', value)}
                        />
                    </div>
                )}

                {/* Default Widgets */}
                {stepOptions.default_widgets && (
                    <div className="bb-rl-field-group">
                        <CheckboxControl
                            label={stepOptions.default_widgets.label}
                            help={stepOptions.default_widgets.description}
                            checked={formData.default_widgets}
                            onChange={(value) => handleInputChange('default_widgets', value)}
                        />
                        
                        {formData.default_widgets && (
                            <div className="bb-rl-default-widgets-list">
                                <p className="bb-rl-widgets-info">
                                    {__('The following widgets will be added:', 'buddyboss')}
                                </p>
                                <ul className="bb-rl-widget-list">
                                    <li>🏃 {__('Recent Activity', 'buddyboss')}</li>
                                    <li>👥 {__('Member List', 'buddyboss')}</li>
                                    <li>👥 {__('Active Groups', 'buddyboss')}</li>
                                    <li>💬 {__('Recent Forum Topics', 'buddyboss')}</li>
                                    <li>📝 {__('Recent Posts', 'buddyboss')}</li>
                                </ul>
                            </div>
                        )}
                    </div>
                )}

                {/* Widget Areas */}
                {stepOptions.widget_areas && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.widget_areas.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.widget_areas.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-widget-area-options">
                            {Object.entries(stepOptions.widget_areas.options || {}).map(([value, label]) => (
                                <div 
                                    key={value}
                                    className={`bb-rl-widget-area-option ${formData.widget_areas === value ? 'bb-rl-selected' : ''}`}
                                    onClick={() => handleInputChange('widget_areas', value)}
                                >
                                    <div className={`bb-rl-widget-area-preview bb-rl-${value}`}>
                                        <div className="bb-rl-preview-layout">
                                            <div className="bb-rl-content-area">
                                                {(value === 'all' || value === 'sidebar') && (
                                                    <div className="bb-rl-sidebar-area">
                                                        <div className="bb-rl-widget-placeholder"></div>
                                                        <div className="bb-rl-widget-placeholder"></div>
                                                    </div>
                                                )}
                                                <div className="bb-rl-main-content-area"></div>
                                            </div>
                                            {(value === 'all' || value === 'footer') && (
                                                <div className="bb-rl-footer-area">
                                                    <div className="bb-rl-widget-placeholder"></div>
                                                    <div className="bb-rl-widget-placeholder"></div>
                                                    <div className="bb-rl-widget-placeholder"></div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                    <span className="bb-rl-widget-area-label">{label}</span>
                                    {formData.widget_areas === value && (
                                        <span className="bb-rl-selected-icon">✓</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Widget Layout Preview */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-widget-layout-preview">
                        <h4>{__('Your Widget Layout', 'buddyboss')}</h4>
                        <div className="bb-rl-layout-mockup">
                            <div className="bb-rl-mockup-header">
                                <div className="bb-rl-mockup-logo"></div>
                                <div className="bb-rl-mockup-nav"></div>
                            </div>
                            <div className="bb-rl-mockup-content">
                                {formData.enable_sidebar_widgets && (formData.widget_areas === 'all' || formData.widget_areas === 'sidebar') && (
                                    <div className="bb-rl-mockup-sidebar">
                                        <div className="bb-rl-mockup-widget">
                                            <h5>{__('Recent Activity', 'buddyboss')}</h5>
                                            <div className="bb-rl-activity-items">
                                                <div className="bb-rl-activity-item"></div>
                                                <div className="bb-rl-activity-item"></div>
                                            </div>
                                        </div>
                                        <div className="bb-rl-mockup-widget">
                                            <h5>{__('Members', 'buddyboss')}</h5>
                                            <div className="bb-rl-member-list">
                                                <div className="bb-rl-member-item"></div>
                                                <div className="bb-rl-member-item"></div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                <div className="bb-rl-mockup-main">
                                    <div className="bb-rl-main-content">
                                        <div className="bb-rl-content-block"></div>
                                        <div className="bb-rl-content-block"></div>
                                    </div>
                                </div>
                            </div>
                            {formData.widget_areas === 'all' || formData.widget_areas === 'footer' && (
                                <div className="bb-rl-mockup-footer">
                                    <div className="bb-rl-footer-widget"></div>
                                    <div className="bb-rl-footer-widget"></div>
                                    <div className="bb-rl-footer-widget"></div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Widget Benefits */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-widget-benefits">
                        <h4>{__('Widget Benefits', 'buddyboss')}</h4>
                        <div className="bb-rl-benefit-grid">
                            <div className="bb-rl-benefit-item">
                                <div className="bb-rl-benefit-icon">⚡</div>
                                <h5>{__('Increased Engagement', 'buddyboss')}</h5>
                                <p>{__('Keep users engaged with relevant content', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-benefit-item">
                                <div className="bb-rl-benefit-icon">🎯</div>
                                <h5>{__('Better Navigation', 'buddyboss')}</h5>
                                <p>{__('Help users find what they\'re looking for', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-benefit-item">
                                <div className="bb-rl-benefit-icon">📊</div>
                                <h5>{__('Dynamic Content', 'buddyboss')}</h5>
                                <p>{__('Automatically updated content areas', 'buddyboss')}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Widget Tips */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-tips-section">
                        <h4>{__('Widget Tips', 'buddyboss')}</h4>
                        <ul className="bb-rl-tips-list">
                            <li>{__('Sidebar widgets are great for secondary navigation and community highlights', 'buddyboss')}</li>
                            <li>{__('Footer widgets work well for links, social media, and contact information', 'buddyboss')}</li>
                            <li>{__('Default widgets can be customized or replaced later', 'buddyboss')}</li>
                            <li>{__('Widget areas help organize content and improve user experience', 'buddyboss')}</li>
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