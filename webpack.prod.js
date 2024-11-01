const path = require('path');

const ExtractTextPlugin = require("extract-text-webpack-plugin");

const extractSass = new ExtractTextPlugin({
    filename: "../../css/dist/plugin.bundle.css",
    disable: process.env.NODE_ENV === "development"
});

process.env.BABEL_ENV = 'production';
process.env.NODE_ENV = 'production';

module.exports = {
	entry: {
		plugin: './js/plugin.js'
	},
	output: {
		filename: '[name].bundle.js',
		path: path.resolve( __dirname, "./js/dist" )
	},
	devtool: 'source-map',
	mode: "production",
	module: {
		rules: [
			{
				test: /\.(js|jsx|mjs)$/,
				exclude: /node_modules/,
				use: {
					loader: "babel-loader",
					options: {
						babelrc: false,
						presets: [ '@babel/preset-env', '@babel/preset-react' ],
						plugins: [ '@babel/plugin-proposal-class-properties' ]
					}
				}
			},
			{
				test: /\.s?css$/,
				use: extractSass.extract({
					use: [{
						loader: "css-loader"
					}, {
						loader: "sass-loader",
						options: {
							outputStyle: 'compressed',
							options: {
								sourceMap: true
							}
						}
					}],
					fallback: "style-loader"
				})
			}
		]
	},
	plugins: [
        extractSass
    ]
}