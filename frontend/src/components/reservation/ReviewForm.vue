<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">

    <!-- Rating con estrellas -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Calificación
      </label>
      <div class="flex gap-1">
        <button
          v-for="star in 5"
          :key="star"
          type="button"
          @click="form.rating = star"
          @mouseover="hoveredStar = star"
          @mouseleave="hoveredStar = 0"
          class="text-3xl transition-transform hover:scale-110"
        >
          <span :class="star <= (hoveredStar || form.rating)
            ? 'text-yellow-400' : 'text-gray-200'">
            ★
          </span>
        </button>
      </div>
      <p class="text-xs text-gray-400 mt-1">
        {{ ratingLabels[form.rating] || 'Selecciona una calificación' }}
      </p>
    </div>

    <!-- Comentario -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1.5">
        Comentario
      </label>
      <textarea
        v-model="form.comment"
        rows="4"
        placeholder="Cuéntanos sobre tu experiencia..."
        class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm
               focus:outline-none focus:ring-2 focus:ring-gray-900 resize-none"
      />
    </div>

    <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

    <AppButton
      type="submit"
      :loading="loading"
      :disabled="!form.rating"
    >
      Publicar reseña
    </AppButton>
  </form>
</template>

<script setup>
import { ref, reactive } from 'vue'
import api from '../../services/api.js'
import AppButton from '../ui/AppButton.vue'

const props = defineProps({
  reservationId: { type: Number, required: true },
})
const emit = defineEmits(['submitted'])

const form = reactive({ rating: 0, comment: '' })
const loading     = ref(false)
const error       = ref('')
const hoveredStar = ref(0)

const ratingLabels = {
  1: 'Muy malo',
  2: 'Malo',
  3: 'Regular',
  4: 'Bueno',
  5: 'Excelente',
}

const handleSubmit = async () => {
  if (!form.rating) return
  loading.value = true
  error.value   = ''
  try {
    await api.post(
      `/reservations/${props.reservationId}/review`,
      form
    )
    emit('submitted')
  } catch (e) {
    error.value = e.response?.data?.message || 'Error al publicar la reseña'
  } finally {
    loading.value = false
  }
}
</script>