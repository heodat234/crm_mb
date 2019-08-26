module.exports = {
	publicPath: process.env.NODE_ENV === 'production'
	? ''
	: '/',
	outputDir: '/var/www/html/worldfone4x/worldchatboxbuilder/',
	configureWebpack: {
		"devServer": {
			"historyApiFallback": true,
			"disableHostCheck" : true
		}
	},
	devServer:{
		proxy: "http://192.168.16.105:8006",
	}
}