import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
    TextControl,
    SelectControl,
    ToggleControl,
    RadioControl,
    ColorPicker,
    ColorIndicator,
    Button,
    Popover,
    Panel,
    PanelBody
} from '@wordpress/components';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

export const DynamicStepRenderer = ({
    stepKey,
    stepOptions = {},
    initialData = {},
    onChange,
    onAutoSave,
    allStepData = {} // Add access to all step data for conditional logic
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
        const { type, options, required, default: defaultValue, value: configValue } = fieldConfig;

        // Use dynamic label and description functions
        const label = getDynamicLabel(fieldKey, fieldConfig);
        const description = getDynamicDescription(fieldKey, fieldConfig);

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
                    <div className={`bb-rl-toggle-wrapper ${fieldConfig.not_available ? 'bb-rl-not-available' : ''} ${Boolean(value) ? 'bb-rl-toggle-checked' : ''}`}>
                        { fieldConfig.icon && (
                            <div className='bb-rl-toggle-icon'>
                                <i className={fieldConfig.icon}></i>
                            </div>
                        )}
                        <ToggleControl
                            key={fieldKey}
                            label={
                                <>
                                    {label || ''}
                                    {fieldConfig.not_available && <span className="bb-rl-coming-soon">Coming Soon</span>}
                                </>
                            }
                            help={description || ''}
                            checked={Boolean(value)}
                            onChange={fieldConfig.not_available ? undefined : (newValue) => handleFieldChange(fieldKey, newValue)}
                        />
                    </div>
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
                            <ColorPickerButton color={value} onChange={(newValue) => handleFieldChange(fieldKey, newValue)} />
                        </div>
                    </div>
                );

            case 'media':
                // Helper function to open the WordPress media library (same as ReadyLaunch)
                const openMediaLibrary = (fieldLabel, onSelect) => {
                    // Check if wp is defined and media is available
                    if (typeof window.wp === 'undefined' || !window.wp.media) {
                        console.error('WordPress Media API is not available');
                        alert('WordPress Media API is not available. Please make sure WordPress Media is properly loaded.');
                        return;
                    }

                    // Create the media frame
                    const mediaFrame = window.wp.media({
                        title: __('Select or Upload Media', 'buddyboss'),
                        button: {
                            text: __('Use this media', 'buddyboss'),
                        },
                        multiple: false,
                        library: {
                            type: 'image'
                        }
                    });

                    mediaFrame.on('select', function() {
                        const attachment = mediaFrame.state().get('selection').first().toJSON();
                        const imageData = {
                            id: attachment.id, // Save the WordPress attachment ID
                            url: attachment.url,
                            alt: attachment.alt || '',
                            title: attachment.title || ''
                        };
                        onSelect(imageData);
                    });

                    mediaFrame.open();
                };

                // ImageSelector component (same as ReadyLaunch)
                const ImageSelector = ({ label, value, onChange, description, customClass }) => {
                    return (
                        <div className={`image-selector-component ${customClass || ''}`}>
                            <div className="image-selector-control">
                                {value && value.url ? (
                                    <div className="bb-rl-image-preview-wrapper">
                                        <div className="bb-rl-image-preview-block">
                                            <img
                                                src={value.url}
                                                alt={value.alt || ''}
                                                className="image-preview"
                                            />
                                        </div>
                                        <div className="image-actions">
                                            <Button
                                                onClick={() => openMediaLibrary(label, onChange)}
                                                className="change-image-button bb-rl-button bb-rl-button--secondary bb-rl-button--small"
                                                icon={<i className="bb-icons-rl-upload-simple" />}
                                            >
                                                {__('Replace', 'buddyboss')}
                                            </Button>
                                            <Button
                                                onClick={() => onChange(null)}
                                                className="remove-image-button bb-rl-button bb-rl-button--outline bb-rl-button--small"
                                                icon={<i className="bb-icons-rl-x" />}
                                            >
                                                {__('Remove', 'buddyboss')}
                                            </Button>
                                        </div>
                                    </div>
                                ) : (
                                    <Button
                                        onClick={() => openMediaLibrary(label, onChange)}
                                        className="bb-rl-upload-image-button"
                                        icon={<i className="bb-icons-rl-plus" />}
                                    />
                                )}
                                {description && (
                                    <p className="field-description">{description}</p>
                                )}
                            </div>
                        </div>
                    );
                };

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
                        <ImageSelector
                            label={label}
                            value={value}
                            onChange={(imageData) => handleFieldChange(fieldKey, imageData)}
                            description={description}
                            customClass={fieldConfig.customClass}
                        />
                    </div>
                );

            case 'visual_radio_options':
                return (
                        <div className="bb-rl-field-group">
	                        {label && (
		                        <div className="bb-rl-field-label">
			                        <h4>{label}</h4>
			                        {description && (
				                        <p className="bb-rl-field-description">{description}</p>
			                        )}
		                        </div>
	                        )}
	                        <div className="bb-rl-color-scheme-options">
		                        {Object.entries(options || {}).map(([optionValue, optionConfig]) => {
	                                // Handle both old string format and new object format
	                                const optionLabel = typeof optionConfig === 'string' ? optionConfig : optionConfig.label;
	                                const optionDescription = typeof optionConfig === 'object' ? optionConfig.description : '';
	                                const iconClass = typeof optionConfig === 'object' ? optionConfig.icon_class : '';

			                        return (
									<div
		                                key={optionValue}
			                            className={`bb-rl-color-option ${value === optionValue ? 'bb-rl-selected' : ''}`}
		                            >
				                        <label className={`bb-rl-color-preview bb-rl-color-${optionValue}`} onClick={() => handleFieldChange(fieldKey, optionValue)}>
					                        {iconClass && <i className={iconClass}></i>}
					                        <span className="bb-rl-color-details">
		                                        <span className="bb-rl-color-label">{optionLabel}</span>
						                        {optionDescription && (
							                        <span className="bb-rl-color-description">{optionDescription}</span>
						                        )}
											</span>
					                        <div className="bb-rl-custom-radio-input">
						                        <input type="radio" name={fieldKey} value={optionValue} checked={value === optionValue} />
						                        <span className="bb-rl-custom-radio-icon"></span>
					                        </div>
				                        </label>
			                        </div>
			                        );
		                        })}
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

            case 'draggable':
                // Helper function to handle draggable item changes
                const handleDraggableItemChange = (itemId, newValue) => {
                    // Get current value from formData (should be an array of items)
                    const currentItems = formData[fieldKey] || options || [];
                    
                    // Update the specific item
                    const updatedItems = currentItems.map(item => 
                        item.id === itemId ? { ...item, enabled: newValue } : item
                    );
                    
                    // Update formData with the new array
                    handleFieldChange(fieldKey, updatedItems);
                };

                // Get current draggable items from formData or fall back to options
                const currentDraggableItems = formData[fieldKey] || options || [];

                return (
                    <DragDropContext onDragEnd={(result) => handleDragEndForField(result, fieldKey)}>
                        <Droppable droppableId={fieldKey}>
                            {(provided) => (
                                <div {...provided.droppableProps} ref={provided.innerRef}>
                                    {currentDraggableItems.map((item, index) => (
                                        <Draggable key={item.id} draggableId={item.id} index={index}>
                                            {(providedDraggable, snapshot) => (
                                                <div
                                                    ref={providedDraggable.innerRef}
                                                    {...providedDraggable.draggableProps}
                                                    {...providedDraggable.dragHandleProps}
                                                    className={`bb-rl-draggable-item ${snapshot.isDragging ? 'is-dragging' : ''}`}
                                                >
                                                    <i className="bb-icons-rl-list" />
                                                    <ToggleControl
                                                        label={<><span className={`menu-icon bb-icons-rl-${item.icon}`}></span> {item.label}</>}
                                                        checked={Boolean(item.enabled)}
                                                        onChange={(newValue) => handleDraggableItemChange(item.id, newValue)}
                                                    />
                                                </div>
                                            )}
                                        </Draggable>
                                    ))}
                                    {provided.placeholder}
                                </div>
                            )}
                        </Droppable>
                    </DragDropContext>
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

    // Handle drag end for specific field
    const handleDragEndForField = (result, fieldKey) => {
        if (!result.destination) return;
        
        // Get current items from formData or options
        const currentItems = formData[fieldKey] || stepOptions[fieldKey]?.options || [];
        const items = Array.from(currentItems);
        
        // Reorder items
        const [reorderedItem] = items.splice(result.source.index, 1);
        items.splice(result.destination.index, 0, reorderedItem);
        
        // Update formData with reordered items
        handleFieldChange(fieldKey, items);
    };

    // Ensure handleDragEnd is defined for backward compatibility
    const handleDragEnd = (result) => {
        if (!result.destination) return;
        // This is a fallback - ideally we should know which field we're dealing with
        console.warn('handleDragEnd called without field context');
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

    // Evaluate a single condition
    const evaluateCondition = (condition, fieldKey, isDebugMode) => {
        const { dependsOn, value: expectedValue, operator = '===' } = condition;
        
        // First check current step's formData, then check all step data
        let actualValue = formData[dependsOn];
        
        // If not found in current step data, search in all step data
        if (actualValue === undefined || actualValue === null) {
            // Search through all step data for the field
            Object.values(allStepData).forEach(stepData => {
                if (stepData && stepData[dependsOn] !== undefined) {
                    actualValue = stepData[dependsOn];
                }
            });
            
            // Also check global preferences from window
            if ((actualValue === undefined || actualValue === null) && window.bbRlOnboarding?.preferences) {
                Object.values(window.bbRlOnboarding.preferences).forEach(stepPrefs => {
                    if (stepPrefs && stepPrefs[dependsOn] !== undefined) {
                        actualValue = stepPrefs[dependsOn];
                    }
                });
            }
        }
        
        let result;
        switch (operator) {
            case '===':
                result = actualValue === expectedValue;
                break;
            case '!==':
                result = actualValue !== expectedValue;
                break;
            case '==':
                // Loose equality for backwards compatibility
                result = actualValue == expectedValue;
                break;
            case '!=':
                // Loose inequality for backwards compatibility
                result = actualValue != expectedValue;
                break;
            case 'in':
                result = Array.isArray(expectedValue) && expectedValue.includes(actualValue);
                break;
            case 'not_in':
                result = Array.isArray(expectedValue) && !expectedValue.includes(actualValue);
                break;
            case 'empty':
                result = !actualValue || actualValue === '' || actualValue === null || actualValue === undefined;
                break;
            case 'not_empty':
                result = actualValue && actualValue !== '' && actualValue !== null && actualValue !== undefined;
                break;
            case 'truthy':
                result = !!actualValue;
                break;
            case 'falsy':
                result = !actualValue;
                break;
            default:
                if (isDebugMode) {
                    console.warn(`Unknown conditional operator '${operator}' for field '${fieldKey}'. Defaulting to true.`);
                }
                result = true;
        }

        // Debug logging for individual condition
        if (isDebugMode) {
            console.log(`Condition evaluation for '${fieldKey}':`, {
                dependsOn,
                actualValue,
                operator,
                expectedValue,
                result,
                searchedInAllStepData: actualValue !== formData[dependsOn],
                formDataValue: formData[dependsOn],
                allStepDataKeys: Object.keys(allStepData)
            });
        }

        return result;
    };

    // Check if a field should be rendered based on conditional logic
    const shouldRenderField = (fieldKey, fieldConfig) => {
        // If no conditional logic is defined, always render
        if (!fieldConfig.conditional) {
            return true;
        }

        const isDebugMode = window.bbRlOnboarding?.debug || false;
        const conditional = fieldConfig.conditional;

        // Handle array of conditions (AND logic by default)
        if (Array.isArray(conditional)) {
            const logic = conditional.logic || 'AND'; // Support AND/OR logic
            
            if (logic === 'OR') {
                // OR logic: if ANY condition is true, show the field
                const result = conditional.some(condition => evaluateCondition(condition, fieldKey, isDebugMode));
                
                if (isDebugMode) {
                    console.log(`Multiple conditions (OR) for '${fieldKey}':`, { result, conditions: conditional });
                }
                
                return result;
            } else {
                // AND logic: ALL conditions must be true to show the field
                const result = conditional.every(condition => evaluateCondition(condition, fieldKey, isDebugMode));
                
                if (isDebugMode) {
                    console.log(`Multiple conditions (AND) for '${fieldKey}':`, { result, conditions: conditional });
                }
                
                return result;
            }
        }

        // Handle single condition (backwards compatibility)
        return evaluateCondition(conditional, fieldKey, isDebugMode);
    };

    // Get dynamic label based on form context
    const getDynamicLabel = (fieldKey, fieldConfig) => {
        if (fieldConfig.dynamicLabel && typeof fieldConfig.dynamicLabel === 'function') {
            return fieldConfig.dynamicLabel(formData);
        }
        return fieldConfig.label;
    };

    // Get dynamic description based on form context
    const getDynamicDescription = (fieldKey, fieldConfig) => {
        if (fieldConfig.dynamicDescription && typeof fieldConfig.dynamicDescription === 'function') {
            return fieldConfig.dynamicDescription(formData);
        }
        return fieldConfig.description;
    };

    // Component for color picker with popover
	const ColorPickerButton = ({ color, onChange }) => {
		const [isPickerOpen, setIsPickerOpen] = useState(false);
		const [tempColor, setTempColor] = useState(color);

		const togglePicker = () => {
			setIsPickerOpen(!isPickerOpen);
			setTempColor(color); // Reset temp color when opening
		};

		const closePicker = () => setIsPickerOpen(false);

		const applyColor = () => {
			onChange(tempColor);
			closePicker();
		};

		// Ensure we have a valid color value
		const colorValue = color || '#3E34FF'; // Default to blue if no color is set

		return (
			<div className="color-picker-button-component bb-rl-color-picker-button-component">
				<div className="color-picker-button-wrapper">
					<Button
						className="color-picker-button"
						onClick={togglePicker}
						aria-expanded={isPickerOpen}
						aria-label={__('Select color', 'buddyboss')}
					>
						<div className="color-indicator-wrapper">
							<ColorIndicator colorValue={colorValue} />
						</div>
						<span className="color-picker-value">{colorValue}</span>
					</Button>
					{isPickerOpen && (
						<Popover
							className="color-picker-popover"
							onClose={closePicker}
							position="bottom center"
						>
							<div className="color-picker-popover-content">
								<ColorPicker
									color={tempColor || colorValue}
									onChange={(newColor) => {
										setTempColor(newColor);
										// Don't call onChange here to keep the popover open
									}}
									enableAlpha={false}
									copyFormat="hex"
								/>
								<div className="color-picker-popover-footer">
									<Button
										onClick={applyColor}
										className="apply-color-button"
									>
										{__('Apply', 'buddyboss')}
									</Button>
								</div>
							</div>
						</Popover>
					)}
				</div>
			</div>
		);
	};

    return (
        <div className="bb-rl-dynamic-step-renderer">
            <div className="bb-rl-form-fields">
                {Object.entries(stepOptions).map(([fieldKey, fieldConfig]) => {
                    // Check conditional rendering
                    if (!shouldRenderField(fieldKey, fieldConfig)) {
                        return null;
                    }

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
