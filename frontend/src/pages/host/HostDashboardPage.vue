<template>
  <div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold text-gray-900">
        Bienvenido, {{ authStore.user?.name?.split(' ')[0] }} 👋
      </h1>
      <p class="text-gray-500 text-sm mt-0.5">
        Panel de administración de tus espacios
      </p>
    </div>

    <!-- Stats -->
    <div v-if="loadingStats" class="flex justify-center py-8">
      <AppSpinner />
    </div>

    <div v-else class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div
        v-for="stat in statCards"
        :key="stat.label"
        class="bg-white rounded-2xl border border-gray-100 p-5"
      >
        <div class="flex items-center gap-3">
          <div :class="`text-2xl p-2 rounded-xl ${stat.bg}`">
            {{ stat.icon }}
          </div>
          <div>
            <p class="text-2xl font-bold text-gray-900">{{ stat.value }}</p>
            <p class="text-xs text-gray-500">{{ stat.label }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Reservas recientes -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50">
        <h2 class="font-semibold text-gray-900">Reservas recientes</h2>
        <RouterLink to="/mis-reservas" class="text-sm text-gray-500 hover:text-gray-900">
          Ver todas →
        </RouterLink>
      </div>

      <div v-if="loadingReservations" class="flex justify-center py-8">
        <AppSpinner />
      </div>

      <div v-else-if="recentReservations.length === 0"
           class="text-center py-10 text-gray-400 text-sm">
        No hay reservas recientes
      </div>

      <div v-else class="divide-y divide-gray-50">
        <div
          v-for="reservation in recentReservations"
          :key="reservation.id"
          class="px-6 py-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center
                        justify-center font-bold text-gray-600">
              {{ reservation.guest?.name?.charAt(0) }}
            </div>
            <div>
              <p class="font-medium text-gray-900 text-sm">
                {{ reservation.guest?.name }}
              </p>
              <p class="text-xs text-gray-400">
                {{ reservation.space?.name }} ·
                {{ formatDate(reservation.check_in) }} –
                {{ formatDate(reservation.check_out) }}
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <span class="font-semibold text-gray-900 text-sm">
              {{ formatPrice(reservation.total_price) }}
            </span>
            <AppBadge :variant="reservation.status">
              {{ statusLabel(reservation.status) }}
            </AppBadge>
          </div>
        </div>
      </div>
    </div>

    <!-- Mis espacios (resumen) -->
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-50">
        <h2 class="font-semibold text-gray-900">Mis espacios</h2>
        <RouterLink to="/host/espacios" class="text-sm text-gray-500 hover:text-gray-900">
          Gestionar →
        </RouterLink>
      </div>

      <div v-if="loadingSpaces" class="flex justify-center py-8">
        <AppSpinner />
      </div>

      <div v-else-if="mySpaces.length === 0"
           class="text-center py-10">
        <p class="text-gray-400 text-sm mb-4">No tienes espacios publicados</p>
        <RouterLink to="/host/espacios/nuevo">
          <AppButton size="sm">+ Publicar espacio</AppButton>
        </RouterLink>
      </div>

      <div v-else class="divide-y divide-gray-50">
        <div
          v-for="space in mySpaces"
          :key="space.id"
          class="px-6 py-4 flex items-center justify-between"
        >
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gray-100 rounded-xl flex items-center
                        justify-center text-xl overflow-hidden">
              <img
                v-if="space.image_url"
                :src="space.image_url"
                :alt="space.name"
                class="w-full h-full object-cover"
              >
              <span v-else>🏠</span>
            </div>
            <div>
              <p class="font-medium text-gray-900 text-sm">{{ space.name }}</p>
              <p class="text-xs text-gray-400">
                📍 {{ space.city }} ·
                {{ formatPrice(space.price_per_night) }}/noche
              </p>
            </div>
          </div>
          <div class="flex items-center gap-4 text-sm text-gray-500">
            <span v-if="space.reviews_avg_rating">
              ★ {{ Number(space.reviews_avg_rating).toFixed(1) }}
            </span>
            <AppBadge :variant="space.is_active ? 'success' : 'default'">
              {{ space.is_active ? 'Activo' : 'Inactivo' }}
            </AppBadge>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore }     from '../../stores/auth.store.js'
import { getHostStats, getMySpaces } from '../../services/space.service.js'
import { getReservations }  from '../../services/reservation.service.js'
import AppButton  from '../../components/ui/AppButton.vue'
import AppBadge   from '../../components/ui/AppBadge.vue'
import AppSpinner from '../../components/ui/AppSpinner.vue'

const authStore = useAuthStore()

const stats            = ref({})
const mySpaces         = ref([])
const recentReservations = ref([])
const loadingStats       = ref(true)
const loadingSpaces      = ref(true)
const loadingReservations = ref(true)

const statCards = computed(() => [
  {
    label: 'Espacios activos',
    value: stats.value.active_spaces ?? 0,
    icon: '🏠', bg: 'bg-blue-50',
  },
  {
    label: 'Ingresos totales',
    value: formatPrice(stats.value.total_revenue ?? 0),
    icon: '💰', bg: 'bg-emerald-50',
  },
  {
    label: 'Reservas pendientes',
    value: stats.value.pending_reservations ?? 0,
    icon: '⏳', bg: 'bg-amber-50',
  },
  {
    label: 'Rating promedio',
    value: stats.value.average_rating
      ? `${stats.value.average_rating} ★` : 'N/A',
    icon: '⭐', bg: 'bg-yellow-50',
  },
])

const statusLabel = (status) => ({
  pending:   'Pendiente',
  confirmed: 'Confirmada',
  completed: 'Completada',
  cancelled: 'Cancelada',
}[status] || status)

const formatDate = (date) =>
  new Date(date).toLocaleDateString('es-ES', {
    day: 'numeric', month: 'short',
  })

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)

onMounted(async () => {
  // Cargamos todo en paralelo con Promise.all
  await Promise.all([
    getHostStats().then(d => { stats.value = d }).finally(() => loadingStats.value = false),
    getMySpaces().then(d => { mySpaces.value = d.data?.slice(0, 5) }).finally(() => loadingSpaces.value = false),
    getReservations({ role: 'host', per_page: 5 })
      .then(d => { recentReservations.value = d.data })
      .finally(() => loadingReservations.value = false),
  ])
})
</script>