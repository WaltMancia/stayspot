import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router/index.js'
import App from './App.vue'
import './index.css'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// Cargamos el usuario autenticado al iniciar la app
// Para que el navbar se actualice correctamente al refrescar
router.isReady().then(async () => {
    const { useAuthStore } = await import('./stores/auth.store.js')
    const authStore = useAuthStore()

    if (authStore.token) {
        await authStore.fetchMe()
    }
})

app.mount('#app')