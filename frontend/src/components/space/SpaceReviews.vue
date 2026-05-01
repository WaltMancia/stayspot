<template>
  <div class="space-y-5">

    <!-- Resumen de rating -->
    <div
      v-if="reviews.length > 0"
      class="flex items-center gap-6 p-5 bg-gray-50 rounded-2xl"
    >
      <!-- Número grande -->
      <div class="text-center">
        <p class="text-5xl font-bold text-gray-900">{{ avgRating }}</p>
        <div class="flex text-yellow-400 justify-center my-1">
          <span v-for="n in 5" :key="n">
            {{ n <= Math.round(avgRating) ? '★' : '☆' }}
          </span>
        </div>
        <p class="text-sm text-gray-500">{{ reviews.length }} reseñas</p>
      </div>

      <!-- Barras de distribución -->
      <div class="flex-1 space-y-1.5">
        <div
          v-for="star in [5, 4, 3, 2, 1]"
          :key="star"
          class="flex items-center gap-2 text-sm"
        >
          <span class="text-gray-500 w-4 text-right">{{ star }}</span>
          <span class="text-yellow-400 text-xs">★</span>
          <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
            <div
              class="bg-yellow-400 h-2 rounded-full transition-all duration-500"
              :style="{ width: `${ratingPercent(star)}%` }"
            />
          </div>
          <span class="text-gray-400 text-xs w-8">
            {{ ratingCount(star) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Lista de reseñas -->
    <div v-if="reviews.length === 0" class="text-center py-8 text-gray-400">
      <p class="text-3xl mb-2">💬</p>
      <p class="text-sm">Sé el primero en dejar una reseña</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="review in visibleReviews"
        :key="review.id"
        class="border-b border-gray-100 pb-4 last:border-0"
      >
        <div class="flex items-start gap-3">
          <div class="w-10 h-10 bg-gradient-to-br from-gray-200 to-gray-300
                      rounded-full flex items-center justify-center
                      font-semibold text-gray-600 flex-shrink-0">
            {{ review.guest?.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="font-medium text-gray-900 text-sm">
                {{ review.guest?.name }}
              </p>
              <p class="text-xs text-gray-400">
                {{ formatDate(review.created_at) }}
              </p>
            </div>
            <!-- Estrellas -->
            <div class="flex text-yellow-400 text-sm my-1">
              <span v-for="n in review.rating" :key="n">★</span>
              <span
                v-for="n in (5 - review.rating)"
                :key="`e-${n}`"
                class="text-gray-200"
              >★</span>
            </div>
            <p class="text-sm text-gray-600 leading-relaxed">
              {{ review.comment }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Ver más / menos -->
    <button
      v-if="reviews.length > 3"
      @click="showAll = !showAll"
      class="text-sm font-medium text-gray-900 underline underline-offset-2
             hover:text-gray-600 transition-colors"
    >
      {{ showAll
        ? 'Ver menos'
        : `Ver las ${reviews.length - 3} reseñas restantes` }}
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  reviews: { type: Array, default: () => [] },
})

const showAll = ref(false)

const visibleReviews = computed(() =>
  showAll.value ? props.reviews : props.reviews.slice(0, 3)
)

const avgRating = computed(() => {
  if (!props.reviews.length) return 0
  const sum = props.reviews.reduce((acc, r) => acc + r.rating, 0)
  return (sum / props.reviews.length).toFixed(1)
})

const ratingCount = (stars) =>
  props.reviews.filter(r => r.rating === stars).length

const ratingPercent = (stars) => {
  if (!props.reviews.length) return 0
  return Math.round((ratingCount(stars) / props.reviews.length) * 100)
}

const formatDate = (date) =>
  new Date(date).toLocaleDateString('es-ES', {
    month: 'long', year: 'numeric',
  })
</script>