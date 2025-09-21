import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import Root from './components/base/Root.vue';

// Vuetify
import 'vuetify/styles';
import { createVuetify } from 'vuetify';
import * as vuetifyComponents from 'vuetify/components';
import * as vuetifyDirectives from 'vuetify/directives';
import '@mdi/font/css/materialdesignicons.css';
import { useAuthStore } from './stores/authStore';
import { ensureCsrf } from './services/csrfService';

const vuetify = createVuetify({
  components: vuetifyComponents,
  directives: vuetifyDirectives,
});

// Disparo temprano de obtención de CSRF (no bloqueante)
ensureCsrf().catch(()=>{/* silencioso: se reintentará automáticamente al primer POST */});

const app = createApp(Root);
const pinia = createPinia();
app.use(pinia);
app.use(router);
app.use(vuetify);
// Bootstrap de sesión persistida
const auth = useAuthStore();
auth.bootstrap();
app.mount('#app');
