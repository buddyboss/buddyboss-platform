/**
 * External dependencies
 */
const path = require( 'path' );

/**
 * WordPress dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		'bp-zoom-meeting-block': './assets/js/blocks/bp-zoom-meeting-block.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js/blocks/build/' ),
		filename: '[name].js',
	},
};
