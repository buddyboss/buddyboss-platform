import { SplashScreen } from './steps/SplashScreen';
import { CommunitySetupStep } from './steps/CommunitySetupStep';
import { SiteAppearanceStep } from './steps/SiteAppearanceStep';
import { BrandingsStep } from './steps/BrandingsStep';
import { PagesStep } from './steps/PagesStep';
import { SideMenusStep } from './steps/SideMenusStep';
import { WidgetsStep } from './steps/WidgetsStep';
import { FinishScreen } from './steps/FinishScreen';

// Component registry mapping step component names to actual components
const stepComponents = {
    'SplashScreen': SplashScreen,
    'CommunitySetupStep': CommunitySetupStep,
    'SiteAppearanceStep': SiteAppearanceStep,
    'BrandingsStep': BrandingsStep,
    'PagesStep': PagesStep,
    'SideMenusStep': SideMenusStep,
    'WidgetsStep': WidgetsStep,
    'FinishScreen': FinishScreen,
};

/**
 * Get the component for a given step
 * @param {string} componentName - The name of the component from stepData.component
 * @returns {React.Component|null} - The component or null if not found
 */
export const getStepComponent = (componentName) => {
    if (!componentName || typeof componentName !== 'string') {
        console.warn('Invalid component name provided to getStepComponent:', componentName);
        return null;
    }

    const component = stepComponents[componentName];
    
    if (!component) {
        console.warn(`Component '${componentName}' not found in step registry. Available components:`, Object.keys(stepComponents));
        return null;
    }

    return component;
};

/**
 * Check if a component exists in the registry
 * @param {string} componentName - The name of the component to check
 * @returns {boolean} - True if component exists, false otherwise
 */
export const hasStepComponent = (componentName) => {
    return componentName && typeof componentName === 'string' && stepComponents.hasOwnProperty(componentName);
};

/**
 * Get all available step component names
 * @returns {string[]} - Array of component names
 */
export const getAvailableStepComponents = () => {
    return Object.keys(stepComponents);
};

/**
 * Register a new step component dynamically
 * @param {string} componentName - The name to register the component under
 * @param {React.Component} component - The component to register
 */
export const registerStepComponent = (componentName, component) => {
    if (!componentName || typeof componentName !== 'string') {
        console.error('Invalid component name provided to registerStepComponent:', componentName);
        return false;
    }

    if (!component) {
        console.error('Invalid component provided to registerStepComponent:', component);
        return false;
    }

    stepComponents[componentName] = component;
    return true;
};

/**
 * Unregister a step component
 * @param {string} componentName - The name of the component to unregister
 */
export const unregisterStepComponent = (componentName) => {
    if (stepComponents.hasOwnProperty(componentName)) {
        delete stepComponents[componentName];
        return true;
    }
    return false;
};

// Export the registry for potential external access
export { stepComponents }; 