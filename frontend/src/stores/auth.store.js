import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login, register, logout, getMe } from '../services/auth.service.js'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
    // En Pinia con Composition API (setup stores)
    // definimos el estado con ref() igual que en componentes
    const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))
    const token = ref(localStorage.getItem('token') || null)

    // computed equivale a getters en Options API
    const isAuthenticated = computed(() => !!token.value)
    const isHost = computed(() => user.value?.role === 'host')
    const isAdmin = computed(() => user.value?.role === 'admin')
    const isGuest = computed(() => user.value?.role === 'guest')

    // Actions — funciones normales
    const setAuth = (userData, userToken) => {
        user.value = userData
        token.value = userToken
        localStorage.setItem('user', JSON.stringify(userData))
        localStorage.setItem('token', userToken)
    }

    const clearAuth = () => {
        user.value = null
        token.value = null
        localStorage.removeItem('user')
        localStorage.removeItem('token')
    }

    const loginAction = async (credentials) => {
        const data = await login(credentials)
        setAuth(data.user, data.access_token)
        return data
    }

    const registerAction = async (userData) => {
        const data = await register(userData)
        setAuth(data.user, data.access_token)
        return data
    }

    const logoutAction = async () => {
        try { await logout() } catch { /* ignora errores de red */ }
        clearAuth()
    }

    const fetchMe = async () => {
        try {
            const data = await getMe()
            user.value = data.data
            localStorage.setItem('user', JSON.stringify(data.data))
        } catch {
            clearAuth()
        }
    }

    return {
        user, token,
        isAuthenticated, isHost, isAdmin, isGuest,
        loginAction, registerAction, logoutAction, fetchMe,
    }
})