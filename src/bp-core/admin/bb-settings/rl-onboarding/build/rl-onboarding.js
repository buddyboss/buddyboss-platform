/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js":
/*!*****************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/BaseStepLayout.js ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BaseStepLayout: () => (/* binding */ BaseStepLayout)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);




const BaseStepLayout = ({
  stepData,
  children,
  onNext,
  onPrevious,
  onSkip,
  isFirstStep = false,
  isLastStep = false,
  currentStep = 0,
  totalSteps = 0,
  rightPanelContent = null
}) => {
  const {
    title,
    description,
    image
  } = stepData;

  // Get the image URL from the assets
  const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl ? `${window.bbRlOnboarding.assets.assetsUrl}${image}` : '';
  const progressPercentage = totalSteps > 0 ? (currentStep + 1) / totalSteps * 100 : 0;
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-layout"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-progress"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-progress-bar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-progress-fill",
    style: {
      width: `${progressPercentage}%`
    }
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-progress-text"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Step', 'buddyboss'), " ", currentStep + 1, " ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('of', 'buddyboss'), " ", totalSteps)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-skip-button",
    onClick: onSkip
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Skip for now', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-left-panel"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-info"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "bb-rl-step-title"
  }, title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "bb-rl-step-description"
  }, description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-options"
  }, children), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-navigation"
  }, !isFirstStep && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-nav-button bb-rl-previous-button",
    onClick: onPrevious
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Previous', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-nav-button bb-rl-next-button",
    onClick: onNext,
    variant: "primary"
  }, isLastStep ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Finish', 'buddyboss') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Next', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-right-panel"
  }, rightPanelContent ? rightPanelContent : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-preview"
  }, imageUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-preview-image"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: imageUrl,
    alt: title
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-preview-placeholder"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Live preview will appear here', 'buddyboss')))))));
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/OnboardingModal.js":
/*!******************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/OnboardingModal.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   OnboardingModal: () => (/* binding */ OnboardingModal)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _StepRegistry__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./StepRegistry */ "./src/js/admin/rl-onboarding/components/StepRegistry.js");





