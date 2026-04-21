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
        'sass-loader',
    ],
};

// Check if we're building for a specific target
const buildTarget = process.env.BUILD_TARGET || 'all';

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

// Settings 2.0 configuration
const settings20Config = {
    ...defaultConfig,
    name: 'settings-2.0',
    entry: {
        'index': path.resolve(__dirname, 'settings-2.0/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/settings-2.0/build'),
        filename: '[name].js',
        clean: {
            keep: /styles/, // Keep the styles directory (SCSS output)
        },
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules,
            scssRule,
        ],
    },
};

// Export configuration based on build target.
// `readylaunch` target retired in BuddyBoss [BBVERSION] — legacy admin page
// folded into Settings 2.0 Appearance feature.
if (buildTarget === 'rl-onboarding') {
    module.exports = rlOnboardingConfig;
} else if (buildTarget === 'settings-2.0') {
    module.exports = settings20Config;
} else {
    // Default: export all configurations for combined builds.
    module.exports = [rlOnboardingConfig, settings20Config];
} 