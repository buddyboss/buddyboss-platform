const path = require( 'path' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	...{
		entry: {
			"readylaunch-header/index": './src/js/blocks/bp-core/readylaunch-header/index.js',
			"readylaunch-header/view": './src/js/blocks/bp-core/readylaunch-header/view.js'
			"readylaunch-header/view": './src/js/blocks/bp-core/readylaunch-header/view.js'
		},
		output: {
			filename: '[name].js',
			path: path.join( __dirname, '..', '..', '..', '..', 'src', 'bp-core', 'blocks' ),
		}
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin( {
			requestToExternal( request ) {
			},
			requestToHandle( request ) {
			}
		} )
	],
}