const OnboardingModal = ({
  isOpen,
  onClose,
  onContinue,
  onSkip,
  onSaveStep
}) => {
  const [currentStepIndex, setCurrentStepIndex] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(0);
  const [stepData, setStepData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});
  const [isProcessing, setIsProcessing] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Get steps from window.bbRlOnboarding
  const steps = window.bbRlOnboarding?.steps || [];
  const totalSteps = steps.length;
  const currentStep = steps[currentStepIndex] || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Initialize step data from saved preferences
    const savedData = {};
    if (window.bbRlOnboarding?.preferences) {
      Object.keys(window.bbRlOnboarding.preferences).forEach(key => {
        savedData[key] = window.bbRlOnboarding.preferences[key];
      });
    }
    setStepData(savedData);
  }, []);

  // Enable/disable fullscreen mode based on current step
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (isOpen && currentStep.component && currentStep.component !== 'SplashScreen') {
      enableFullscreenMode();
    } else {
      disableFullscreenMode();
    }

    // Cleanup on unmount
    return () => {
      disableFullscreenMode();
    };
  }, [isOpen, currentStep.component]);
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
  const handleNext = async (formData = {}) => {
    setIsProcessing(true);
    try {
      // Save current step data if provided
      if (formData && currentStep.key) {
        setStepData(prev => ({
          ...prev,
          [currentStep.key]: formData
        }));

        // Save step data via callback
        if (onSaveStep) {
          await onSaveStep({
            step: currentStep.key,
            data: formData,
            timestamp: new Date().toISOString()
          });
        }
      }

      // Check if this is the last step
      if (currentStepIndex >= totalSteps - 1) {
        // This is the finish step
        handleComplete();
      } else {
        // Move to next step
        setCurrentStepIndex(prev => Math.min(prev + 1, totalSteps - 1));
      }
    } catch (error) {
      console.error('Error proceeding to next step:', error);
    } finally {
      setIsProcessing(false);
    }
  };
  const handlePrevious = () => {
    setCurrentStepIndex(prev => Math.max(prev - 1, 0));
  };
  const handleSkip = async () => {
    setIsProcessing(true);
    try {
      // Skip the entire onboarding
      if (onSkip) {
        await onSkip();
      }
    } catch (error) {
      console.error('Error skipping onboarding:', error);
    } finally {
      setIsProcessing(false);
    }
  };
  const handleComplete = async () => {
    setIsProcessing(true);
    try {
      // Complete the onboarding with all collected data
      if (onContinue) {
        await onContinue(stepData);
      }

      // Trigger completion event
      const event = new CustomEvent('bb_rl_onboarding_completed', {
        detail: {
          stepData: stepData,
          completedAt: new Date().toISOString()
        }
      });
      document.dispatchEvent(event);

      // Close the modal
      if (onClose) {
        onClose();
      }
    } catch (error) {
      console.error('Error completing onboarding:', error);
    } finally {
      setIsProcessing(false);
    }
  };
  const handleStepSkip = () => {
    // Skip current step and move to next
    if (currentStepIndex < totalSteps - 1) {
      setCurrentStepIndex(prev => prev + 1);
    } else {
      handleComplete();
    }
  };
  const handleClose = () => {
    disableFullscreenMode();
    if (onClose) {
      onClose();
    }
  };
  const renderCurrentStep = () => {
    if (!currentStep || !currentStep.component) {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "bb-rl-error-state"
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Step Not Found', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('The current step could not be loaded. Please try refreshing the page.', 'buddyboss')));
    }

    // Check if component exists in registry
    if (!(0,_StepRegistry__WEBPACK_IMPORTED_MODULE_4__.hasStepComponent)(currentStep.component)) {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "bb-rl-error-state"
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Component Not Found', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Component', 'buddyboss'), " \"", currentStep.component, "\" ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('not found in registry.', 'buddyboss')));
    }

    // Get the component from registry
    const StepComponent = (0,_StepRegistry__WEBPACK_IMPORTED_MODULE_4__.getStepComponent)(currentStep.component);
    if (!StepComponent) {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "bb-rl-error-state"
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h2", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Component Load Error', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Failed to load the step component.', 'buddyboss')));
    }

    // Handle special case for SplashScreen (doesn't use BaseStepLayout)
    if (currentStep.component === 'SplashScreen') {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(StepComponent, {
        stepData: currentStep,
        onNext: handleNext,
        onSkip: handleSkip
      });
    }

    // Handle FinishScreen (custom fullscreen layout)
    if (currentStep.component === 'FinishScreen') {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(StepComponent, {
        stepData: currentStep,
        onFinish: handleComplete,
        onViewSite: () => {
          window.open(window.bbRlOnboarding?.readylaunch?.site_url || window.location.origin, '_blank');
        }
      });
    }

    // For all other steps that use BaseStepLayout
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(StepComponent, {
      stepData: currentStep,
      onNext: handleNext,
      onPrevious: handlePrevious,
      onSkip: handleStepSkip,
      currentStep: currentStepIndex,
      totalSteps: totalSteps,
      onSaveStep: onSaveStep,
      isProcessing: isProcessing
    });
  };
  if (!isOpen) {
    return null;
  }

  // Special handling for splash screen only (modal popup)
  if (currentStep.component === 'SplashScreen') {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-onboarding-overlay"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-onboarding-modal bb-rl-special-step"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-modal-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-logo"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      src: window.bbRlOnboarding?.assets?.logo || '',
      alt: "BuddyBoss"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
      className: "bb-rl-close-button",
      onClick: handleClose,
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Close', 'buddyboss'),
      disabled: isProcessing
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "dashicons dashicons-no-alt"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-modal-content"
    }, renderCurrentStep())));
  }

  // Full screen layout for all step-based components (including FinishScreen)
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-onboarding-overlay bb-rl-fullscreen"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-onboarding-modal bb-rl-fullscreen-modal"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-fullscreen-header"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-logo"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: window.bbRlOnboarding?.assets?.logo || '',
    alt: "BuddyBoss"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-header-actions"
  }, currentStep.component !== 'FinishScreen' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-back-to-selection",
    onClick: () => setCurrentStepIndex(0),
    disabled: isProcessing
  }, "\u2190 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Back to Start', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-close-button",
    onClick: handleClose,
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Close', 'buddyboss'),
    disabled: isProcessing
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "dashicons dashicons-no-alt"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-fullscreen-content"
  }, renderCurrentStep())));
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/StepRegistry.js":
/*!***************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/StepRegistry.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getAvailableStepComponents: () => (/* binding */ getAvailableStepComponents),
/* harmony export */   getStepComponent: () => (/* binding */ getStepComponent),
/* harmony export */   hasStepComponent: () => (/* binding */ hasStepComponent),
/* harmony export */   registerStepComponent: () => (/* binding */ registerStepComponent),
/* harmony export */   stepComponents: () => (/* binding */ stepComponents),
/* harmony export */   unregisterStepComponent: () => (/* binding */ unregisterStepComponent)
/* harmony export */ });
/* harmony import */ var _steps_SplashScreen__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./steps/SplashScreen */ "./src/js/admin/rl-onboarding/components/steps/SplashScreen.js");
/* harmony import */ var _steps_CommunitySetupStep__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./steps/CommunitySetupStep */ "./src/js/admin/rl-onboarding/components/steps/CommunitySetupStep.js");
/* harmony import */ var _steps_SiteAppearanceStep__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./steps/SiteAppearanceStep */ "./src/js/admin/rl-onboarding/components/steps/SiteAppearanceStep.js");
/* harmony import */ var _steps_BrandingsStep__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./steps/BrandingsStep */ "./src/js/admin/rl-onboarding/components/steps/BrandingsStep.js");
/* harmony import */ var _steps_PagesStep__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./steps/PagesStep */ "./src/js/admin/rl-onboarding/components/steps/PagesStep.js");
/* harmony import */ var _steps_SideMenusStep__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./steps/SideMenusStep */ "./src/js/admin/rl-onboarding/components/steps/SideMenusStep.js");
/* harmony import */ var _steps_WidgetsStep__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./steps/WidgetsStep */ "./src/js/admin/rl-onboarding/components/steps/WidgetsStep.js");
/* harmony import */ var _steps_FinishScreen__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./steps/FinishScreen */ "./src/js/admin/rl-onboarding/components/steps/FinishScreen.js");









