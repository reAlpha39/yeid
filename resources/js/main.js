import App from '@/App.vue';
import { registerPlugins } from '@core/utils/plugins';
import axios from 'axios';
import 'moment/locale/id';
import VueEasyLightbox from 'vue-easy-lightbox'
import { createApp } from 'vue';

// Styles
import '@core-scss/template/index.scss';
import '@styles/styles.scss';

// Toast
import Toast from "vue-toastification";
// Import the CSS or use your own!
import "vue-toastification/dist/index.css";

import caslPlugin from './plugins/casl';

// Create Vue app
const app = createApp(App);

const options = {
    // You can set your default options here
};

app.use(Toast, options, caslPlugin, VueEasyLightbox);

// Register plugins
registerPlugins(app);

// Configure Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
  console.error('CSRF token not found');
}

// Make Axios available globally in your Vue components
app.config.globalProperties.$axios = axios;

// Mount Vue app
app.mount('#app');
