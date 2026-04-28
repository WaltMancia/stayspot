<template>
  <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <button
      @click="$router.back()"
      class="flex items-center gap-2 text-sm text-gray-500
             hover:text-gray-900 transition-colors"
    >
      ← Volver
    </button>

    <div v-if="loading" class="flex justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <template v-else-if="reservation">

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            Reserva #{{ reservation.id }}
          </h1>
          <p class="text-sm text-gray-400 mt-1">
            {{ formatDate(reservation.created_at) }}
          </p>
        </div>
        <AppBadge :variant="reservation.status" class="text-sm px-3 py-1">
          {{ statusLabel }}
        </AppBadge>
      </div>

      <!-- Espacio -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5">
        <h2 class="font-semibold text-gray-900 mb-3">Espacio</h2>
        <div class="flex items-center gap-3">
          <div class="w-16 h-16 bg-gray-100 rounded-xl flex items-center
                      justify-center text-3xl">🏠</div>
          <div>
            <p class="font-medium text-gray-900">
              {{ reservation.space?.name }}
            </p>
            <p class="text-sm text-gray-500">
              📍 {{ reservation.space?.city }}
            </p>
            <RouterLink
              :to="`/espacios/${reservation.space?.id}`"
              class="text-xs text-blue-600 hover:underline"
            >
              Ver espacio →
            </RouterLink>
          </div>
        </div>
      </div>

      <!-- Detalles -->
      <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4">
        <h2 class="font-semibold text-gray-900">Detalles de la estadía</h2>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-gray-400 mb-0.5">Check-in</p>
            <p class="font-semibold text-gray-900">
              {{ formatDate(reservation.check_in) }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 mb-0.5">Check-out</p>
            <p class="font-semibold text-gray-900">
              {{ formatDate(reservation.check_out) }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 mb-0.5">Noches</p>
            <p class="font-semibold text-gray-900">
              {{ reservation.nights }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 mb-0.5">Huéspedes</p>
            <p class="font-semibold text-gray-900">
              {{ reservation.guests_count }}
            </p>
          </div>
        </div>

        <div class="border-t pt-4 space-y-2 text-sm">
          <div class="flex justify-between text-gray-500">
            <span>
              {{ formatPrice(reservation.price_per_night) }}
              × {{ reservation.nights }} noches
            </span>
            <span>
              {{ formatPrice(reservation.price_per_night * reservation.nights) }}
            </span>
          </div>
          <div class="flex justify-between font-bold text-gray-900 text-base
                      border-t pt-2">
            <span>Total</span>
            <span>{{ formatPrice(reservation.total_price) }}</span>
          </div>
        </div>
      </div>

      <!-- Sección de pago — solo si está pendiente -->
      <div
        v-if="reservation.status === 'pending'"
        class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4"
      >
        <h2 class="font-semibold text-gray-900">Completar pago</h2>
        <p class="text-sm text-gray-500">
          Tu reserva está pendiente. Completa el pago para confirmarla.
        </p>

        <!-- Stripe Elements se monta aquí -->
        <div id="stripe-card-element" class="p-3 border border-gray-200 rounded-xl" />

        <div v-if="paymentError" class="text-sm text-red-600">
          {{ paymentError }}
        </div>

        <AppButton
          class="w-full"
          size="lg"
          :loading="paymentLoading"
          @click="handlePayment"
        >
          💳 Pagar {{ formatPrice(reservation.total_price) }}
        </AppButton>
      </div>

      <!-- Pago completado -->
      <div
        v-if="reservation.is_paid"
        class="flex items-center gap-3 p-4 bg-emerald-50
               border border-emerald-100 rounded-2xl"
      >
        <span class="text-2xl">✅</span>
        <div>
          <p class="font-semibold text-emerald-800">Pago confirmado</p>
          <p class="text-sm text-emerald-600">
            Pagado el {{ formatDate(reservation.paid_at) }}
          </p>
        </div>
      </div>

      <!-- Reseña — si está completada y no tiene reseña -->
      <div
        v-if="reservation.can_be_reviewed"
        class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4"
      >
        <h2 class="font-semibold text-gray-900">¿Cómo fue tu estadía?</h2>
        <ReviewForm
          :reservation-id="reservation.id"
          @submitted="onReviewSubmitted"
        />
      </div>

      <!-- Reseña existente -->
      <div
        v-else-if="reservation.review"
        class="bg-gray-50 rounded-2xl p-5"
      >
        <h2 class="font-semibold text-gray-900 mb-3">Tu reseña</h2>
        <div class="flex text-yellow-400 mb-2">
          <span v-for="n in reservation.review.rating" :key="n">★</span>
          <span
            v-for="n in (5 - reservation.review.rating)"
            :key="`e-${n}`"
            class="text-gray-300"
          >★</span>
        </div>
        <p class="text-sm text-gray-600">{{ reservation.review.comment }}</p>
      </div>

    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute }                 from 'vue-router'
import { loadStripe }               from '@stripe/stripe-js'
import {
  getReservation,
  createPaymentIntent,
} from '../../services/reservation.service.js'
import { useGlobalToast } from '../../composables/useToast.js'
import AppBadge    from '../../components/ui/AppBadge.vue'
import AppButton   from '../../components/ui/AppButton.vue'
import AppSpinner  from '../../components/ui/AppSpinner.vue'
import ReviewForm  from '../../components/reservation/ReviewForm.vue'

const route = useRoute()
const toast = useGlobalToast()

const reservation    = ref(null)
const loading        = ref(true)
const paymentLoading = ref(false)
const paymentError   = ref('')

let stripe      = null
let cardElement = null

const statusLabels = {
  pending:   'Pendiente de pago',
  confirmed: 'Confirmada',
  completed: 'Completada',
  cancelled: 'Cancelada',
}

const statusLabel = computed(
  () => statusLabels[reservation.value?.status] || reservation.value?.status
)

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('es-ES', {
    weekday: 'long', year: 'numeric',
    month: 'long', day: 'numeric',
  })
}

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)

