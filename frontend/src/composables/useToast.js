import { ref } from 'vue'

// Composable para notificaciones toast simples
export function useToast() {
    const toasts = ref([])

    const add = (message, type = 'success', duration = 3000) => {
        const id = Date.now()
        toasts.value.push({ id, message, type })
        setTimeout(() => remove(id), duration)
    }

    const remove = (id) => {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    const success = (msg) => add(msg, 'success')
    const error = (msg) => add(msg, 'error', 5000)
    const info = (msg) => add(msg, 'info')

    return { toasts, success, error, info, remove }
}

// Singleton para usar el mismo toast en toda la app
const globalToast = useToast()
export const useGlobalToast = () => globalToast