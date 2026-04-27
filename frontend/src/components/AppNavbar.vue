<template>
  <nav class="bg-white border-b border-gray-100 sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3.5 flex items-center justify-between">

      <!-- Logo -->
      <RouterLink to="/" class="flex items-center gap-2 font-bold text-gray-900 text-lg">
        <span class="text-2xl">🏠</span>
        StaySpot
      </RouterLink>

      <!-- Links centrales -->
      <div class="hidden md:flex items-center gap-1">
        <RouterLink
          to="/espacios"
          class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600
                 hover:bg-gray-50 hover:text-gray-900 transition-colors"
          active-class="bg-gray-100 text-gray-900"
        >
          Explorar
        </RouterLink>
      </div>

      <!-- Acciones -->
      <div class="flex items-center gap-2">
        <template v-if="authStore.isAuthenticated">

          <!-- Dashboard host -->
          <RouterLink
            v-if="authStore.isHost || authStore.isAdmin"
            to="/host"
            class="hidden md:flex items-center gap-1.5 px-3 py-2 text-sm
                   text-gray-600 hover:bg-gray-50 rounded-xl transition-colors"
            active-class="bg-gray-100"
          >
            🏠 Mi Panel
          </RouterLink>

          <!-- Mis reservas -->
          <RouterLink
            to="/mis-reservas"
            class="hidden md:flex items-center gap-1.5 px-3 py-2 text-sm
                   text-gray-600 hover:bg-gray-50 rounded-xl transition-colors"
            active-class="bg-gray-100"
          >
            📋 Mis Reservas
          </RouterLink>

          <!-- Usuario + logout -->
          <div class="flex items-center gap-2 pl-2 border-l border-gray-100">
            <div class="hidden md:block text-right">
              <p class="text-xs font-semibold text-gray-900 leading-none">
                {{ authStore.user?.name?.split(' ')[0] }}
              </p>
              <p class="text-xs text-gray-400 mt-0.5 capitalize">
                {{ authStore.user?.role }}
              </p>
            </div>
            <button
              @click="handleLogout"
              class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50
                     rounded-xl transition-colors"
              title="Cerrar sesión"
            >
              🚪
            </button>
          </div>
        </template>

        <template v-else>
          <RouterLink
            to="/login"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900 transition-colors"
          >
            Iniciar sesión
          </RouterLink>
          <RouterLink
            to="/registro"
            class="px-4 py-2 text-sm bg-gray-900 text-white rounded-xl
                   hover:bg-gray-700 transition-colors"
          >
            Registrarse
          </RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore }    from '../stores/auth.store.js'
import { useGlobalToast }  from '../composables/useToast.js'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useGlobalToast()

const handleLogout = async () => {
  await authStore.logoutAction()
  toast.success('Sesión cerrada exitosamente')
  router.push('/login')
}
</script>