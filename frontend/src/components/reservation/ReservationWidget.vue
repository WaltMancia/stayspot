<template>
  <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-6
              space-y-4">

    <!-- Precio -->
    <div class="flex items-baseline gap-1">
      <span class="text-2xl font-bold text-gray-900">
        {{ formatPrice(space.price_per_night) }}
      </span>
      <span class="text-gray-500 text-sm">/ noche</span>
    </div>

    <!-- Formulario de fechas -->
    <div class="border border-gray-200 rounded-xl overflow-hidden">
      <div class="grid grid-cols-2">
        <div class="p-3 border-r border-gray-200">
          <label class="block text-xs font-bold text-gray-700 uppercase
                        tracking-wide mb-1">
            Check-in
          </label>
          <input
            v-model="form.check_in"
            type="date"
            :min="today"
            @change="onDatesChange"
            class="w-full text-sm text-gray-900 focus:outline-none"
          >
        </div>
        <div class="p-3">
          <label class="block text-xs font-bold text-gray-700 uppercase
                        tracking-wide mb-1">
            Check-out
          </label>
          <input
            v-model="form.check_out"
            type="date"
            :min="form.check_in || today"
            @change="onDatesChange"
            class="w-full text-sm text-gray-900 focus:outline-none"
          >
        </div>
      </div>
      <div class="border-t border-gray-200 p-3">
        <label class="block text-xs font-bold text-gray-700 uppercase
                      tracking-wide mb-1">
          Huéspedes
        </label>
        <select
          v-model="form.guests_count"
          class="w-full text-sm text-gray-900 focus:outline-none bg-transparent"
        >
          <option
            v-for="n in space.max_guests"
            :key="n"
            :value="n"
          >
            {{ n }} {{ n === 1 ? 'huésped' : 'huéspedes' }}
          </option>
        </select>
      </div>
    </div>

    <!-- Fechas bloqueadas -->
    <div
      v-if="blockedDates.length > 0"
      class="text-xs text-amber-700 bg-amber-50 rounded-xl p-3"
    >
      ⚠️ Este espacio tiene fechas no disponibles.
      Revisa tu selección.
    </div>

    <!-- Estimación de precio -->
    <div v-if="loadingPrice" class="flex justify-center py-2">
      <AppSpinner size="sm" />
    </div>

    <div
      v-else-if="priceEstimate"
      class="space-y-2 text-sm"
    >
      <!-- Disponibilidad -->
      <div
        :class="[
          'flex items-center gap-2 p-2 rounded-lg text-xs font-medium',
          priceEstimate.is_available
            ? 'bg-emerald-50 text-emerald-700'
            : 'bg-red-50 text-red-700',
        ]"
      >
        <span>{{ priceEstimate.is_available ? '✅' : '❌' }}</span>
        <span>
          {{ priceEstimate.is_available
            ? 'Disponible para las fechas seleccionadas'
            : 'No disponible en estas fechas' }}
        </span>
      </div>

      <!-- Desglose de precio -->
      <div class="space-y-1 pt-1">
        <div class="flex justify-between text-gray-600">
          <span>
            {{ formatPrice(space.price_per_night) }} × {{ priceEstimate.nights }}
            {{ priceEstimate.nights === 1 ? 'noche' : 'noches' }}
          </span>
          <span>{{ formatPrice(priceEstimate.subtotal) }}</span>
        </div>

        <div
          v-if="priceEstimate.discount > 0"
          class="flex justify-between text-emerald-600"
        >
          <span>{{ priceEstimate.discount_reason }}</span>
          <span>-{{ formatPrice(priceEstimate.discount) }}</span>
        </div>

        <div class="flex justify-between font-bold text-gray-900
                    border-t pt-2 mt-2">
          <span>Total</span>
          <span>{{ formatPrice(priceEstimate.total) }}</span>
        </div>
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="error"
      class="p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700"
    >
      {{ error }}
    </div>

    <!-- Botón reservar -->
    <AppButton
      class="w-full"
      size="lg"
      :loading="loading"
      :disabled="!canReserve"
      @click="handleReserve"
    >
      {{ reserveButtonText }}
    </AppButton>

    <!-- Login requerido -->
    <p v-if="!authStore.isAuthenticated" class="text-center text-xs text-gray-500">
      <RouterLink to="/login" class="underline text-gray-900">
        Inicia sesión
      </RouterLink>
      para realizar una reserva
    </p>

    <p class="text-center text-xs text-gray-400">
      No se realizará ningún cargo hasta confirmar
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter }            from 'vue-router'
import { useAuthStore }         from '../../stores/auth.store.js'
import { useAvailability }      from '../../composables/useAvailability.js'
import { createReservation }    from '../../services/reservation.service.js'
import { useGlobalToast }       from '../../composables/useToast.js'
import AppButton                from '../ui/AppButton.vue'
import AppSpinner               from '../ui/AppSpinner.vue'

const props = defineProps({
  space: { type: Object, required: true },
})
const emit = defineEmits(['reserved'])

const authStore = useAuthStore()
const router    = useRouter()
const toast     = useGlobalToast()

const {
  blockedDates,
  priceEstimate,
  loadingPrice,
  fetchBlockedDates,
  fetchPriceEstimate,
  isDateBlocked,
} = useAvailability(props.space.id)

const form = ref({
  check_in:     '',
  check_out:    '',
  guests_count: 1,
})
const loading = ref(false)
const error   = ref('')

const today = new Date().toISOString().split('T')[0]

const canReserve = computed(() =>
  authStore.isAuthenticated &&
  form.value.check_in &&
  form.value.check_out &&
  priceEstimate.value?.is_available &&
  !loading.value
)

const reserveButtonText = computed(() => {
  if (!authStore.isAuthenticated) return 'Inicia sesión para reservar'
  if (!form.value.check_in || !form.value.check_out) return 'Selecciona las fechas'
  if (priceEstimate.value && !priceEstimate.value.is_available) return 'No disponible'
  if (priceEstimate.value?.is_available) return 'Reservar ahora'
  return 'Reservar'
})

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)

// Cuando cambian las fechas, obtenemos el estimado de precio
const onDatesChange = async () => {
  error.value = ''
  if (form.value.check_in && form.value.check_out) {
    // Verificamos que check_out sea después de check_in
    if (form.value.check_out <= form.value.check_in) {
      error.value = 'El check-out debe ser posterior al check-in'
      return
    }
    await fetchPriceEstimate(form.value.check_in, form.value.check_out)
  }
}

const handleReserve = async () => {
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }

  loading.value = true
  error.value   = ''

  try {
    const reservation = await createReservation({
      space_id:     props.space.id,
      check_in:     form.value.check_in,
      check_out:    form.value.check_out,
      guests_count: form.value.guests_count,
    })

    emit('reserved', reservation.data)
  } catch (e) {
    error.value = e.response?.data?.message
      || 'Error al crear la reserva. Intenta de nuevo.'
  } finally {
    loading.value = false
  }
}

// Cargamos las fechas bloqueadas al montar
fetchBlockedDates()
</script>