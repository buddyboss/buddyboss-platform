/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./readylaunch/src/components/ReadyLaunchSettings.js":
/*!***********************************************************!*\
  !*** ./readylaunch/src/components/ReadyLaunchSettings.js ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ReadyLaunchSettings: () => (/* binding */ ReadyLaunchSettings)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _Sidebar__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./Sidebar */ "./readylaunch/src/components/Sidebar.js");
/* harmony import */ var _utils_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../utils/api */ "./readylaunch/src/utils/api.js");






const ReadyLaunchSettings = () => {
  const [activeTab, setActiveTab] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('activation');
  const [settings, setSettings] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)({
    readyLaunchEnabled: false,
    communityName: ''
  });
  const [isLoading, setIsLoading] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  const [isSaving, setIsSaving] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(false);
  const [notification, setNotification] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(null);
  const [initialLoad, setInitialLoad] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(true);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    loadSettings();
  }, []);

  // Auto-save when settings change (except on initial load)
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    if (!initialLoad && !isLoading) {
      const saveTimer = setTimeout(() => {
        handleSave();
      }, 500); // Debounce save for 500ms

      return () => clearTimeout(saveTimer);
    }
  }, [settings]);
  const loadSettings = async () => {
    setIsLoading(true);
    const data = await (0,_utils_api__WEBPACK_IMPORTED_MODULE_5__.fetchSettings)();
    if (data) {
      setSettings(data);
    }
    setIsLoading(false);
    setInitialLoad(false);
  };
  const handleSave = async () => {
    setIsSaving(true);
    const data = await (0,_utils_api__WEBPACK_IMPORTED_MODULE_5__.saveSettings)(settings);
    setIsSaving(false);
    if (data) {
      setNotification({
        status: 'success',
        message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Settings saved successfully!', 'buddyboss')
      });
    } else {
      setNotification({
        status: 'error',
        message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Error saving settings. Please try again.', 'buddyboss')
      });
    }

    // Auto-dismiss notification after 3 seconds.
    setTimeout(() => {
      setNotification(null);
    }, 3000);
  };
  const handleToggleChange = name => value => {
    setSettings({
      ...settings,
      [name]: value
    });
  };
  const handleInputChange = name => value => {
    setSettings({
      ...settings,
      [name]: value
    });
  };
  const renderContent = () => {
    if (isLoading) {
      return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
        className: "settings-loading"
      }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Loading settings...', 'buddyboss')));
    }
    switch (activeTab) {
      case 'activation':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-content"
        }, notification && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Notice, {
          status: notification.status,
          isDismissible: false,
          className: "settings-notice"
        }, notification.message), isSaving && (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-saving-indicator"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.Spinner, null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", null, (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_2__.__)('Saving...', 'buddyboss'))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-card"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-toggle-container"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "toggle-content"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "ReadyLaunch Enabled"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Description text goes here explaining RL activation and deactivation logics")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.ToggleControl, {
          checked: settings.readyLaunchEnabled,
          onChange: handleToggleChange('readyLaunchEnabled')
        }))), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-card"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-header"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("h3", null, "Site Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
          className: "help-icon"
        }, "?")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("hr", null), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "settings-form-field"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "field-label"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("label", null, "Community Name"), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", null, "Description text goes here")), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
          className: "field-input"
        }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_wordpress_components__WEBPACK_IMPORTED_MODULE_3__.TextControl, {
          placeholder: "Type community name",
          value: settings.communityName,
          onChange: handleInputChange('communityName')
        }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("p", {
          className: "field-description"
        }, "Description texts goes here")))));
      case 'styles':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Styles Settings");
      case 'pages':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Pages & Sidebars Settings");
      case 'menus':
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Menus Settings");
      default:
        return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", null, "Select a tab");
    }
  };
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-readylaunch-settings-container"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_Sidebar__WEBPACK_IMPORTED_MODULE_4__.Sidebar, {
    activeTab: activeTab,
    setActiveTab: setActiveTab
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-readylaunch-settings-content"
  }, renderContent()));
};

