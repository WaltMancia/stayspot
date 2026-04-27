<template>
  <div class="space-y-20">

    <!-- Hero -->
    <section class="bg-gray-900 text-white py-24 px-4">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-5xl font-bold leading-tight mb-4">
          Encuentra tu espacio<br />perfecto en Guatemala
        </h1>
        <p class="text-gray-400 text-xl mb-10">
          Casas coloniales, cabañas en el lago y apartamentos modernos.
          Más de {{ stats.total_spaces }} espacios te esperan.
        </p>

        <!-- Búsqueda rápida -->
        <div class="bg-white rounded-2xl p-2 flex gap-2 max-w-xl mx-auto shadow-xl">
          <input
            v-model="quickSearch.city"
            type="text"
            placeholder="¿A dónde vas?"
            class="flex-1 px-4 py-2.5 text-gray-900 text-sm focus:outline-none"
          >
          <AppButton @click="goToSearch">
            Buscar espacios
          </AppButton>
        </div>
      </div>
    </section>

    <!-- Espacios destacados -->
    <section class="max-w-7xl mx-auto px-4">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Espacios populares</h2>
        <RouterLink
          to="/espacios"
          class="text-sm text-gray-500 hover:text-gray-900 transition-colors"
        >
          Ver todos →
        </RouterLink>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <AppSpinner size="lg" />
      </div>

      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
      >
        <SpaceCard
          v-for="space in featuredSpaces"
          :key="space.id"
          :space="space"
        />
      </div>
    </section>

    <!-- Por qué StaySpot -->
    <section class="bg-white py-16 px-4">
      <div class="max-w-5xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-900 text-center mb-10">
          ¿Por qué elegir StaySpot?
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div
            v-for="feature in features"
            :key="feature.title"
            class="text-center"
          >
            <div class="text-4xl mb-4">{{ feature.icon }}</div>
            <h3 class="font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
            <p class="text-sm text-gray-500">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getSpaces } from '../services/space.service.js'
import SpaceCard  from '../components/space/SpaceCard.vue'
import AppButton  from '../components/ui/AppButton.vue'
import AppSpinner from '../components/ui/AppSpinner.vue'

const router        = useRouter()
const featuredSpaces = ref([])
const loading        = ref(true)
const stats          = ref({ total_spaces: '150+' })
const quickSearch    = reactive({ city: '' })

const features = [
  {
    icon: '🔒',
    title: 'Pagos seguros',
    description: 'Todos los pagos están protegidos con cifrado SSL y procesados por Stripe.',
  },
  {
    icon: '⭐',
    title: 'Espacios verificados',
    description: 'Cada anfitrión y espacio pasa por nuestro proceso de verificación.',
  },
  {
    icon: '💬',
    title: 'Soporte 24/7',
    description: 'Estamos disponibles en todo momento para ayudarte en tu estadía.',
  },
]

const goToSearch = () => {
  router.push({
    path: '/espacios',
    query: quickSearch.city ? { city: quickSearch.city } : {},
  })
}

onMounted(async () => {
  try {
    const data = await getSpaces({ per_page: 6, sort: 'rating' })
    featuredSpaces.value = data.data
  } finally {
    loading.value = false
  }
})
</script>