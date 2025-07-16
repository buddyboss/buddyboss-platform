import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { CheckboxControl, SelectControl } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const SideMenusStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        enable_primary_menu: true,
        enable_member_menu: true,
        menu_style: 'horizontal'
    });

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.side_menus || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.side_menus || {};
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
                step: 'side_menus',
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
                {/* Enable Primary Menu */}
                {stepOptions.enable_primary_menu && (
                    <div className="bb-rl-field-group">
                        <CheckboxControl
                            label={stepOptions.enable_primary_menu.label}
                            help={stepOptions.enable_primary_menu.description}
                            checked={formData.enable_primary_menu}
                            onChange={(value) => handleInputChange('enable_primary_menu', value)}
                        />
                    </div>
                )}

                {/* Enable Member Menu */}
                {stepOptions.enable_member_menu && (
                    <div className="bb-rl-field-group">
                        <CheckboxControl
                            label={stepOptions.enable_member_menu.label}
                            help={stepOptions.enable_member_menu.description}
                            checked={formData.enable_member_menu}
                            onChange={(value) => handleInputChange('enable_member_menu', value)}
                        />
                    </div>
                )}

                {/* Menu Style */}
                {stepOptions.menu_style && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.menu_style.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.menu_style.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-menu-style-options">
                            {Object.entries(stepOptions.menu_style.options || {}).map(([value, label]) => (
                                <div 
                                    key={value}
                                    className={`bb-rl-menu-option ${formData.menu_style === value ? 'bb-rl-selected' : ''}`}
                                    onClick={() => handleInputChange('menu_style', value)}
                                >
                                    <div className={`bb-rl-menu-preview bb-rl-${value}`}>
                                        {value === 'horizontal' ? (
                                            <>
                                                <div className="bb-rl-horizontal-header">
                                                    <div className="bb-rl-logo-area"></div>
                                                    <div className="bb-rl-nav-items">
                                                        <div className="bb-rl-nav-item"></div>
                                                        <div className="bb-rl-nav-item"></div>
                                                        <div className="bb-rl-nav-item"></div>
                                                    </div>
                                                </div>
                                                <div className="bb-rl-content-area"></div>
                                            </>
                                        ) : (
                                            <>
                                                <div className="bb-rl-vertical-layout">
                                                    <div className="bb-rl-sidebar-nav">
                                                        <div className="bb-rl-nav-item"></div>
                                                        <div className="bb-rl-nav-item"></div>
                                                        <div className="bb-rl-nav-item"></div>
                                                    </div>
                                                    <div className="bb-rl-main-content"></div>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                    <span className="bb-rl-menu-label">{label}</span>
                                    {formData.menu_style === value && (
                                        <span className="bb-rl-selected-icon">✓</span>
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Menu Preview */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-menu-structure">
                        <h4>{__('Your Navigation Structure', 'buddyboss')}</h4>
                        
                        {formData.enable_primary_menu && (
                            <div className="bb-rl-menu-section">
                                <h5>🧭 {__('Primary Navigation', 'buddyboss')}</h5>
                                <div className="bb-rl-menu-items">
                                    <div className="bb-rl-menu-item">🏠 {__('Home', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">👥 {__('Members', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">👥 {__('Groups', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">💬 {__('Forums', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">📱 {__('Activity', 'buddyboss')}</div>
                                </div>
                            </div>
                        )}

                        {formData.enable_member_menu && (
                            <div className="bb-rl-menu-section">
                                <h5>👤 {__('Member Menu', 'buddyboss')}</h5>
                                <div className="bb-rl-menu-items">
                                    <div className="bb-rl-menu-item">📊 {__('Dashboard', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">👤 {__('Profile', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">⚙️ {__('Settings', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">📧 {__('Messages', 'buddyboss')}</div>
                                    <div className="bb-rl-menu-item">🚪 {__('Logout', 'buddyboss')}</div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Menu Features */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-features-section">
                        <h4>{__('Navigation Features', 'buddyboss')}</h4>
                        <div className="bb-rl-feature-grid">
                            <div className="bb-rl-feature-card">
                                <div className="bb-rl-feature-icon">📱</div>
                                <h5>{__('Mobile Responsive', 'buddyboss')}</h5>
                                <p>{__('Automatically adapts to mobile devices', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-feature-card">
                                <div className="bb-rl-feature-icon">🎨</div>
                                <h5>{__('Customizable', 'buddyboss')}</h5>
                                <p>{__('Easy to customize colors and styles', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-feature-card">
                                <div className="bb-rl-feature-icon">⚡</div>
                                <h5>{__('Fast Loading', 'buddyboss')}</h5>
                                <p>{__('Optimized for speed and performance', 'buddyboss')}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Menu Tips */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-tips-section">
                        <h4>{__('Navigation Tips', 'buddyboss')}</h4>
                        <ul className="bb-rl-tips-list">
                            <li>{__('Primary navigation helps users find main content areas', 'buddyboss')}</li>
                            <li>{__('Member menu provides quick access to personal features', 'buddyboss')}</li>
                            <li>{__('Horizontal menus work well for desktop users', 'buddyboss')}</li>
                            <li>{__('Vertical sidebars save space and look modern', 'buddyboss')}</li>
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