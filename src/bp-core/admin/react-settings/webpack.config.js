const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

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
            ...defaultConfig.module.rules,
        ],
    },
}; 