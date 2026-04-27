<template>
  <div class="min-h-screen flex">
    <!-- Panel izquierdo decorativo -->
    <div class="hidden lg:flex lg:w-1/2 bg-gray-900 flex-col
                justify-between p-16 relative overflow-hidden">
      <div class="absolute inset-0 bg-gradient-to-br
                  from-gray-900 via-gray-800 to-gray-900" />
      <div class="absolute top-0 right-0 w-96 h-96 bg-white/5
                  rounded-full -translate-y-1/2 translate-x-1/2" />

      <div class="relative z-10 flex items-center gap-3">
        <span class="text-3xl">🏠</span>
        <span class="text-white font-bold text-2xl">StaySpot</span>
      </div>

      <div class="relative z-10">
        <h1 class="text-4xl font-bold text-white leading-tight mb-4">
          Tu próxima aventura<br />te espera aquí
        </h1>
        <p class="text-gray-400 text-lg">
          Espacios únicos en los destinos más increíbles de Guatemala.
        </p>
      </div>

      <p class="relative z-10 text-gray-600 text-sm">
        © 2024 StaySpot
      </p>
    </div>

    <!-- Formulario -->
    <div class="flex-1 flex items-center justify-center px-8">
      <div class="w-full max-w-md">
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900">Bienvenido de nuevo</h2>
          <p class="text-gray-500 mt-1">Ingresa tus credenciales para continuar</p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Correo electrónico
            </label>
            <input
              v-model="form.email"
              type="email"
              required
              placeholder="tu@email.com"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Contraseña
            </label>
            <input
              v-model="form.password"
              type="password"
              required
              placeholder="••••••••"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
          </div>

          <!-- Errores -->
          <div
            v-if="error"
            class="p-3 bg-red-50 border border-red-100 rounded-xl
                   text-sm text-red-700"
          >
            {{ error }}
          </div>

          <AppButton
            type="submit"
            :loading="loading"
            class="w-full"
            size="lg"
          >
            Iniciar sesión
          </AppButton>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
          ¿No tienes cuenta?{' '}
          <RouterLink to="/registro" class="font-medium text-gray-900 hover:underline">
            Regístrate gratis
          </RouterLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore }   from '../../stores/auth.store.js'
import { useGlobalToast } from '../../composables/useToast.js'
import AppButton          from '../../components/ui/AppButton.vue'

const authStore = useAuthStore()
const router    = useRouter()
const route     = useRoute()
const toast     = useGlobalToast()

const form    = reactive({ email: '', password: '' })
const loading = ref(false)
const error   = ref('')

const handleSubmit = async () => {
  loading.value = true
  error.value   = ''
  try {
    await authStore.loginAction(form)
    toast.success(`¡Bienvenido, ${authStore.user.name}!`)
    // Redirige a la página que intentaba visitar, o al inicio
    const redirect = route.query.redirect || '/'
    router.push(redirect)
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors?.email?.[0] || 'Credenciales inválidas'
  } finally {
    loading.value = false
  }
}
</script>