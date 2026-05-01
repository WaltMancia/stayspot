<template>
  <div class="group bg-white rounded-2xl border border-gray-100 overflow-hidden
           shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer"
    @click="$router.push(`/espacios/${space.id}`)">
    <!-- Imagen -->
    <div class="relative aspect-video overflow-hidden bg-gray-100">
      <img v-if="space.image_url" :src="space.image_url" :alt="space.name" class="w-full h-full object-cover group-hover:scale-105
               transition-transform duration-500">
      <div v-else class="w-full h-full flex items-center justify-center">
        <span class="text-5xl">🏠</span>
      </div>

      <!-- Badge de disponibilidad -->
      <AppBadge v-if="space.available_spots === 0" variant="danger" class="absolute top-3 left-3">
        Sin disponibilidad
      </AppBadge>

      <!-- Rating -->
      <div class="flex items-center justify-between">
        <div>
          <span class="text-xl font-bold text-gray-900">
            {{ formatPrice(space.price_per_night) }}
          </span>
          <span class="text-sm text-gray-400"> / noche</span>
        </div>

        <div v-if="space.reviews_avg_rating" class="flex items-center gap-1 text-sm">
          <span class="text-yellow-400">★</span>
          <span class="font-semibold text-gray-900">
            {{ Number(space.reviews_avg_rating).toFixed(1) }}
          </span>
          <span class="text-gray-400 text-xs">({{ space.reviews_count }})</span>
        </div>
        <span v-else class="text-xs text-gray-400">Sin reseñas</span>
      </div>
    </div>

    <!-- Contenido -->
    <div class="p-4">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">
        📍 {{ space.city }}
      </p>

      <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1
                 group-hover:text-gray-600 transition-colors">
        {{ space.name }}
      </h3>

      <p v-if="space.host" class="text-sm text-gray-400 mb-3">
        por {{ space.host.name }}
      </p>

      <div class="flex items-center justify-between">
        <div>
          <span class="text-xl font-bold text-gray-900">
            {{ formatPrice(space.price_per_night) }}
          </span>
          <span class="text-sm text-gray-400"> / noche</span>
        </div>

        <div class="flex items-center gap-1 text-sm text-gray-500">
          <span>👥</span>
          <span>{{ space.max_guests }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppBadge from '../ui/AppBadge.vue'

defineProps({
  space: { type: Object, required: true },
})

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)
</script>