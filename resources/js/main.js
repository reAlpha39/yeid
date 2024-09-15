import { createApp } from 'vue';
import App from '@/App.vue';
import { registerPlugins } from '@core/utils/plugins';
import axios from 'axios';

// Styles
import '@core-scss/template/index.scss';
import '@styles/styles.scss';

// Create Vue app
const app = createApp(App);

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
