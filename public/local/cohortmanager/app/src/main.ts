import { createApp } from 'vue'
import { createPinia } from 'pinia'
import './style.css'
import App from './App.vue'
import router from './router'

const init = async (selector: string) => {
  const app = createApp(App);
  const pinia = createPinia();
  
  // Use the router and pinia
  app.use(router);
  app.use(pinia);
  
  app.mount(selector);

  return app;
};

export default { init };