// Export all components for easy importing
export { OnboardingModal } from './OnboardingModal';
export { BaseStepLayout } from './BaseStepLayout';
export { DynamicStepRenderer } from './DynamicStepRenderer';

// Step Components
export { SplashScreen } from './steps/SplashScreen';
export { CommunitySetupStep } from './steps/CommunitySetupStep';
export { SiteAppearanceStep } from './steps/SiteAppearanceStep';
export { BrandingsStep } from './steps/BrandingsStep';
export { PagesStep } from './steps/PagesStep';
export { SideMenusStep } from './steps/SideMenusStep';
export { WidgetsStep } from './steps/WidgetsStep';
export { FinishScreen } from './steps/FinishScreen';

// Step Registry Functions
export { 
    registerStepComponent, 
    getStepComponent, 
    hasStepComponent, 
    getAllStepComponents 
} from './StepRegistry'; 