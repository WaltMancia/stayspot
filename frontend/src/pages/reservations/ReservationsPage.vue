<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-6">

    <h1 class="text-2xl font-bold text-gray-900">Mis Reservas</h1>

    <!-- Tabs guest/host -->
    <div
      v-if="authStore.isHost"
      class="flex gap-1 bg-gray-100 p-1 rounded-xl w-fit"
    >
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="activeTab = tab.value"
        :class="[
          'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
          activeTab === tab.value
            ? 'bg-white text-gray-900 shadow-sm'
            : 'text-gray-500 hover:text-gray-700',
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Cargando -->
    <div v-if="loading" class="flex justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <!-- Vacío -->
    <div v-else-if="reservations.length === 0" class="text-center py-16">
      <p class="text-5xl mb-4">📋</p>
      <p class="font-medium text-gray-700 mb-2">
        {{ activeTab === 'guest' ? 'Aún no tienes reservas' : 'No tienes reservas de huéspedes' }}
      </p>
      <p class="text-sm text-gray-400 mb-6">
        {{ activeTab === 'guest'
          ? 'Explora espacios y haz tu primera reserva'
          : 'Cuando alguien reserve tu espacio, aparecerá aquí' }}
      </p>
      <RouterLink v-if="activeTab === 'guest'" to="/espacios">
        <AppButton>Explorar espacios</AppButton>
      </RouterLink>
    </div>

    <!-- Lista de reservas -->
    <div v-else class="space-y-3">
      <ReservationCard
        v-for="reservation in reservations"
        :key="reservation.id"
        :reservation="reservation"
        :role="activeTab"
        @cancelled="fetchReservations"
        @confirmed="fetchReservations"
      />
    </div>

    <!-- Paginación -->
    <div
      v-if="pagination?.last_page > 1"
      class="flex items-center justify-center gap-2"
    >
      <AppButton
        variant="secondary" size="sm"
        :disabled="page === 1"
        @click="changePage(page - 1)"
      >
        ← Anterior
      </AppButton>
      <span class="text-sm text-gray-500">
        {{ page }} / {{ pagination.last_page }}
      </span>
      <AppButton
        variant="secondary" size="sm"
        :disabled="page >= pagination.last_page"
        @click="changePage(page + 1)"
      >
        Siguiente →
      </AppButton>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useAuthStore }          from '../../stores/auth.store.js'
import { getReservations }       from '../../services/reservation.service.js'
import ReservationCard from '../../components/reservation/ReservationCard.vue'
import AppButton       from '../../components/ui/AppButton.vue'
import AppSpinner      from '../../components/ui/AppSpinner.vue'

const authStore = useAuthStore()

const reservations = ref([])
const pagination   = ref(null)
const loading      = ref(false)
const page         = ref(1)
const activeTab    = ref('guest')

const tabs = [
  { value: 'guest', label: 'Como huésped' },
  { value: 'host',  label: 'Como anfitrión' },
]

const fetchReservations = async () => {
  loading.value = true
  try {
    const data = await getReservations({
      role: activeTab.value,
      page: page.value,
    })
    reservations.value = data.data
    pagination.value   = data.meta
  } finally {
    loading.value = false
  }
}

const changePage = (newPage) => {
  page.value = newPage
  fetchReservations()
}

// Re-fetcha cuando cambia el tab
watch(activeTab, () => {
  page.value = 1
  fetchReservations()
})

onMounted(fetchReservations)
</script>