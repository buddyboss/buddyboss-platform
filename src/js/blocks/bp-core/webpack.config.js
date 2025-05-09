const path                 = require( 'path' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

/**
 * WordPress Dependencies
 */
const defaultConfig                     = require( '@wordpress/scripts/config/webpack.config.js' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	...{
		entry: {
			"block-data/index": {
				import: './src/js/blocks/bp-core/block-assets/block-data.js',
				library: {
					name: [ 'buddyboss', 'blockData' ],
					type: 'window',
				},
			},
			"block-components/index": {
				import: './src/js/blocks/bp-core/block-components/block-components.js',
				library: {
					name: [ 'buddyboss', 'blockComponents' ],
					type: 'window',
				},
			},
			"block-collection/index": './src/js/blocks/bp-core/block-collection/block-collection.js',
			"login-form/index": './src/js/blocks/bp-core/login-form/login-form.js',
			"readylaunch-header/index": './src/js/blocks/bp-core/readylaunch-header/index.js',
			"readylaunch-header/style": './src/js/blocks/bp-core/readylaunch-header/style.scss',
			"readylaunch-header/editor": './src/js/blocks/bp-core/readylaunch-header/editor.scss',
		},
		output: {
			filename: '[name].js',
			path: path.join( __dirname, '..', '..', '..', '..', 'src', 'bp-core', 'blocks' ),
		},
		module: {
			...defaultConfig.module,
			rules: [
				...defaultConfig.module.rules,
				{
					test: /\.scss$/,
					use: [
						MiniCssExtractPlugin.loader,
						'css-loader',
						'sass-loader',
					],
			},
			],
		},
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
			plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new DependencyExtractionWebpackPlugin(
			{
					requestToExternal( request ) {
						if ( request === '@buddypress/block-components' ) {
							return [ 'buddyboss', 'blockComponents' ];
						} else if ( request === '@buddypress/block-data' ) {
							return [ 'buddyboss', 'blockData' ];
						}
				},
					requestToHandle( request ) {
						if ( request === '@buddypress/block-components' ) {
							return 'bp-block-components';
						} else if ( request === '@buddypress/block-data' ) {
							return 'bp-block-data';
						}
				}
			}
		),
		new MiniCssExtractPlugin(
			{
				filename: '[name].css',
			}
		),
	],
}
