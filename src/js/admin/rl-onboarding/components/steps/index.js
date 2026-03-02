// Step Components Index
// Export all step components for easier importing

export { SplashScreen } from './SplashScreen';
export { CommunitySetupStep } from './CommunitySetupStep';
export { SiteAppearanceStep } from './SiteAppearanceStep';
export { BrandingsStep } from './BrandingsStep';
export { PagesStep } from './PagesStep';
export { SideMenusStep } from './SideMenusStep';
export { WidgetsStep } from './WidgetsStep';
export { FinishScreen } from './FinishScreen';

// Base Layout Component
export { BaseStepLayout } from '../BaseStepLayout';

// Step Registry
export { 
    getStepComponent, 
    hasStepComponent, 
    getAvailableStepComponents, 
    registerStepComponent, 
    unregisterStepComponent, 
    stepComponents 
} from '../StepRegistry'; 