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

module.exports = {
    ...defaultConfig,
    entry: {
        'index': path.resolve(__dirname, 'readylaunch/index.js'),
    },
    output: {
        path: path.resolve(__dirname, '../../bp-core/admin/bb-settings/readylaunch/build'),
        filename: '[name].js',
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules,
            {
                test: /\.scss$/,
                use: [
                    'style-loader',
                    'css-loader',
                    'sass-loader',
                ],
            },
        ],
    },
}; 