<template>
  <div class="max-w-5xl mx-auto px-4 py-8 space-y-8">

    <!-- Botón volver -->
    <button @click="$router.push('/espacios')" class="flex items-center gap-2 text-sm text-gray-500
             hover:text-gray-900 transition-colors">
      ← Volver a espacios
    </button>

    <!-- Estado de carga -->
    <div v-if="loading" class="flex justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <template v-else-if="space">

      <!-- Imagen principal -->
      <div class="aspect-video rounded-3xl overflow-hidden bg-gray-100">
        <img v-if="space.image_url" :src="space.image_url" :alt="space.name" class="w-full h-full object-cover">
        <div v-else class="w-full h-full flex items-center justify-center">
          <span class="text-8xl">🏠</span>
        </div>
      </div>

      <!-- Contenido en dos columnas -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        <!-- Columna izquierda — Información -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Header del espacio -->
          <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wide mb-1">
              📍 {{ space.city }}, {{ space.country }}
            </p>
            <h1 class="text-3xl font-bold text-gray-900 mb-3">
              {{ space.name }}
            </h1>

            <!-- Stats rápidos -->
            <div class="flex items-center gap-4 text-sm text-gray-600 flex-wrap">
              <span>👥 Hasta {{ space.max_guests }} huéspedes</span>
              <span>🛏️ {{ space.bedrooms }} habitaciones</span>
              <span>🚿 {{ space.bathrooms }} baños</span>
              <div v-if="space.reviews_avg_rating" class="flex items-center gap-1">
                <span class="text-yellow-400">★</span>
                <span class="font-semibold text-gray-900">
                  {{ Number(space.reviews_avg_rating).toFixed(1) }}
                </span>
                <span class="text-gray-400">
                  ({{ space.reviews_count }} reseñas)
                </span>
              </div>
            </div>
          </div>

          <!-- Anfitrión -->
          <div v-if="space.host" class="flex items-center gap-4 p-4 bg-gray-50 rounded-2xl">
            <div class="w-14 h-14 bg-gray-200 rounded-full flex
                        items-center justify-center text-2xl flex-shrink-0">
              {{ space.host.name?.charAt(0) }}
            </div>
            <div>
              <p class="font-semibold text-gray-900">{{ space.host.name }}</p>
              <p class="text-sm text-gray-500">Anfitrión desde {{ hostSince }}</p>
            </div>
          </div>

          <!-- Descripción -->
          <div>
            <h2 class="font-semibold text-gray-900 text-lg mb-3">
              Descripción
            </h2>
            <p class="text-gray-600 leading-relaxed whitespace-pre-line">
              {{ space.description || 'Sin descripción disponible.' }}
            </p>
          </div>

          <!-- Amenidades -->
          <div v-if="space.amenities?.length">
            <h2 class="font-semibold text-gray-900 text-lg mb-3">
              Servicios y amenidades
            </h2>
            <div class="grid grid-cols-2 gap-2">
              <div v-for="amenity in space.amenities" :key="amenity"
                class="flex items-center gap-2 text-sm text-gray-600">
                <span class="text-emerald-500">✓</span>
                {{ amenity }}
              </div>
            </div>
          </div>

          <!-- Reseñas -->
          <div>
            <h2 class="font-semibold text-gray-900 text-lg mb-4">Reseñas</h2>
            <SpaceReviews :reviews="space.reviews || []" />
          </div>
        </div>

        <!-- Columna derecha — Formulario de reserva -->
        <div class="lg:col-span-1">
          <div class="sticky top-6">
            <ReservationWidget :space="space" @reserved="handleReserved" />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSpacesStore } from '../../stores/spaces.store.js'
import ReservationWidget from '../../components/reservation/ReservationWidget.vue'
import AppSpinner from '../../components/ui/AppSpinner.vue'
import { useGlobalToast } from '../../composables/useToast.js'
import SpaceReviews from '../../components/space/SpaceReviews.vue'

const route = useRoute()
const router = useRouter()
const store = useSpacesStore()
const toast = useGlobalToast()

const space = ref(null)
const loading = ref(true)

const hostSince = computed(() => {
  if (!space.value?.host?.created_at) return ''
  return new Date(space.value.host.created_at)
    .toLocaleDateString('es-ES', { year: 'numeric', month: 'long' })
})

const formatDate = (dateStr) =>
  new Date(dateStr).toLocaleDateString('es-ES', {
    year: 'numeric', month: 'long', day: 'numeric',
  })

const handleReserved = (reservation) => {
  toast.success('¡Reserva creada! Procede al pago.')
  router.push(`/mis-reservas/${reservation.id}`)
}

onMounted(async () => {
  try {
    await store.fetchSpace(route.params.id)
    space.value = store.current
  } catch {
    toast.error('Espacio no encontrado')
    router.push('/espacios')
  } finally {
    loading.value = false
  }
})
</script>