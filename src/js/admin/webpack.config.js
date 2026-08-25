const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const DependencyExtractionWebpackPlugin = require('@wordpress/dependency-extraction-webpack-plugin');
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

/**
 * Replace the inherited DependencyExtractionWebpackPlugin instance with one
 * that also externalizes `@bb/admin-common` → `window.bbAdminCommon` and
 * declares the `bb-admin-common` WP script handle as a dependency.
 *
 * Must NOT add a second instance — `defaultConfig` already contains one.
 * Two instances fight over the generated `.asset.php` file.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} config - A webpack config object that inherits from defaultConfig.
 * @returns {Object} Config with the DependencyExtractionWebpackPlugin replaced.
 */
function withCommonExternal(config) {
    const plugins = (config.plugins || []).filter(
        (p) => p.constructor && p.constructor.name !== 'DependencyExtractionWebpackPlugin'
    );
    plugins.push(
        new DependencyExtractionWebpackPlugin({
            requestToExternal(request) {
                if (request === '@bb/admin-common') {
                    return 'bbAdminCommon';
                }
                // return undefined → default @wordpress/* handling applies.
            },
            requestToHandle(request) {
                if (request === '@bb/admin-common') {
                    return 'bb-admin-common';
                }
            },
        })
    );
    return { ...config, plugins };
}

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

// Settings configuration
const settingsConfig = withCommonExternal({
    ...defaultConfig,
    name: 'settings',
    entry: {
        'index': path.resolve(__dirname, 'settings/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/settings/build'),
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
});

// Integrations marketplace configuration.
// Standalone admin page (BuddyBoss → Integrations); its own bundle so it never
// loads on the Settings page and vice-versa.
const integrationsConfig = withCommonExternal({
    ...defaultConfig,
    name: 'integrations',
    entry: {
        'index': path.resolve(__dirname, 'integrations/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/integrations/build'),
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
});

// Standalone KB modal — mounts the shared KB modal on any admin page and
// exposes window.bbKb. Externalizes @bb/admin-common like the other consumers.
const kbStandaloneConfig = withCommonExternal({
    ...defaultConfig,
    name: 'kb-standalone',
    entry: {
        'index': path.resolve(__dirname, 'kb-standalone/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/kb-standalone/build'),
        filename: '[name].js',
        clean: false,
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules,
            scssRule,
        ],
    },
});

// Shared admin-common layer — built ONCE, consumed by every admin app as an external.
// Exposes its exports on window.bbAdminCommon so app bundles can import
// `@bb/admin-common` as an external (no code duplication across bundles).
//
// NOTE: commonConfig intentionally does NOT use withCommonExternal() — it IS
// window.bbAdminCommon, so it must not externalize itself. Only consumer configs
// (settings, integrations) are wrapped with withCommonExternal().
const commonConfig = {
    ...defaultConfig,
    name: 'common',
    entry: {
        index: path.resolve(__dirname, 'common/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/common/build'),
        filename: '[name].js',
        library: { name: 'bbAdminCommon', type: 'window' },
        clean: { keep: /styles/ },
    },
    module: {
        ...defaultConfig.module,
        rules: [...rules, scssRule],
    },
};

// Export configuration based on build target.
// `readylaunch` target retired in BuddyBoss [BBVERSION] — legacy admin page
// folded into Settings Appearance feature.
if (buildTarget === 'common') {
    module.exports = commonConfig;
} else if (buildTarget === 'rl-onboarding') {
    module.exports = rlOnboardingConfig;
} else if (buildTarget === 'settings') {
    module.exports = settingsConfig;
} else if (buildTarget === 'integrations') {
    module.exports = integrationsConfig;
} else if (buildTarget === 'kb-standalone') {
    module.exports = kbStandaloneConfig;
} else {
    // Default: export all configurations for combined builds. common is listed
    // before settings/integrations to mirror the package.json build order (they
    // externalize @bb/admin-common as a runtime dep, so order is cosmetic here).
    module.exports = [rlOnboardingConfig, commonConfig, settingsConfig, integrationsConfig, kbStandaloneConfig];
}