const initStripe = async () => {
  if (reservation.value?.status !== 'pending') return

  stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY)
  const elements = stripe.elements()

  cardElement = elements.create('card', {
    style: {
      base: {
        fontSize: '16px',
        color: '#1a1a1a',
        '::placeholder': { color: '#9ca3af' },
      },
    },
  })

  // Espera a que el DOM esté listo
  await new Promise(resolve => setTimeout(resolve, 100))
  cardElement.mount('#stripe-card-element')
}

const handlePayment = async () => {
  if (!stripe || !cardElement) return

  paymentLoading.value = true
  paymentError.value   = ''

  try {
    // Obtenemos el client_secret del backend
    const { client_secret } = await createPaymentIntent(reservation.value.id)

    // Confirmamos el pago con Stripe.js
    const { error, paymentIntent } = await stripe.confirmCardPayment(
      client_secret,
      { payment_method: { card: cardElement } }
    )

    if (error) {
      paymentError.value = error.message
      return
    }

    if (paymentIntent.status === 'succeeded') {
      toast.success('¡Pago exitoso! Tu reserva está confirmada.')
      // Recargamos la reserva para ver el nuevo estado
      await fetchReservation()
    }
  } catch (e) {
    paymentError.value = e.response?.data?.message || 'Error al procesar el pago'
  } finally {
    paymentLoading.value = false
  }
}

const fetchReservation = async () => {
  const data = await getReservation(route.params.id)
  reservation.value = data.data
}

const onReviewSubmitted = async () => {
  toast.success('¡Gracias por tu reseña!')
  await fetchReservation()
}

onMounted(async () => {
  try {
    await fetchReservation()
    await initStripe()
  } finally {
    loading.value = false
  }
})
</script>