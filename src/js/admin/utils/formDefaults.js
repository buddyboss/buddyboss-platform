/**
 * Utility functions for extracting default values from form field configurations
 */

/**
 * Extracts default values from step options configuration
 * @param {Object} stepOptions - The step options configuration object
 * @param {Object} savedData - Previously saved data to merge with defaults
 * @returns {Object} Object containing default values for all fields
 */
export const extractFormDefaults = (stepOptions = {}, savedData = {}) => {
    const defaults = {};
    
    Object.entries(stepOptions).forEach(([fieldKey, fieldConfig]) => {
        // Skip non-interactive fields
        if (['description', 'hr'].includes(fieldConfig.type)) {
            return;
        }
        
        // checkbox_group needs special handling – the default
        // selected list is derived from the options where default === true.
        if (fieldConfig.type === 'checkbox_group') {
            if (Array.isArray(fieldConfig.value)) {
                defaults[fieldKey] = fieldConfig.value;
            } else {
                const selected = Object.entries(fieldConfig.options || {})
                    .filter(([, optCfg]) => optCfg && optCfg.default)
                    .map(([optKey]) => optKey);
                defaults[fieldKey] = selected;
            }
            return;
        }
        
        // draggable and draggable_links need special handling
        // For these field types, the options array is the default value
        if (fieldConfig.type === 'draggable' || fieldConfig.type === 'draggable_links') {
            if (Array.isArray(fieldConfig.options)) {
                defaults[fieldKey] = fieldConfig.options;
            } else if (fieldConfig.value !== undefined) {
                defaults[fieldKey] = fieldConfig.value;
            } else if (fieldConfig.default !== undefined) {
                defaults[fieldKey] = fieldConfig.default;
            } else {
                defaults[fieldKey] = [];
            }
            return;
        }
        
        // Generic handling for other field types
        if (fieldConfig.value !== undefined) {
            defaults[fieldKey] = fieldConfig.value;
        } else if (fieldConfig.default !== undefined) {
            defaults[fieldKey] = fieldConfig.default;
        }
    });
    
    // If savedData is provided, merge it with defaults
    // savedData takes precedence over defaults
    if (savedData && Object.keys(savedData).length > 0) {
        // For CommunitySetupStep compatibility: only use savedData for fields with actual values
        const validSavedData = {};
        Object.entries(savedData).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                validSavedData[key] = value;
            }
        });
        
        return { ...defaults, ...validSavedData };
    }
    
    return defaults;
};

/**
 * Helper function to get initial form data for step components
 * This is a convenience wrapper that step components can use
 * @param {Object} stepOptions - The step options configuration object
 * @param {Object} savedData - Previously saved data to merge with defaults
 * @returns {Object} Initial form data with defaults and saved data merged
 */
export const getInitialFormData = (stepOptions = {}, savedData = {}) => {
    return extractFormDefaults(stepOptions, savedData);
};