// Component registry mapping step component names to actual components
const stepComponents = {
  'SplashScreen': _steps_SplashScreen__WEBPACK_IMPORTED_MODULE_0__.SplashScreen,
  'CommunitySetupStep': _steps_CommunitySetupStep__WEBPACK_IMPORTED_MODULE_1__.CommunitySetupStep,
  'SiteAppearanceStep': _steps_SiteAppearanceStep__WEBPACK_IMPORTED_MODULE_2__.SiteAppearanceStep,
  'BrandingsStep': _steps_BrandingsStep__WEBPACK_IMPORTED_MODULE_3__.BrandingsStep,
  'PagesStep': _steps_PagesStep__WEBPACK_IMPORTED_MODULE_4__.PagesStep,
  'SideMenusStep': _steps_SideMenusStep__WEBPACK_IMPORTED_MODULE_5__.SideMenusStep,
  'WidgetsStep': _steps_WidgetsStep__WEBPACK_IMPORTED_MODULE_6__.WidgetsStep,
  'FinishScreen': _steps_FinishScreen__WEBPACK_IMPORTED_MODULE_7__.FinishScreen
};

/**
 * Get the component for a given step
 * @param {string} componentName - The name of the component from stepData.component
 * @returns {React.Component|null} - The component or null if not found
 */
const getStepComponent = componentName => {
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
const hasStepComponent = componentName => {
  return componentName && typeof componentName === 'string' && stepComponents.hasOwnProperty(componentName);
};

/**
 * Get all available step component names
 * @returns {string[]} - Array of component names
 */
const getAvailableStepComponents = () => {
  return Object.keys(stepComponents);
};

/**
 * Register a new step component dynamically
 * @param {string} componentName - The name to register the component under
 * @param {React.Component} component - The component to register
 */
const registerStepComponent = (componentName, component) => {
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
const unregisterStepComponent = componentName => {
  if (stepComponents.hasOwnProperty(componentName)) {
    delete stepComponents[componentName];
    return true;
  }
  return false;
};

// Export the registry for potential external access


/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/BrandingsStep.js":
/*!**********************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/BrandingsStep.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   BrandingsStep: () => (/* binding */ BrandingsStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const BrandingsStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    site_logo: '',
    favicon: '',
    brand_colors: '#e57e3a'
  });
  const [uploadingLogo, setUploadingLogo] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [uploadingFavicon, setUploadingFavicon] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.brandings || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.brandings || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
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
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-file-upload"
    }, currentValue ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-file-preview"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      src: currentValue,
      alt: label,
      className: "bb-rl-uploaded-image"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
      className: "bb-rl-remove-file",
      onClick: () => handleInputChange(field, ''),
      isDestructive: true,
      variant: "link"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Remove', 'buddyboss'))) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-upload-area"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("input", {
      type: "file",
      accept: "image/*",
      onChange: e => {
        const file = e.target.files[0];
        if (file) {
          handleFileUpload(field, file);
        }
      },
      className: "bb-rl-file-input",
      id: `bb-rl-${field}`,
      disabled: isUploading
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", {
      htmlFor: `bb-rl-${field}`,
      className: "bb-rl-upload-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-upload-icon"
    }, "\uD83D\uDCC1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-upload-text"
    }, isUploading ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Uploading...', 'buddyboss') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Click to upload', 'buddyboss'))))));
  };
  const renderFormFields = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.site_logo && renderFileUpload('site_logo', stepOptions.site_logo.label, stepOptions.site_logo.description, formData.site_logo, uploadingLogo), stepOptions.favicon && renderFileUpload('favicon', stepOptions.favicon.label, stepOptions.favicon.description, formData.favicon, uploadingFavicon), stepOptions.brand_colors && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.brand_colors.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.brand_colors.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-picker-container"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-preview-box"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-swatch",
      style: {
        backgroundColor: formData.brand_colors
      }
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-color-value"
    }, formData.brand_colors)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ColorPicker, {
      color: formData.brand_colors,
      onChange: color => handleInputChange('brand_colors', color.hex)
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-branding-preview"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Brand Preview', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-brand-preview-container"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-header",
      style: {
        backgroundColor: formData.brand_colors
      }
    }, formData.site_logo ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
      src: formData.site_logo,
      alt: "Logo",
      className: "bb-rl-mock-logo"
    }) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-logo-placeholder"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Your Logo', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-nav"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Home', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Members', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Groups', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-activity"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mock-button",
      style: {
        backgroundColor: formData.brand_colors
      }
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Join Community', 'buddyboss'))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-tips-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Branding Tips', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-tips-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Logo should be at least 200px wide for best quality', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Favicon should be 32x32px or 16x16px', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Choose colors that reflect your brand identity', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Ensure good contrast for accessibility', 'buddyboss'))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: false,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/CommunitySetupStep.js":
/*!***************************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/CommunitySetupStep.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   CommunitySetupStep: () => (/* binding */ CommunitySetupStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const CommunitySetupStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    site_title: '',
    privacy_mode: 'public'
  });
  const [errors, setErrors] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({});

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.community_setup || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.community_setup || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
    }
  }, []);
  const handleInputChange = (field, value) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));

    // Clear error for this field
    if (errors[field]) {
      setErrors(prev => ({
        ...prev,
        [field]: null
      }));
    }
  };
  const validateForm = () => {
    const newErrors = {};

    // Check required fields
    if (stepOptions.site_title?.required && !formData.site_title.trim()) {
      newErrors.site_title = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Community name is required', 'buddyboss');
    }
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };
  const handleNext = async () => {
    if (!validateForm()) {
      return;
    }

    // Save step data
    if (onSaveStep) {
      await onSaveStep({
        step: 'community_setup',
        data: formData,
        timestamp: new Date().toISOString()
      });
    }
    if (onNext) {
      onNext(formData);
    }
  };
  const renderFormFields = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.site_title && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
      label: stepOptions.site_title.label,
      help: stepOptions.site_title.description,
      value: formData.site_title,
      onChange: value => handleInputChange('site_title', value),
      placeholder: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Enter your community name', 'buddyboss'),
      className: errors.site_title ? 'bb-rl-field-error' : ''
    }), errors.site_title && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-error-message"
    }, errors.site_title)), stepOptions.privacy_mode && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.SelectControl, {
      label: stepOptions.privacy_mode.label,
      help: stepOptions.privacy_mode.description,
      value: formData.privacy_mode,
      onChange: value => handleInputChange('privacy_mode', value),
      options: Object.entries(stepOptions.privacy_mode.options || {}).map(([value, label]) => ({
        value,
        label
      }))
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-info-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('What you\'ll get:', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-feature-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-check-icon"
    }, "\u2713"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Member profiles and directories', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-check-icon"
    }, "\u2713"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Activity feeds and social interactions', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-check-icon"
    }, "\u2713"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Private messaging system', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-check-icon"
    }, "\u2713"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Groups and forums', 'buddyboss'))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: currentStep === 0,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/FinishScreen.js":
