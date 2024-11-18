import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'

export const store = createPinia()
export default function (app) {
  store.use(piniaPluginPersistedstate)
  app.use(store)
}