/***/ }),

/***/ "./readylaunch/src/components/Sidebar.js":
/*!***********************************************!*\
  !*** ./readylaunch/src/components/Sidebar.js ***!
  \***********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Sidebar: () => (/* binding */ Sidebar)
/* harmony export */ });
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);


const Sidebar = ({
  activeTab,
  setActiveTab
}) => {
  const menuItems = [{
    id: 'activation',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Activation Settings', 'buddyboss'),
    icon: 'dashicons-toggle-on'
  }, {
    id: 'styles',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Styles', 'buddyboss'),
    icon: 'dashicons-admin-appearance'
  }, {
    id: 'pages',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Pages & Sidebars', 'buddyboss'),
    icon: 'dashicons-admin-page'
  }, {
    id: 'menus',
    label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Menus', 'buddyboss'),
    icon: 'dashicons-menu'
  }];
  return (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(react__WEBPACK_IMPORTED_MODULE_0__.Fragment, null, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("div", {
    className: "bb-readylaunch-sidebar"
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("ul", null, menuItems.map(item => (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("li", {
    key: item.id,
    className: activeTab === item.id ? 'active' : '',
    onClick: () => setActiveTab(item.id)
  }, (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)("span", {
    className: `dashicons ${item.icon}`
  }), item.label)))));
};

/***/ }),

/***/ "./readylaunch/src/styles/settings.css":
/*!*********************************************!*\
  !*** ./readylaunch/src/styles/settings.css ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }),

/***/ "./readylaunch/src/utils/api.js":
/*!**************************************!*\
  !*** ./readylaunch/src/utils/api.js ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   debounce: () => (/* binding */ debounce),
/* harmony export */   fetchSettings: () => (/* binding */ fetchSettings),
/* harmony export */   saveSettings: () => (/* binding */ saveSettings)
/* harmony export */ });
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/api-fetch */ "@wordpress/api-fetch");
/* harmony import */ var _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0__);


/**
 * Fetch ReadyLaunch settings from the WordPress REST API.
 *
 * @returns {Promise} Promise that resolves to settings object.
 */
const fetchSettings = async () => {
  try {
    return await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/buddyboss/v1/readylaunch/settings',
      method: 'GET'
    });
  } catch (error) {
    console.error('Error fetching ReadyLaunch settings:', error);
    return null;
  }
};

/**
 * Save ReadyLaunch settings to the WordPress REST API.
 *
 * @param {Object} settings - The settings object to save.
 * @returns {Promise} Promise that resolves to updated settings object.
 */
const saveSettings = async settings => {
  try {
    return await _wordpress_api_fetch__WEBPACK_IMPORTED_MODULE_0___default()({
      path: '/buddyboss/v1/readylaunch/settings',
      method: 'POST',
      data: settings
    });
  } catch (error) {
    console.error('Error saving ReadyLaunch settings:', error);
    return null;
  }
};

/**
 * Creates a debounced function that delays invoking func until after wait milliseconds have elapsed
 * since the last time the debounced function was invoked.
 *
 * @param {Function} func - The function to debounce.
 * @param {number} wait - The number of milliseconds to delay.
 * @returns {Function} The debounced function.
 */
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

/***/ }),

/***/ "@wordpress/api-fetch":
/*!**********************************!*\
  !*** external ["wp","apiFetch"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["apiFetch"];

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
/*!**********************************!*\
  !*** ./readylaunch/src/index.js ***!
  \**********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_ReadyLaunchSettings__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/ReadyLaunchSettings */ "./readylaunch/src/components/ReadyLaunchSettings.js");
/* harmony import */ var _styles_settings_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./styles/settings.css */ "./readylaunch/src/styles/settings.css");





// Initialize the React app
const app = document.getElementById('bb-rl-field-wrap');
if (app) {
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)((0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(_components_ReadyLaunchSettings__WEBPACK_IMPORTED_MODULE_2__.ReadyLaunchSettings, null), app);
}
})();

/******/ })()
;
//# sourceMappingURL=index.js.map