/*!*********************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/FinishScreen.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   FinishScreen: () => (/* binding */ FinishScreen)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);




const FinishScreen = ({
  stepData,
  onFinish,
  onViewSite
}) => {
  const {
    title,
    description,
    image
  } = stepData;
  const [isFinishing, setIsFinishing] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);

  // Get the image URL from the assets
  const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl ? `${window.bbRlOnboarding.assets.assetsUrl}${image}` : '';
  const handleFinish = async () => {
    setIsFinishing(true);
    try {
      if (onFinish) {
        await onFinish();
      }
    } catch (error) {
      console.error('Error finishing onboarding:', error);
    } finally {
      setIsFinishing(false);
    }
  };
  const handleViewSite = () => {
    if (onViewSite) {
      onViewSite();
    } else {
      // Default to homepage
      window.open(window.bbRlOnboarding?.readylaunch?.site_url || window.location.origin, '_blank');
    }
  };
  const handleGoToAdmin = () => {
    // Navigate to BuddyBoss settings
    window.location.href = window.bbRlOnboarding?.readylaunch?.admin_url + 'admin.php?page=buddyboss-platform';
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-screen bb-rl-fullscreen-finish"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-left"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-success-animation"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-checkmark"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    className: "bb-rl-checkmark-circle",
    viewBox: "0 0 52 52"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("circle", {
    className: "bb-rl-checkmark-circle-path",
    cx: "26",
    cy: "26",
    r: "25",
    fill: "none"
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("svg", {
    className: "bb-rl-checkmark-check",
    viewBox: "0 0 52 52"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("path", {
    className: "bb-rl-checkmark-check-path",
    fill: "none",
    d: "M14.1 27.2l7.1 7.2 16.7-16.8"
  })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-text"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "bb-rl-finish-title"
  }, title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "bb-rl-finish-description"
  }, description), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "bb-rl-finish-subtitle"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Your community is now configured and ready for members to join!', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-setup-summary"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('What we\'ve set up for you:', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-grid"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83C\uDFE0"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Community structure', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83C\uDFA8"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Site appearance', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83C\uDFF7\uFE0F"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Branding elements', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83D\uDCC4"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Essential pages', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83E\uDDED"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Navigation menus', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-summary-icon"
  }, "\uD83D\uDD27"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Widget areas', 'buddyboss'))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-finish-button bb-rl-primary-action",
    onClick: handleViewSite,
    variant: "primary",
    size: "large"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('View Your Site', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-finish-button bb-rl-secondary-action",
    onClick: handleGoToAdmin,
    variant: "secondary",
    size: "large"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Go to Settings', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-finish-button bb-rl-close-action",
    onClick: handleFinish,
    variant: "link",
    disabled: isFinishing
  }, isFinishing ? (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Finishing...', 'buddyboss') : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Close Setup', 'buddyboss'))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-right"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-sidebar"
  }, imageUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-finish-image"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: imageUrl,
    alt: title
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-next-steps"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('What\'s next?', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-next-steps-list"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-next-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-number"
  }, "1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Invite members', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Start building your community by inviting the first members', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-next-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-number"
  }, "2"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Create content', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Add some initial posts and groups to get conversations started', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-next-step"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-number"
  }, "3"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-step-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Customize further', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Fine-tune your settings and explore advanced features', 'buddyboss')))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-support-resources"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Need help?', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-resource-links"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://www.buddyboss.com/resources/docs/",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "bb-rl-resource-link"
  }, "\uD83D\uDCDA ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Documentation', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://www.buddyboss.com/contact/",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "bb-rl-resource-link"
  }, "\uD83D\uDCAC ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Get Support', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("a", {
    href: "https://www.buddyboss.com/community/",
    target: "_blank",
    rel: "noopener noreferrer",
    className: "bb-rl-resource-link"
  }, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Join Community', 'buddyboss')))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-thank-you"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Thank you for choosing BuddyBoss! We\'re excited to see what you build with your new community.', 'buddyboss')))))));
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/PagesStep.js":
/*!******************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/PagesStep.js ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   PagesStep: () => (/* binding */ PagesStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const PagesStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    create_essential_pages: true,
    homepage_layout: 'activity'
  });

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.pages || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.pages || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
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
        step: 'pages',
        data: formData,
        timestamp: new Date().toISOString()
      });
    }
    if (onNext) {
      onNext(formData);
    }
  };
  const renderFormFields = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.create_essential_pages && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CheckboxControl, {
      label: stepOptions.create_essential_pages.label,
      help: stepOptions.create_essential_pages.description,
      checked: formData.create_essential_pages,
      onChange: value => handleInputChange('create_essential_pages', value)
    }), formData.create_essential_pages && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-pages-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-pages-info"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('The following pages will be created:', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-essential-pages"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Privacy Policy', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Terms of Service', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('About Us', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Contact', 'buddyboss'))))), stepOptions.homepage_layout && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.homepage_layout.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.homepage_layout.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-homepage-options"
    }, Object.entries(stepOptions.homepage_layout.options || {}).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: value,
      className: `bb-rl-homepage-option ${formData.homepage_layout === value ? 'bb-rl-selected' : ''}`,
      onClick: () => handleInputChange('homepage_layout', value)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-homepage-preview bb-rl-${value}`
    }, value === 'activity' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-item"
    })), value === 'custom' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-custom-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-custom-block"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-custom-block"
    })), value === 'landing' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-hero-section"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-features-section"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-homepage-label"
    }, label), formData.homepage_layout === value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-selected-icon"
    }, "\u2713"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-structure"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Your Site Structure', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-site-tree"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node bb-rl-homepage"
    }, "\uD83C\uDFE0 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Homepage', 'buddyboss'), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-layout-type"
    }, "(", stepOptions.homepage_layout?.options?.[formData.homepage_layout] || formData.homepage_layout, ")")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-children"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Members', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Groups', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCAC ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Forums', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCF1 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Activity', 'buddyboss')), formData.create_essential_pages && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Privacy Policy', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Terms of Service', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('About Us', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-page-node"
    }, "\uD83D\uDCC4 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Contact', 'buddyboss'))))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-tips-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Page Setup Tips', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-tips-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Essential pages help with legal compliance and user trust', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Activity feed homepage encourages community engagement', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Landing page is great for marketing and conversions', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('You can always change these settings later', 'buddyboss'))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: false,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/SideMenusStep.js":
/*!**********************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/SideMenusStep.js ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SideMenusStep: () => (/* binding */ SideMenusStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const SideMenusStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    enable_primary_menu: true,
    enable_member_menu: true,
    menu_style: 'horizontal'
  });

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.side_menus || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.side_menus || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
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
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.enable_primary_menu && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CheckboxControl, {
      label: stepOptions.enable_primary_menu.label,
      help: stepOptions.enable_primary_menu.description,
      checked: formData.enable_primary_menu,
      onChange: value => handleInputChange('enable_primary_menu', value)
    })), stepOptions.enable_member_menu && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CheckboxControl, {
      label: stepOptions.enable_member_menu.label,
      help: stepOptions.enable_member_menu.description,
      checked: formData.enable_member_menu,
      onChange: value => handleInputChange('enable_member_menu', value)
    })), stepOptions.menu_style && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.menu_style.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.menu_style.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-style-options"
    }, Object.entries(stepOptions.menu_style.options || {}).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: value,
      className: `bb-rl-menu-option ${formData.menu_style === value ? 'bb-rl-selected' : ''}`,
      onClick: () => handleInputChange('menu_style', value)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-menu-preview bb-rl-${value}`
    }, value === 'horizontal' ? (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-horizontal-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-logo-area"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-items"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-content-area"
    })) : (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-vertical-layout"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-sidebar-nav"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-nav-item"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-main-content"
    })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-menu-label"
    }, label), formData.menu_style === value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-selected-icon"
    }, "\u2713"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-structure"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Your Navigation Structure', 'buddyboss')), formData.enable_primary_menu && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, "\uD83E\uDDED ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Primary Navigation', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-items"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83C\uDFE0 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Home', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Members', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Groups', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDCAC ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Forums', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDCF1 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Activity', 'buddyboss')))), formData.enable_member_menu && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, "\uD83D\uDC64 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Member Menu', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-items"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDCCA ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Dashboard', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDC64 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Profile', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\u2699\uFE0F ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Settings', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDCE7 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Messages', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-menu-item"
    }, "\uD83D\uDEAA ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Logout', 'buddyboss')))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-features-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Navigation Features', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-grid"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-card"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-icon"
    }, "\uD83D\uDCF1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Mobile Responsive', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Automatically adapts to mobile devices', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-card"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-icon"
    }, "\uD83C\uDFA8"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Customizable', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Easy to customize colors and styles', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-card"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-feature-icon"
    }, "\u26A1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Fast Loading', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Optimized for speed and performance', 'buddyboss')))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-tips-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Navigation Tips', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-tips-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Primary navigation helps users find main content areas', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Member menu provides quick access to personal features', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Horizontal menus work well for desktop users', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Vertical sidebars save space and look modern', 'buddyboss'))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: false,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/SiteAppearanceStep.js":
