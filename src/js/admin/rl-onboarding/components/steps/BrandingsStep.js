import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, ColorPicker } from '@wordpress/components';
import { BaseStepLayout } from '../BaseStepLayout';

export const BrandingsStep = ({ 
    stepData, 
    onNext, 
    onPrevious, 
    onSkip, 
    currentStep, 
    totalSteps, 
    onSaveStep 
}) => {
    const [formData, setFormData] = useState({
        site_logo: '',
        favicon: '',
        brand_colors: '#e57e3a'
    });

    const [uploadingLogo, setUploadingLogo] = useState(false);
    const [uploadingFavicon, setUploadingFavicon] = useState(false);

    // Get step options from window.bbRlOnboarding
    const stepOptions = window.bbRlOnboarding?.stepOptions?.brandings || {};

    useEffect(() => {
        // Load any saved data for this step
        const savedData = window.bbRlOnboarding?.preferences?.brandings || {};
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

    const handleFileUpload = async (field, file) => {
        const setLoading = field === 'site_logo' ? setUploadingLogo : setUploadingFavicon;
        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'bb_rl_upload_branding_file');
            formData.append('nonce', window.bbRlOnboarding?.nonce || '');
            formData.append('field', field);

            const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                handleInputChange(field, data.data.url);
            } else {
                console.error('Upload failed:', data.data?.message);
            }
        } catch (error) {
            console.error('Upload error:', error);
        } finally {
            setLoading(false);
        }
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

    const renderFileUpload = (field, label, description, currentValue, isUploading) => {
        return (
            <div className="bb-rl-field-group">
                <div className="bb-rl-field-label">
                    <h4>{label}</h4>
                    <p className="bb-rl-field-description">{description}</p>
                </div>
                
                <div className="bb-rl-file-upload">
                    {currentValue ? (
                        <div className="bb-rl-file-preview">
                            <img src={currentValue} alt={label} className="bb-rl-uploaded-image" />
                            <Button 
                                className="bb-rl-remove-file"
                                onClick={() => handleInputChange(field, '')}
                                isDestructive
                                variant="link"
                            >
                                {__('Remove', 'buddyboss')}
                            </Button>
                        </div>
                    ) : (
                        <div className="bb-rl-upload-area">
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(e) => {
                                    const file = e.target.files[0];
                                    if (file) {
                                        handleFileUpload(field, file);
                                    }
                                }}
                                className="bb-rl-file-input"
                                id={`bb-rl-${field}`}
                                disabled={isUploading}
                            />
                            <label htmlFor={`bb-rl-${field}`} className="bb-rl-upload-label">
                                <div className="bb-rl-upload-icon">📁</div>
                                <div className="bb-rl-upload-text">
                                    {isUploading ? __('Uploading...', 'buddyboss') : __('Click to upload', 'buddyboss')}
                                </div>
                            </label>
                        </div>
                    )}
                </div>
            </div>
        );
    };

    const renderFormFields = () => {
        return (
            <div className="bb-rl-form-fields">
                {/* Site Logo Upload */}
                {stepOptions.site_logo && renderFileUpload(
                    'site_logo',
                    stepOptions.site_logo.label,
                    stepOptions.site_logo.description,
                    formData.site_logo,
                    uploadingLogo
                )}

                {/* Favicon Upload */}
                {stepOptions.favicon && renderFileUpload(
                    'favicon',
                    stepOptions.favicon.label,
                    stepOptions.favicon.description,
                    formData.favicon,
                    uploadingFavicon
                )}

                {/* Brand Colors */}
                {stepOptions.brand_colors && (
                    <div className="bb-rl-field-group">
                        <div className="bb-rl-field-label">
                            <h4>{stepOptions.brand_colors.label}</h4>
                            <p className="bb-rl-field-description">
                                {stepOptions.brand_colors.description}
                            </p>
                        </div>
                        
                        <div className="bb-rl-color-picker-container">
                            <div className="bb-rl-color-preview-box">
                                <div 
                                    className="bb-rl-color-swatch"
                                    style={{ backgroundColor: formData.brand_colors }}
                                />
                                <span className="bb-rl-color-value">{formData.brand_colors}</span>
                            </div>
                            <ColorPicker
                                color={formData.brand_colors}
                                onChange={(color) => handleInputChange('brand_colors', color.hex)}
                            />
                        </div>
                    </div>
                )}

                {/* Brand Preview */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-branding-preview">
                        <h4>{__('Brand Preview', 'buddyboss')}</h4>
                        <div className="bb-rl-brand-preview-container">
                            <div 
                                className="bb-rl-mock-header"
                                style={{ backgroundColor: formData.brand_colors }}
                            >
                                {formData.site_logo ? (
                                    <img 
                                        src={formData.site_logo} 
                                        alt="Logo" 
                                        className="bb-rl-mock-logo"
                                    />
                                ) : (
                                    <div className="bb-rl-mock-logo-placeholder">
                                        {__('Your Logo', 'buddyboss')}
                                    </div>
                                )}
                                <div className="bb-rl-mock-nav">
                                    <span>{__('Home', 'buddyboss')}</span>
                                    <span>{__('Members', 'buddyboss')}</span>
                                    <span>{__('Groups', 'buddyboss')}</span>
                                </div>
                            </div>
                            <div className="bb-rl-mock-content">
                                <div className="bb-rl-mock-activity">
                                    <div 
                                        className="bb-rl-mock-button"
                                        style={{ backgroundColor: formData.brand_colors }}
                                    >
                                        {__('Join Community', 'buddyboss')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Branding Tips */}
                <div className="bb-rl-field-group">
                    <div className="bb-rl-tips-section">
                        <h4>{__('Branding Tips', 'buddyboss')}</h4>
                        <ul className="bb-rl-tips-list">
                            <li>{__('Logo should be at least 200px wide for best quality', 'buddyboss')}</li>
                            <li>{__('Favicon should be 32x32px or 16x16px', 'buddyboss')}</li>
                            <li>{__('Choose colors that reflect your brand identity', 'buddyboss')}</li>
                            <li>{__('Ensure good contrast for accessibility', 'buddyboss')}</li>
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