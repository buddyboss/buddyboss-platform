const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

// Find the CSS rule in the default WordPress webpack config
const cssRuleIndex = defaultConfig.module.rules.findIndex(
    rule => rule.test && rule.test.toString().includes('css')
);

// Create a modified version of the rules
const rules = [...defaultConfig.module.rules];

// Modify the CSS rule to exclude SCSS files
if (cssRuleIndex !== -1) {
    const cssRule = { ...rules[cssRuleIndex] };
    cssRule.test = /\.css$/;
    rules[cssRuleIndex] = cssRule;
}

// Common SCSS rule
const scssRule = {
    test: /\.scss$/,
    use: [
        'style-loader',
        'css-loader',
        {
            loader: 'sass-loader',
            options: {
                sassOptions: {
                    // TODO: Remove silenceDeprecations once SCSS files are updated to Dart Sass syntax
                    silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'slash-div', 'legacy-js-api', 'if-function'],
                },
            },
        },
    ],
};

// Check if we're building for a specific target
const buildTarget = process.env.BUILD_TARGET || 'all';

// ReadyLaunch configuration
const readylaunchConfig = {
    ...defaultConfig,
    name: 'readylaunch',
    entry: {
        'index': path.resolve(__dirname, 'readylaunch/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/readylaunch/build'),
        filename: '[name].js',
        clean: false, // Prevent cleaning other build directories
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules,
            scssRule,
        ],
    },
};

// RL Onboarding configuration
const rlOnboardingConfig = {
    ...defaultConfig,
    name: 'rl-onboarding',
    entry: {
        'rl-onboarding': path.resolve(__dirname, 'rl-onboarding/onboarding.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/rl-onboarding/build'),
        filename: '[name].js',
        clean: false, // Prevent cleaning other build directories
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules,
            scssRule,
        ],
    },
};

// Export configuration based on build target
if (buildTarget === 'readylaunch') {
    module.exports = readylaunchConfig;
} else if (buildTarget === 'rl-onboarding') {
    module.exports = rlOnboardingConfig;
} else {
    // Default: export both configurations for combined builds
    module.exports = [readylaunchConfig, rlOnboardingConfig];
} 