/*!***************************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/SiteAppearanceStep.js ***!
  \***************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SiteAppearanceStep: () => (/* binding */ SiteAppearanceStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const SiteAppearanceStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    color_scheme: 'default',
    site_layout: 'fullwidth'
  });

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.site_appearance || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.site_appearance || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
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
        step: 'site_appearance',
        data: formData,
        timestamp: new Date().toISOString()
      });
    }
    if (onNext) {
      onNext(formData);
    }
  };
  const renderFormFields = () => {
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.color_scheme && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.color_scheme.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.color_scheme.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-scheme-options"
    }, Object.entries(stepOptions.color_scheme.options || {}).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: value,
      className: `bb-rl-color-option ${formData.color_scheme === value ? 'bb-rl-selected' : ''}`,
      onClick: () => handleInputChange('color_scheme', value)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-color-preview bb-rl-color-${value}`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-swatch bb-rl-primary"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-swatch bb-rl-secondary"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-color-swatch bb-rl-accent"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-color-label"
    }, label), formData.color_scheme === value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-selected-icon"
    }, "\u2713"))))), stepOptions.site_layout && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.site_layout.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.site_layout.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-options"
    }, Object.entries(stepOptions.site_layout.options || {}).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: value,
      className: `bb-rl-layout-option ${formData.site_layout === value ? 'bb-rl-selected' : ''}`,
      onClick: () => handleInputChange('site_layout', value)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-layout-preview bb-rl-layout-${value}`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-header"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-main"
    }), value === 'boxed' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-sidebar"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-layout-label"
    }, label), formData.site_layout === value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-selected-icon"
    }, "\u2713"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-appearance-preview"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Preview', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-container"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-site-preview bb-rl-${formData.color_scheme} bb-rl-${formData.site_layout}`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-logo"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-nav"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-sidebar"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-widget"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-widget"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-main"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-activity"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-activity"
    }))))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: false,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/SplashScreen.js":
/*!*********************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/SplashScreen.js ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SplashScreen: () => (/* binding */ SplashScreen)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);




const SplashScreen = ({
  stepData,
  onNext,
  onSkip
}) => {
  const {
    title,
    description,
    image
  } = stepData;

  // Get the image URL from the assets
  const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl ? `${window.bbRlOnboarding.assets.assetsUrl}${image}` : '';
  const handleGetStarted = () => {
    if (onNext) {
      onNext();
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-screen"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-content"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-logo"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: window.bbRlOnboarding?.assets?.logo || '',
    alt: "BuddyBoss",
    className: "bb-rl-logo-image"
  })), imageUrl && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-image"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("img", {
    src: imageUrl,
    alt: title
  })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-text"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h1", {
    className: "bb-rl-splash-title"
  }, title), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "bb-rl-splash-description"
  }, description), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
    className: "bb-rl-splash-subtitle"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Let\'s set up your community in just a few simple steps.', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-actions"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-splash-button bb-rl-get-started-button",
    onClick: handleGetStarted,
    variant: "primary",
    size: "large"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Let\'s Get Started!', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Button, {
    className: "bb-rl-splash-button bb-rl-skip-splash-button",
    onClick: onSkip,
    variant: "link"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Skip setup for now', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-splash-features"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-feature-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-icon"
  }, "\u26A1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-text"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Quick 5-minute setup', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-feature-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-icon"
  }, "\uD83C\uDFA8"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-text"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Customize your community', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-rl-feature-item"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-icon"
  }, "\uD83D\uDE80"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: "bb-rl-feature-text"
  }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Launch with confidence', 'buddyboss'))))));
};

/***/ }),

/***/ "./src/js/admin/rl-onboarding/components/steps/WidgetsStep.js":
/*!********************************************************************!*\
  !*** ./src/js/admin/rl-onboarding/components/steps/WidgetsStep.js ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   WidgetsStep: () => (/* binding */ WidgetsStep)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../BaseStepLayout */ "./src/js/admin/rl-onboarding/components/BaseStepLayout.js");





const WidgetsStep = ({
  stepData,
  onNext,
  onPrevious,
  onSkip,
  currentStep,
  totalSteps,
  onSaveStep
}) => {
  const [formData, setFormData] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    enable_sidebar_widgets: true,
    default_widgets: true,
    widget_areas: 'all'
  });

  // Get step options from window.bbRlOnboarding
  const stepOptions = window.bbRlOnboarding?.stepOptions?.widgets || {};
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Load any saved data for this step
    const savedData = window.bbRlOnboarding?.preferences?.widgets || {};
    if (Object.keys(savedData).length > 0) {
      setFormData(prev => ({
        ...prev,
        ...savedData
      }));
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
    return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-form-fields"
    }, stepOptions.enable_sidebar_widgets && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CheckboxControl, {
      label: stepOptions.enable_sidebar_widgets.label,
      help: stepOptions.enable_sidebar_widgets.description,
      checked: formData.enable_sidebar_widgets,
      onChange: value => handleInputChange('enable_sidebar_widgets', value)
    })), stepOptions.default_widgets && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.CheckboxControl, {
      label: stepOptions.default_widgets.label,
      help: stepOptions.default_widgets.description,
      checked: formData.default_widgets,
      onChange: value => handleInputChange('default_widgets', value)
    }), formData.default_widgets && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-default-widgets-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-widgets-info"
    }, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('The following widgets will be added:', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-widget-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83C\uDFC3 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Recent Activity', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Member List', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDC65 ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Active Groups', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCAC ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Recent Forum Topics', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, "\uD83D\uDCDD ", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Recent Posts', 'buddyboss'))))), stepOptions.widget_areas && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-label"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, stepOptions.widget_areas.label), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
      className: "bb-rl-field-description"
    }, stepOptions.widget_areas.description)), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-area-options"
    }, Object.entries(stepOptions.widget_areas.options || {}).map(([value, label]) => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      key: value,
      className: `bb-rl-widget-area-option ${formData.widget_areas === value ? 'bb-rl-selected' : ''}`,
      onClick: () => handleInputChange('widget_areas', value)
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: `bb-rl-widget-area-preview bb-rl-${value}`
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-preview-layout"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-content-area"
    }, (value === 'all' || value === 'sidebar') && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-sidebar-area"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-placeholder"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-placeholder"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-main-content-area"
    })), (value === 'all' || value === 'footer') && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-footer-area"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-placeholder"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-placeholder"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-placeholder"
    })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-widget-area-label"
    }, label), formData.widget_areas === value && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
      className: "bb-rl-selected-icon"
    }, "\u2713"))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-layout-preview"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Your Widget Layout', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-layout-mockup"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-header"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-logo"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-nav"
    })), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-content"
    }, formData.enable_sidebar_widgets && (formData.widget_areas === 'all' || formData.widget_areas === 'sidebar') && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-sidebar"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-widget"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Recent Activity', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-items"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-activity-item"
    }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-widget"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Members', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-member-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-member-item"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-member-item"
    })))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-main"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-main-content"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-content-block"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-content-block"
    })))), formData.widget_areas === 'all' || formData.widget_areas === 'footer' && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-mockup-footer"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-footer-widget"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-footer-widget"
    }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-footer-widget"
    }))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-widget-benefits"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Widget Benefits', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-grid"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-item"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-icon"
    }, "\u26A1"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Increased Engagement', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Keep users engaged with relevant content', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-item"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-icon"
    }, "\uD83C\uDFAF"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Better Navigation', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Help users find what they\'re looking for', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-item"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-benefit-icon"
    }, "\uD83D\uDCCA"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h5", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Dynamic Content', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Automatically updated content areas', 'buddyboss')))))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-field-group"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
      className: "bb-rl-tips-section"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h4", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Widget Tips', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", {
      className: "bb-rl-tips-list"
    }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Sidebar widgets are great for secondary navigation and community highlights', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Footer widgets work well for links, social media, and contact information', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Default widgets can be customized or replaced later', 'buddyboss')), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Widget areas help organize content and improve user experience', 'buddyboss'))))));
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_BaseStepLayout__WEBPACK_IMPORTED_MODULE_4__.BaseStepLayout, {
    stepData: stepData,
    onNext: handleNext,
    onPrevious: onPrevious,
    onSkip: onSkip,
    isFirstStep: false,
    isLastStep: currentStep === totalSteps - 1,
    currentStep: currentStep,
    totalSteps: totalSteps
  }, renderFormFields());
};

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!**************************************************!*\
  !*** ./src/js/admin/rl-onboarding/onboarding.js ***!
  \**************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   OnboardingApp: () => (/* binding */ OnboardingApp)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _components_OnboardingModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/OnboardingModal */ "./src/js/admin/rl-onboarding/components/OnboardingModal.js");





// Onboarding App Component
const OnboardingApp = () => {
  const [showModal, setShowModal] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Check if we should show the onboarding modal
    // Since shouldShow is already determined by PHP, we can use it directly
    if (window.bbRlOnboarding && window.bbRlOnboarding.shouldShow) {
      setShowModal(true);
    }
  }, []);
  const checkShouldShowOnboarding = async () => {
    try {
      const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: window.bbRlOnboarding.wizardId + '_should_show',
          nonce: window.bbRlOnboarding?.nonce || ''
        })
      });
      const data = await response.json();
      if (data.success && data.data.shouldShow) {
        setShowModal(true);
      }
    } catch (error) {
      console.error('Error checking onboarding status:', error);
    }
  };
  const handleModalClose = () => {
    setShowModal(false);
  };
  const handleContinue = async selectedOption => {
    // Handle the continue action with the selected option
    console.log('Selected option:', selectedOption);
    try {
      const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: window.bbRlOnboarding.wizardId + '_complete',
          nonce: window.bbRlOnboarding?.nonce || '',
          selectedOption: selectedOption,
          skipped: '0'
        })
      });
      const data = await response.json();
      if (data.success) {
        console.log('Onboarding completed successfully:', data.data);

        // Trigger custom event for extensibility
        const event = new CustomEvent('bb_rl_onboarding_completed', {
          detail: {
            selectedOption: selectedOption,
            data: data.data
          }
        });
        document.dispatchEvent(event);

        // Handle different completion scenarios
        if (selectedOption === 'readylaunch') {
          // Redirect to ReadyLaunch settings page
          window.location.href = window.location.origin + '/wp-admin/admin.php?page=buddyboss-platform&tab=buddyboss_readylaunch';
        } else if (selectedOption === 'buddyboss-theme-buy') {
          // Redirect to BuddyBoss theme purchase page
          window.open('https://www.buddyboss.com/theme/', '_blank');
        } else {
          // Just close the modal for other options
          setShowModal(false);
        }
      } else {
        console.error('Error completing onboarding:', data.data?.message || 'Unknown error');
      }
    } catch (error) {
      console.error('Error completing onboarding:', error);
    }
  };
  const handleSkip = async () => {
    // Handle skipping the onboarding
    try {
      const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: window.bbRlOnboarding.wizardId + '_complete',
          nonce: window.bbRlOnboarding?.nonce || '',
          selectedOption: '',
          skipped: '1'
        })
      });
      const data = await response.json();
      if (data.success) {
        console.log('Onboarding skipped successfully');

        // Trigger custom event for extensibility
        const event = new CustomEvent('bb_rl_onboarding_skipped', {
          detail: {
            data: data.data
          }
        });
        document.dispatchEvent(event);
        setShowModal(false);
      } else {
        console.error('Error skipping onboarding:', data.data?.message || 'Unknown error');
      }
    } catch (error) {
      console.error('Error skipping onboarding:', error);
    }
  };
  const saveStep = async stepData => {
    // Save current step data
    try {
      const response = await fetch(window.bbRlOnboarding?.ajaxUrl || window.ajaxurl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: window.bbRlOnboarding.wizardId + '_save_step',
          nonce: window.bbRlOnboarding?.nonce || '',
          stepData: JSON.stringify(stepData)
        })
      });
      const data = await response.json();
      if (data.success) {
        console.log('Step saved successfully:', data.data);
        return true;
      } else {
        console.error('Error saving step:', data.data?.message || 'Unknown error');
        return false;
      }
    } catch (error) {
      console.error('Error saving step:', error);
      return false;
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_OnboardingModal__WEBPACK_IMPORTED_MODULE_3__.OnboardingModal, {
    isOpen: showModal,
    onClose: handleModalClose,
    onContinue: handleContinue,
    onSkip: handleSkip,
    onSaveStep: saveStep
  });
};

// Initialize the onboarding when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // Check if we're on the correct admin page and have the onboarding data
  if (window.bbRlOnboarding) {
    const container = document.createElement('div');
    container.id = 'bb-rl-onboarding-root';
    document.body.appendChild(container);
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(OnboardingApp, null), container);
  }
});

// Also export for potential manual initialization

})();

/******/ })()
;
//# sourceMappingURL=rl-onboarding.js.map