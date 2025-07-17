import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { 
    TextControl, 
    SelectControl, 
    CheckboxControl, 
    RadioControl,
    ColorPicker,
    Button,
    Panel,
    PanelBody
} from '@wordpress/components';

export const DynamicStepRenderer = ({ 
    stepKey, 
    stepOptions = {}, 
    initialData = {}, 
    onChange,
    onAutoSave
}) => {
    // Extract default values from stepOptions configuration
    const getDefaultValues = () => {
        const defaults = {};
        Object.entries(stepOptions).forEach(([fieldKey, fieldConfig]) => {
            // Skip description and hr fields as they're not form inputs
            if (fieldConfig.type === 'description' || fieldConfig.type === 'hr') {
                return;
            }
            
            // Check for both 'value' and 'default' properties
            if (fieldConfig.value !== undefined) {
                defaults[fieldKey] = fieldConfig.value;
            } else if (fieldConfig.default !== undefined) {
                defaults[fieldKey] = fieldConfig.default;
            }
        });
        return defaults;
    };

    // Initialize form data with defaults from stepOptions, then overlay initialData
    const [formData, setFormData] = useState(() => {
        const defaults = getDefaultValues();
        return { ...defaults, ...initialData };
    });
    const [autoSaveTimeout, setAutoSaveTimeout] = useState(null);

    useEffect(() => {
        // Update form data when initial data changes, preserving defaults
        const defaults = getDefaultValues();
        setFormData(prev => ({ ...defaults, ...prev, ...initialData }));
    }, [initialData, stepOptions]);

    const handleFieldChange = (field, value) => {
        const newFormData = {
            ...formData,
            [field]: value
        };
        
        setFormData(newFormData);
        
        // Trigger onChange immediately
        if (onChange) {
            onChange(newFormData);
        }

        // Auto-save with debounce
        if (onAutoSave) {
            if (autoSaveTimeout) {
                clearTimeout(autoSaveTimeout);
            }
            
            const timeout = setTimeout(() => {
                onAutoSave({ [stepKey]: newFormData });
            }, 1000); // Auto-save after 1 second of no changes
            
            setAutoSaveTimeout(timeout);
        }
    };

    const renderField = (fieldKey, fieldConfig) => {
        const { type, label, description, options, required, default: defaultValue, value: configValue } = fieldConfig;
        // Use value from formData, or fall back to 'value' or 'default' from config
        // Check if formData has a non-empty value, otherwise fall back to config value or default
        const hasValidFormValue = formData[fieldKey] !== undefined && formData[fieldKey] !== '' && formData[fieldKey] !== null;
        const value = hasValidFormValue ? formData[fieldKey] : (configValue !== undefined ? configValue : defaultValue);

        switch (type) {
            case 'text':
                // Add placeholder for fields without labels
                const placeholder = !label && fieldKey === 'blogname' ? __('Enter site title...', 'buddyboss') : '';
                
                return (
                    <TextControl
                        key={fieldKey}
                        label={label || ''}
                        help={description || ''}
                        placeholder={placeholder}
                        value={value || ''}
                        onChange={(newValue) => handleFieldChange(fieldKey, newValue)}
                        required={required}
                    />
                );

            case 'select':
                return (
                    <SelectControl
                        key={fieldKey}
                        label={label || ''}
                        help={description || ''}
                        value={value || ''}
                        onChange={(newValue) => handleFieldChange(fieldKey, newValue)}
                        options={Object.entries(options || {}).map(([optionValue, optionLabel]) => ({
                            value: optionValue,
                            label: optionLabel
                        }))}
                    />
                );

            case 'checkbox':
                return (
                    <CheckboxControl
                        key={fieldKey}
                        label={label || ''}
                        help={description || ''}
                        checked={Boolean(value)}
                        onChange={(newValue) => handleFieldChange(fieldKey, newValue)}
                    />
                );

            case 'radio':
                return (
                    <div key={fieldKey} className="bb-rl-field-group">
                        {label && (
                            <div className="bb-rl-field-label">
                                <h4>{label}</h4>
                                {description && (
                                    <p className="bb-rl-field-description">{description}</p>
                                )}
                            </div>
                        )}
                        <RadioControl
                            selected={value || ''}
                            options={Object.entries(options || {}).map(([optionValue, optionLabel]) => ({
                                value: optionValue,
                                label: optionLabel
                            }))}
                            onChange={(newValue) => handleFieldChange(fieldKey, newValue)}
                        />
                    </div>
                );

            case 'color':
                return (
                    <div key={fieldKey} className="bb-rl-field-group">
                        {label && (
                            <div className="bb-rl-field-label">
                                <h4>{label}</h4>
                                {description && (
                                    <p className="bb-rl-field-description">{description}</p>
                                )}
                            </div>
                        )}
                        <div className="bb-rl-color-picker-wrapper">
                            <ColorPicker
                                color={value || '#e57e3a'}
                                onChange={(newValue) => handleFieldChange(fieldKey, newValue)}
                                disableAlpha
                            />
                        </div>
                    </div>
                );

            case 'media':
                return (
                    <div key={fieldKey} className="bb-rl-field-group">
                        {label && (
                            <div className="bb-rl-field-label">
                                <h4>{label}</h4>
                                {description && (
                                    <p className="bb-rl-field-description">{description}</p>
                                )}
                            </div>
                        )}
                        <div className="bb-rl-media-field">
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    // Open WordPress media library
                                    const mediaFrame = wp.media({
                                        title: label,
                                        multiple: false,
                                        library: { type: 'image' }
                                    });

                                    mediaFrame.on('select', () => {
                                        const attachment = mediaFrame.state().get('selection').first().toJSON();
                                        handleFieldChange(fieldKey, attachment.id);
                                        handleFieldChange(fieldKey + '_url', attachment.url);
                                    });

                                    mediaFrame.open();
                                }}
                            >
                                {value ? __('Change Image', 'buddyboss') : __('Select Image', 'buddyboss')}
                            </Button>
                            
                            {formData[fieldKey + '_url'] && (
                                <div className="bb-rl-media-preview">
                                    <img 
                                        src={formData[fieldKey + '_url']} 
                                        alt={label}
                                        style={{ maxWidth: '200px', height: 'auto' }}
                                    />
                                    <Button
                                        variant="link"
                                        isDestructive
                                        onClick={() => {
                                            handleFieldChange(fieldKey, '');
                                            handleFieldChange(fieldKey + '_url', '');
                                        }}
                                    >
                                        {__('Remove', 'buddyboss')}
                                    </Button>
                                </div>
                            )}
                        </div>
                    </div>
                );

            case 'visual_radio_options':
                return (
                        <div className="bb-rl-field-group"><div className="bb-rl-color-scheme-options">
                                {Object.entries(stepOptions.bb_rl_theme_mode.options || {}).map((option,index) => (
                                    <div 
                                        key={index}
                                        className={`bb-rl-color-option ${formData.color_scheme === option[0] ? 'bb-rl-selected' : ''}`}
                                    >
                                        <label className={`bb-rl-color-preview bb-rl-color-${option[0]}`} onClick={() => handleFieldChange(fieldKey, option[0])}>
                                            <i className={option[1].icon_class}></i>
                                            <span className="bb-rl-color-details">
                                                <span className="bb-rl-color-label">{option[1].label}</span>
                                                <span className="bb-rl-color-description">{option[1].description}</span>
                                            </span>
                                            <div className="bb-rl-custom-radio-input">
                                                <input type="radio" name="color_scheme" value={option[0]} checked={formData.color_scheme === option[0]}  />
                                                <span className="bb-rl-custom-radio-icon"></span>
                                            </div>
                                        </label>
                                    </div>
                                ))}
                            </div>
                        </div>
                );

            case 'visual_options':
                return (
                    <div key={fieldKey} className="bb-rl-field-group">
                        {label && (
                            <div className="bb-rl-field-label">
                                <h4>{label}</h4>
                                {description && (
                                    <p className="bb-rl-field-description">{description}</p>
                                )}
                            </div>
                        )}
                        <div className="bb-rl-visual-options">
                            {Object.entries(options || {}).map(([optionValue, optionConfig]) => {
                                // Handle both old string format and new object format
                                const optionLabel = typeof optionConfig === 'string' ? optionConfig : optionConfig.label;
                                const optionDescription = typeof optionConfig === 'object' ? optionConfig.description : '';
                                const iconClass = typeof optionConfig === 'object' ? optionConfig.icon_class : '';
                                
                                return (
                                    <div 
                                        key={optionValue}
                                        className={`bb-rl-visual-option ${value === optionValue ? 'bb-rl-selected' : ''}`}
                                        onClick={() => handleFieldChange(fieldKey, optionValue)}
                                    >
                                        <div className={`bb-rl-option-preview bb-rl-${fieldKey}-${optionValue}`}>
                                            {iconClass && <span className={iconClass}></span>}
                                            {renderVisualPreview(fieldKey, optionValue)}
                                        </div>
                                        <div className="bb-rl-option-content">
                                            <span className="bb-rl-option-label">{optionLabel}</span>
                                            {optionDescription && (
                                                <span className="bb-rl-option-description">{optionDescription}</span>
                                            )}
                                        </div>
                                        {value === optionValue && (
                                            <span className="bb-rl-selected-icon">✓</span>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );

            case 'description':
                return (
                    <div key={fieldKey} className="bb-rl-field-group bb-rl-description">
                        <p className="bb-rl-field-description">
                            {description}
                        </p>
                    </div>
                );

            case 'hr':
                return (
                    <div key={fieldKey} className="bb-rl-field-group bb-rl-separator">
                        <hr className="bb-rl-horizontal-rule" />
                    </div>
                );

            default:
                return (
                    <div key={fieldKey} className="bb-rl-field-group">
                        <p className="bb-rl-error-message">
                            {__('Unknown field type:', 'buddyboss')} {type}
                        </p>
                    </div>
                );
        }
    };

    const renderVisualPreview = (fieldKey, optionValue) => {
        // Custom preview renderers for different field types
        switch (fieldKey) {
            case 'bb_rl_theme_mode':
            case 'color_scheme': // Keep backward compatibility
                return (
                    <div className="bb-rl-color-scheme-preview">
                        <div className={`bb-rl-color-swatch bb-rl-primary-${optionValue}`}></div>
                        <div className={`bb-rl-color-swatch bb-rl-secondary-${optionValue}`}></div>
                        <div className={`bb-rl-color-swatch bb-rl-accent-${optionValue}`}></div>
                    </div>
                );

            case 'site_layout':
                return (
                    <div className="bb-rl-layout-preview">
                        <div className="bb-rl-layout-header"></div>
                        <div className="bb-rl-layout-content">
                            <div className="bb-rl-layout-main"></div>
                            {optionValue === 'boxed' && <div className="bb-rl-layout-sidebar"></div>}
                        </div>
                    </div>
                );

            case 'homepage_layout':
                if (optionValue === 'activity') {
                    return (
                        <>
                            <div className="bb-rl-activity-item"></div>
                            <div className="bb-rl-activity-item"></div>
                            <div className="bb-rl-activity-item"></div>
                        </>
                    );
                } else if (optionValue === 'custom') {
                    return (
                        <div className="bb-rl-custom-content">
                            <div className="bb-rl-custom-block"></div>
                            <div className="bb-rl-custom-block"></div>
                        </div>
                    );
                } else if (optionValue === 'landing') {
                    return (
                        <>
                            <div className="bb-rl-hero-section"></div>
                            <div className="bb-rl-features-section"></div>
                        </>
                    );
                }
                break;

            case 'menu_style':
                if (optionValue === 'horizontal') {
                    return (
                        <div className="bb-rl-menu-preview bb-rl-horizontal">
                            <div className="bb-rl-menu-item"></div>
                            <div className="bb-rl-menu-item"></div>
                            <div className="bb-rl-menu-item"></div>
                        </div>
                    );
                } else if (optionValue === 'vertical') {
                    return (
                        <div className="bb-rl-menu-preview bb-rl-vertical">
                            <div className="bb-rl-menu-item"></div>
                            <div className="bb-rl-menu-item"></div>
                            <div className="bb-rl-menu-item"></div>
                        </div>
                    );
                }
                break;

            case 'widget_areas':
                return (
                    <div className="bb-rl-widget-area-preview">
                        <div className="bb-rl-content-area">
                            {(optionValue === 'all' || optionValue === 'sidebar') && (
                                <div className="bb-rl-sidebar-area">
                                    <div className="bb-rl-widget-placeholder"></div>
                                    <div className="bb-rl-widget-placeholder"></div>
                                </div>
                            )}
                            <div className="bb-rl-main-content-area"></div>
                        </div>
                        {(optionValue === 'all' || optionValue === 'footer') && (
                            <div className="bb-rl-footer-area">
                                <div className="bb-rl-widget-placeholder"></div>
                                <div className="bb-rl-widget-placeholder"></div>
                                <div className="bb-rl-widget-placeholder"></div>
                            </div>
                        )}
                    </div>
                );

            default:
                return <div className="bb-rl-default-preview"></div>;
        }
    };

    return (
        <div className="bb-rl-dynamic-step-renderer">
            <div className="bb-rl-form-fields">
                {Object.entries(stepOptions).map(([fieldKey, fieldConfig]) => {
                    // Render description and hr fields directly without the wrapper div
                    if (fieldConfig.type === 'description' || fieldConfig.type === 'hr') {
                        return renderField(fieldKey, fieldConfig);
                    }
                    
                    // For all other field types, wrap in field-group
                    return (
                        <div key={fieldKey} className="bb-rl-field-group">
                            {renderField(fieldKey, fieldConfig)}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}; 