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
    cssRule.test = /\.css$/; // Only target CSS files, not SCSS
    rules[cssRuleIndex] = cssRule;
}

module.exports = {
    ...defaultConfig,
    entry: {
        index: path.resolve(__dirname, 'readylaunch/src/index.js'),
    },
    output: {
        path: path.resolve(__dirname, 'readylaunch/build'),
        filename: '[name].js',
    },
    module: {
        ...defaultConfig.module,
        rules: [
            ...rules, // Use the modified rules array
            {
                test: /\.scss$/,
                use: [
                    // First compile the SCSS to CSS and then process it with style loaders
                    'style-loader',
                    'css-loader',
                    'sass-loader',
                ],
            },
        ],
    },
}; 