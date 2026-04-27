<template>
  <div class="min-h-screen flex items-center justify-center
              bg-gray-50 px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <span class="text-4xl">🏠</span>
        <h2 class="text-2xl font-bold text-gray-900 mt-2">Crea tu cuenta</h2>
        <p class="text-gray-500 mt-1 text-sm">
          Únete a nuestra comunidad de viajeros
        </p>
      </div>

      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8">
        <form @submit.prevent="handleSubmit" class="space-y-4">

          <!-- Tipo de cuenta -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Tipo de cuenta
            </label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="option in roleOptions"
                :key="option.value"
                type="button"
                @click="form.role = option.value"
                :class="[
                  'flex items-center justify-center gap-2 py-2.5 rounded-xl',
                  'border text-sm font-medium transition-colors',
                  form.role === option.value
                    ? 'border-gray-900 bg-gray-900 text-white'
                    : 'border-gray-200 text-gray-600 hover:border-gray-300',
                ]"
              >
                {{ option.icon }} {{ option.label }}
              </button>
            </div>
          </div>

          <div v-for="field in fields" :key="field.key">
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              {{ field.label }}
            </label>
            <input
              v-model="form[field.key]"
              :type="field.type"
              :placeholder="field.placeholder"
              :required="field.required"
              :minlength="field.minlength"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
            <!-- Error por campo -->
            <p v-if="errors[field.key]" class="text-xs text-red-600 mt-1">
              {{ errors[field.key][0] }}
            </p>
          </div>

          <AppButton
            type="submit"
            :loading="loading"
            class="w-full mt-2"
            size="lg"
          >
            Crear cuenta
          </AppButton>
        </form>
      </div>

      <p class="text-center text-sm text-gray-500 mt-4">
        ¿Ya tienes cuenta?
        <RouterLink to="/login" class="font-medium text-gray-900 hover:underline">
          Inicia sesión
        </RouterLink>
      </p>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter }      from 'vue-router'
import { useAuthStore }   from '../../stores/auth.store.js'
import { useGlobalToast } from '../../composables/useToast.js'
import AppButton          from '../../components/ui/AppButton.vue'

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useGlobalToast()

const form    = reactive({
  name: '', email: '', password: '',
  password_confirmation: '', role: 'guest',
})
const loading = ref(false)
const errors  = ref({})

const roleOptions = [
  { value: 'guest', label: 'Viajero',   icon: '🧳' },
  { value: 'host',  label: 'Anfitrión', icon: '🏠' },
]

const fields = [
  { key: 'name',                  label: 'Nombre completo',   type: 'text',     placeholder: 'Juan Pérez',          required: true },
  { key: 'email',                 label: 'Correo electrónico',type: 'email',    placeholder: 'tu@email.com',         required: true },
  { key: 'password',              label: 'Contraseña',         type: 'password', placeholder: '••••••••',             required: true, minlength: 8 },
  { key: 'password_confirmation', label: 'Confirmar contraseña',type:'password', placeholder: '••••••••',            required: true },
]

const handleSubmit = async () => {
  loading.value = true
  errors.value  = {}
  try {
    await authStore.registerAction(form)
    toast.success('¡Bienvenido a StaySpot!')
    router.push('/')
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
  } finally {
    loading.value = false
  }
}
</script>