// import io from 'socket.io-client'; 
import './assets/js/app.js'
import mainstyle from './assets/css/main.css'
import Vue from 'vue'
import App from './App.vue'


Vue.config.devtools=false
Vue.config.productionTip = false

new Vue({
	// router,
	render: h => h(App)
}).$mount('#app')
