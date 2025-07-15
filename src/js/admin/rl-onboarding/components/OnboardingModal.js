import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const OnboardingModal = ({ isOpen, onClose, onContinue, onSkip, onSaveStep }) => {
    const [currentStep, setCurrentStep] = useState(0);
    const [selectedOption, setSelectedOption] = useState(null);
    const [isConfiguring, setIsConfiguring] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);
    const [configData, setConfigData] = useState({
        siteName: '',
        siteDescription: '',
        activityFeed: true,
        userGroups: true,
        privateMessages: true,
        memberDirectory: true,
        primaryColor: '#e57e3a',
        layoutStyle: 'default',
    });

    // Modal steps content
    const steps = [
        {
            title: __('Welcome to BuddyBoss', 'buddyboss'),
            subtitle: __('Let\'s bring your community to life by choosing the look and feel that matches your vision.', 'buddyboss'),
            content: (
                <div className="bb-rl-onboarding-step">
                    <div className="bb-rl-onboarding-options">
                        <div className="bb-rl-option-card">
                            <div className="bb-rl-option-header">
                                <h3>{__('BuddyBoss Theme', 'buddyboss')}</h3>
                                <p className="bb-rl-option-subtitle">{__('Customizable WordPress theme', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-option-preview">
                                <img src={window.bbRlOnboarding?.assets?.buddybossThemePreview || ''} alt="BuddyBoss Theme Preview" />
                            </div>
                            <div className="bb-rl-option-description">
                                {__('Our crafted theme made just for the BuddyBoss Platform giving you full control to design a community that feels truly yours.', 'buddyboss')}
                            </div>
                            <div className="bb-rl-option-features">
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Advanced customization', 'buddyboss')}
                                </div>
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Deep integration support', 'buddyboss')}
                                </div>
                            </div>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-secondary"
                                onClick={() => handleOptionSelect('buddyboss-theme')}
                                disabled={isProcessing}
                            >
                                {__('Configure BuddyBoss Theme', 'buddyboss')}
                            </Button>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-primary"
                                onClick={() => handleOptionSelect('buddyboss-theme-buy')}
                                disabled={isProcessing}
                            >
                                {__('Buy Theme', 'buddyboss')}
                            </Button>
                        </div>

                        <div className="bb-rl-option-card">
                            <div className="bb-rl-option-header">
                                <h3>{__('ReadyLaunch', 'buddyboss')}</h3>
                                <p className="bb-rl-option-subtitle">{__('Community features for any WordPress theme', 'buddyboss')}</p>
                            </div>
                            <div className="bb-rl-option-preview">
                                <img src={window.bbRlOnboarding?.assets?.currentThemePreview || ''} alt="ReadyLaunch Preview" />
                            </div>
                            <div className="bb-rl-option-description">
                                {__('Get your community up and running in no time with our easy to use template system.', 'buddyboss')}
                            </div>
                            <div className="bb-rl-option-features">
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Minimal configuration', 'buddyboss')}
                                </div>
                                <div className="bb-rl-feature">
                                    <span className="bb-rl-feature-icon">✓</span>
                                    {__('Supports any WordPress Theme', 'buddyboss')}
                                </div>
                            </div>
                            <Button 
                                className="bb-rl-option-button bb-rl-button-primary"
                                onClick={() => handleOptionSelect('readylaunch')}
                                disabled={isProcessing}
                            >
                                {isProcessing && selectedOption === 'readylaunch' ? __('Processing...', 'buddyboss') : __('Configure ReadyLaunch', 'buddyboss')}
                            </Button>
                        </div>
                    </div>
                </div>
            )
        }
    ];

    const handleOptionSelect = async (option) => {
        setSelectedOption(option);
        setIsProcessing(true);
        
        // Save welcome step data
        if (onSaveStep) {
            const stepData = {
                step: 'welcome',
                selected_option: option,
                timestamp: new Date().toISOString(),
            };
            await onSaveStep(stepData);
        }
        
        if (option === 'readylaunch') {
            // Start the ReadyLaunch configuration process
            setIsConfiguring(true);
            enableFullscreenMode();
            setIsProcessing(false);
        } else {
            // Handle other options (BuddyBoss theme, etc.)
            if (onContinue) {
                await onContinue(option);
            }
            setIsProcessing(false);
        }
    };

    const enableFullscreenMode = () => {
        // Hide WordPress admin elements for fullscreen experience
        document.body.classList.add('bb-rl-fullscreen-mode');
        
        // Hide admin bar
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            adminBar.style.display = 'none';
        }
        
        // Hide admin menu
        const adminMenu = document.getElementById('adminmenumain');
        if (adminMenu) {
            adminMenu.style.display = 'none';
        }
        
        // Hide admin footer
        const adminFooter = document.getElementById('wpfooter');
        if (adminFooter) {
            adminFooter.style.display = 'none';
        }
        
        // Adjust main content area
        const wpwrap = document.getElementById('wpwrap');
        if (wpwrap) {
            wpwrap.style.marginLeft = '0';
        }
    };

    const disableFullscreenMode = () => {
        // Restore WordPress admin elements
        document.body.classList.remove('bb-rl-fullscreen-mode');
        
        // Restore admin bar
        const adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            adminBar.style.display = '';
        }
        
        // Restore admin menu
        const adminMenu = document.getElementById('adminmenumain');
        if (adminMenu) {
            adminMenu.style.display = '';
        }
        
        // Restore admin footer
        const adminFooter = document.getElementById('wpfooter');
        if (adminFooter) {
            adminFooter.style.display = '';
        }
        
        // Restore main content area
        const wpwrap = document.getElementById('wpwrap');
        if (wpwrap) {
            wpwrap.style.marginLeft = '';
        }
    };

    const handleClose = () => {
        if (isConfiguring) {
            disableFullscreenMode();
        }
        if (onClose) {
            onClose();
        }
    };

    const handleSkip = () => {
        if (onSkip) {
            onSkip();
        }
    };

    const handleBackToSelection = () => {
        setIsConfiguring(false);
        setSelectedOption(null);
        disableFullscreenMode();
    };

    const handleConfigChange = (field, value) => {
        setConfigData(prev => ({
            ...prev,
            [field]: value,
        }));
    };

    const completeOnboarding = async () => {
        setIsProcessing(true);
        
        // Save configuration step data
        if (onSaveStep) {
            const stepData = {
                step: 'configuration',
                readylaunch_settings: {
                    theme_mode: 'light',
                    enabled_pages: ['activity', 'members', 'groups'],
                    config_data: configData,
                },
                timestamp: new Date().toISOString(),
            };
            await onSaveStep(stepData);
        }
        
        if (onContinue) {
            await onContinue(selectedOption || '');
        }
        
        setIsProcessing(false);
        handleClose();
    };

    const renderConfigurationScreen = () => {
        return (
            <div className="bb-rl-configuration-screen">
                <div className="bb-rl-config-header">
                    <div className="bb-rl-config-header-left">
                        <Button 
                            className="bb-rl-back-button"
                            onClick={handleBackToSelection}
                            disabled={isProcessing}
                        >
                            ← {__('Back', 'buddyboss')}
                        </Button>
                        <h1>{__('Configure ReadyLaunch', 'buddyboss')}</h1>
                    </div>
                    <div className="bb-rl-config-header-right">
                        <Button 
                            className="bb-rl-finish-button"
                            onClick={completeOnboarding}
                            disabled={isProcessing}
                        >
                            {isProcessing ? __('Processing...', 'buddyboss') : __('Finish Setup', 'buddyboss')}
                        </Button>
                    </div>
                </div>

                <div className="bb-rl-config-content">
                    <div className="bb-rl-config-sidebar">
                        <div className="bb-rl-config-section">
                            <h3>{__('Site Information', 'buddyboss')}</h3>
                            <div className="bb-rl-config-field">
                                <label>{__('Site Name', 'buddyboss')}</label>
                                <input 
                                    type="text" 
                                    value={configData.siteName}
                                    onChange={(e) => handleConfigChange('siteName', e.target.value)}
                                    placeholder={__('Enter your site name', 'buddyboss')} 
                                />
                            </div>
                            <div className="bb-rl-config-field">
                                <label>{__('Site Description', 'buddyboss')}</label>
                                <textarea 
                                    value={configData.siteDescription}
                                    onChange={(e) => handleConfigChange('siteDescription', e.target.value)}
                                    placeholder={__('Enter your site description', 'buddyboss')}
                                ></textarea>
                            </div>
                        </div>

                        <div className="bb-rl-config-section">
                            <h3>{__('Community Features', 'buddyboss')}</h3>
                            <div className="bb-rl-config-field">
                                <label className="bb-rl-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        checked={configData.activityFeed}
                                        onChange={(e) => handleConfigChange('activityFeed', e.target.checked)}
                                    />
                                    {__('Enable Activity Feed', 'buddyboss')}
                                </label>
                            </div>
                            <div className="bb-rl-config-field">
                                <label className="bb-rl-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        checked={configData.userGroups}
                                        onChange={(e) => handleConfigChange('userGroups', e.target.checked)}
                                    />
                                    {__('Enable User Groups', 'buddyboss')}
                                </label>
                            </div>
                            <div className="bb-rl-config-field">
                                <label className="bb-rl-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        checked={configData.privateMessages}
                                        onChange={(e) => handleConfigChange('privateMessages', e.target.checked)}
                                    />
                                    {__('Enable Private Messages', 'buddyboss')}
                                </label>
                            </div>
                            <div className="bb-rl-config-field">
                                <label className="bb-rl-checkbox-label">
                                    <input 
                                        type="checkbox" 
                                        checked={configData.memberDirectory}
                                        onChange={(e) => handleConfigChange('memberDirectory', e.target.checked)}
                                    />
                                    {__('Enable Member Directory', 'buddyboss')}
                                </label>
                            </div>
                        </div>

                        <div className="bb-rl-config-section">
                            <h3>{__('Appearance', 'buddyboss')}</h3>
                            <div className="bb-rl-config-field">
                                <label>{__('Primary Color', 'buddyboss')}</label>
                                <input 
                                    type="color" 
                                    value={configData.primaryColor}
                                    onChange={(e) => handleConfigChange('primaryColor', e.target.value)}
                                />
                            </div>
                            <div className="bb-rl-config-field">
                                <label>{__('Layout Style', 'buddyboss')}</label>
                                <select 
                                    value={configData.layoutStyle}
                                    onChange={(e) => handleConfigChange('layoutStyle', e.target.value)}
                                >
                                    <option value="default">{__('Default', 'buddyboss')}</option>
                                    <option value="boxed">{__('Boxed', 'buddyboss')}</option>
                                    <option value="wide">{__('Wide', 'buddyboss')}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div className="bb-rl-config-preview">
                        <div className="bb-rl-preview-header">
                            <h3>{__('Live Preview', 'buddyboss')}</h3>
                            <p className="bb-rl-preview-note">{__('Preview will update based on your configuration', 'buddyboss')}</p>
                        </div>
                        <div className="bb-rl-preview-content">
                            <iframe 
                                src={window.location.origin + '?bb_rl_preview=1'} 
                                frameBorder="0"
                                className="bb-rl-preview-iframe"
                                title={__('Site Preview', 'buddyboss')}
                            />
                        </div>
                    </div>
                </div>
            </div>
        );
    };

    if (!isOpen) {
        return null;
    }

    // Show configuration screen if configuring ReadyLaunch
    if (isConfiguring) {
        return (
            <div className="bb-rl-onboarding-overlay bb-rl-fullscreen">
                <div className="bb-rl-onboarding-modal bb-rl-fullscreen-modal">
                    {renderConfigurationScreen()}
                </div>
            </div>
        );
    }

    // Show initial selection screen
    const currentStepData = steps[currentStep];

    return (
        <div className="bb-rl-onboarding-overlay">
            <div className="bb-rl-onboarding-modal">
                <div className="bb-rl-onboarding-header">
                    <div className="bb-rl-logo">
                        <img src={window.bbRlOnboarding?.assets?.logo || ''} alt="BuddyBoss" />
                    </div>
                    <div className="bb-rl-header-actions">
                        <Button 
                            className="bb-rl-skip-button"
                            onClick={handleSkip}
                            disabled={isProcessing}
                        >
                            {__('Skip for now', 'buddyboss')}
                        </Button>
                        <Button 
                            className="bb-rl-close-button"
                            onClick={handleClose}
                            label={__('Close', 'buddyboss')}
                            disabled={isProcessing}
                        >
                            <span className="dashicons dashicons-no-alt"></span>
                        </Button>
                    </div>
                </div>

                <div className="bb-rl-onboarding-content">
                    <div className="bb-rl-onboarding-title">
                        <h1>{currentStepData.title}</h1>
                        {currentStepData.subtitle && (
                            <p className="bb-rl-subtitle">{currentStepData.subtitle}</p>
                        )}
                    </div>

                    <div className="bb-rl-onboarding-body">
                        {currentStepData.content}
                    </div>
                </div>
            </div>
        </div>
    );
}; 