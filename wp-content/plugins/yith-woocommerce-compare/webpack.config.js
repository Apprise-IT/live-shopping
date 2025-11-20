const path = require( 'path' ),
	defaultConfig = require( '@wordpress/scripts/config/webpack.config' ),
	WooCommerceDependencyExtractionWebpackPlugin = require( '@woocommerce/dependency-extraction-webpack-plugin' );

module.exports = {
	...defaultConfig,
	devtool    : 'source-map',
	entry      : {
		'frontend': './assets/js/src/frontend.js',
		'admin': './assets/js/src/admin.js',
	},
	mode: 'production',
	module: {
		rules: [
			{
				test: /\.(js|jsx)$/,
				exclude: /(node_modules|bower_components)/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/preset-env', '@babel/react' ],
						plugins: [ [ '@babel/transform-runtime' ] ],
					},
				}
			}
		]
	},
	optimization: {
		minimize: false,
	},
	output     : {
		filename: (pathData) => {
			let name = pathData.chunk.name,
				components = name.split( '/' ),
				fileName = components?.[components.length - 1];

			fileName = `woocompare-${fileName}`
				.replace('-frontend', '');

			components[components.length - 1] = `${fileName}.js`;

			return components.join( '/' );
		},
		path: path.resolve( __dirname, 'assets/js' ),
		libraryTarget: 'window'
	},
	resolve: {
		extensions: ['*', '.js', '.jsx'],
	},
	plugins: [
		...defaultConfig.plugins.filter(
			( plugin ) =>
				! [
					'DependencyExtractionWebpackPlugin',
					'CleanWebpackPlugin',
				].includes( plugin.constructor.name )
		),
		new WooCommerceDependencyExtractionWebpackPlugin(),
	],
};
