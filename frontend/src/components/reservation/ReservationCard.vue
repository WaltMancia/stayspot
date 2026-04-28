<template>
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm
              overflow-hidden">
    <div class="p-5">
      <div class="flex items-start justify-between gap-4">

        <!-- Info del espacio o huésped -->
        <div class="flex items-center gap-3 flex-1 min-w-0">
          <div class="w-14 h-14 bg-gray-100 rounded-xl flex items-center
                      justify-center text-2xl flex-shrink-0">
            🏠
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-gray-900 truncate">
              {{
                role === 'host'
                  ? reservation.guest?.name
                  : reservation.space?.name
              }}
            </p>
            <p v-if="role === 'guest'" class="text-sm text-gray-400 truncate">
              📍 {{ reservation.space?.city }}
            </p>
            <p v-else class="text-sm text-gray-400">
              📧 {{ reservation.guest?.email }}
            </p>
          </div>
        </div>

        <!-- Status badge -->
        <AppBadge :variant="reservation.status">
          {{ statusLabel }}
        </AppBadge>
      </div>

      <!-- Fechas y detalles -->
      <div class="mt-4 grid grid-cols-3 gap-3 text-sm">
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-400 mb-0.5">Check-in</p>
          <p class="font-medium text-gray-900">{{ formatDate(reservation.check_in) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-400 mb-0.5">Check-out</p>
          <p class="font-medium text-gray-900">{{ formatDate(reservation.check_out) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-3">
          <p class="text-xs text-gray-400 mb-0.5">Total</p>
          <p class="font-bold text-gray-900">{{ formatPrice(reservation.total_price) }}</p>
        </div>
      </div>

      <!-- Acciones -->
      <div class="mt-4 flex items-center gap-2 flex-wrap">
        <RouterLink :to="`/mis-reservas/${reservation.id}`">
          <AppButton size="sm" variant="secondary">Ver detalles</AppButton>
        </RouterLink>

        <!-- Pagar si está pendiente (guest) -->
        <RouterLink
          v-if="role === 'guest' && reservation.status === 'pending'"
          :to="`/mis-reservas/${reservation.id}`"
        >
          <AppButton size="sm" variant="success">💳 Pagar ahora</AppButton>
        </RouterLink>

        <!-- Confirmar si está pendiente (host) -->
        <AppButton
          v-if="role === 'host' && reservation.status === 'pending'"
          size="sm"
          variant="success"
          :loading="confirming"
          @click="handleConfirm"
        >
          ✓ Confirmar
        </AppButton>

        <!-- Cancelar -->
        <AppButton
          v-if="reservation.can_be_cancelled"
          size="sm"
          variant="danger"
          :loading="cancelling"
          @click="showCancelModal = true"
        >
          Cancelar
        </AppButton>
      </div>
    </div>

    <!-- Modal de cancelación -->
    <Teleport to="body">
      <div
        v-if="showCancelModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/50"
          @click="showCancelModal = false"
        />
        <div class="relative bg-white rounded-2xl p-6 w-full max-w-md z-10">
          <h3 class="font-bold text-gray-900 mb-2">Cancelar reserva</h3>
          <p class="text-sm text-gray-500 mb-4">
            ¿Estás seguro? Esta acción no se puede deshacer.
          </p>
          <textarea
            v-model="cancelReason"
            placeholder="Motivo de cancelación (opcional)"
            rows="3"
            class="w-full px-3 py-2 border border-gray-200 rounded-xl
                   text-sm focus:outline-none focus:ring-2 focus:ring-gray-900
                   resize-none mb-4"
          />
          <div class="flex gap-3">
            <AppButton
              variant="danger"
              :loading="cancelling"
              @click="handleCancel"
              class="flex-1"
            >
              Sí, cancelar
            </AppButton>
            <AppButton
              variant="secondary"
              @click="showCancelModal = false"
              class="flex-1"
            >
              Volver
            </AppButton>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  cancelReservation,
  confirmReservation,
} from '../../services/reservation.service.js'
import { useGlobalToast } from '../../composables/useToast.js'
import AppButton from '../ui/AppButton.vue'
import AppBadge  from '../ui/AppBadge.vue'

const props = defineProps({
  reservation: { type: Object, required: true },
  role:        { type: String, default: 'guest' },
})
const emit = defineEmits(['cancelled', 'confirmed'])

const toast          = useGlobalToast()
const showCancelModal = ref(false)
const cancelReason   = ref('')
const cancelling     = ref(false)
const confirming     = ref(false)

const statusLabels = {
  pending:   'Pendiente',
  confirmed: 'Confirmada',
  paid:      'Pagada',
  shipped:   'En camino',
  completed: 'Completada',
  cancelled: 'Cancelada',
}

const statusLabel = computed(
  () => statusLabels[props.reservation.status] || props.reservation.status
)

const formatDate = (date) =>
  new Date(date).toLocaleDateString('es-ES', {
    day: 'numeric', month: 'short', year: 'numeric',
  })

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)

const handleCancel = async () => {
  cancelling.value = true
  try {
    await cancelReservation(props.reservation.id, cancelReason.value)
    toast.success('Reserva cancelada')
    showCancelModal.value = false
    emit('cancelled')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Error al cancelar')
  } finally {
    cancelling.value = false
  }
}

const handleConfirm = async () => {
  confirming.value = true
  try {
    await confirmReservation(props.reservation.id)
    toast.success('Reserva confirmada')
    emit('confirmed')
  } catch (e) {
    toast.error(e.response?.data?.message || 'Error al confirmar')
  } finally {
    confirming.value = false
  }
}
</script>