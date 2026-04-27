<template>
  <div class="max-w-7xl mx-auto px-4 py-8 space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Espacios</h1>
        <p v-if="spacesStore.pagination" class="text-sm text-gray-500 mt-0.5">
          {{ spacesStore.pagination.total }} espacios disponibles
        </p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-2xl border border-gray-100 p-4">
      <div class="flex flex-wrap gap-3">
        <!-- Búsqueda por ciudad -->
        <div class="relative flex-1 min-w-48">
          <input
            v-model="spacesStore.filters.city"
            @keyup.enter="fetchWithFilters"
            placeholder="Ciudad..."
            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl
                   text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
          >
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
            📍
          </span>
        </div>

        <!-- Precio min/max -->
        <input
          v-model="spacesStore.filters.price_min"
          type="number"
          placeholder="Precio mín"
          class="w-28 px-3 py-2 border border-gray-200 rounded-xl text-sm
                 focus:outline-none focus:ring-2 focus:ring-gray-900"
        >
        <input
          v-model="spacesStore.filters.price_max"
          type="number"
          placeholder="Precio máx"
          class="w-28 px-3 py-2 border border-gray-200 rounded-xl text-sm
                 focus:outline-none focus:ring-2 focus:ring-gray-900"
        >

        <!-- Ordenar -->
        <select
          v-model="spacesStore.filters.sort"
          @change="fetchWithFilters"
          class="px-3 py-2 border border-gray-200 rounded-xl text-sm
                 focus:outline-none focus:ring-2 focus:ring-gray-900 bg-white"
        >
          <option value="">Más recientes</option>
          <option value="price_asc">Precio: menor a mayor</option>
          <option value="price_desc">Precio: mayor a menor</option>
          <option value="rating">Mejor valorados</option>
        </select>

        <AppButton size="sm" @click="fetchWithFilters">
          Buscar
        </AppButton>

        <AppButton
          v-if="hasActiveFilters"
          size="sm"
          variant="ghost"
          @click="clearFilters"
        >
          ✕ Limpiar
        </AppButton>
      </div>
    </div>

    <!-- Grid de espacios -->
    <div v-if="spacesStore.loading" class="flex justify-center py-20">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="spacesStore.spaces.length === 0" class="text-center py-20">
      <p class="text-5xl mb-4">🔍</p>
      <p class="text-gray-700 font-medium mb-2">No encontramos espacios</p>
      <p class="text-gray-400 text-sm mb-6">Prueba con otros filtros</p>
      <AppButton variant="secondary" @click="clearFilters">
        Limpiar filtros
      </AppButton>
    </div>

    <div v-else>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <SpaceCard
          v-for="space in spacesStore.spaces"
          :key="space.id"
          :space="space"
        />
      </div>

      <!-- Paginación -->
      <div
        v-if="spacesStore.pagination?.last_page > 1"
        class="flex items-center justify-center gap-2 mt-8"
      >
        <AppButton
          variant="secondary" size="sm"
          :disabled="spacesStore.filters.page === 1"
          @click="changePage(spacesStore.filters.page - 1)"
        >
          ← Anterior
        </AppButton>
        <span class="text-sm text-gray-500">
          {{ spacesStore.filters.page }} / {{ spacesStore.pagination.last_page }}
        </span>
        <AppButton
          variant="secondary" size="sm"
          :disabled="spacesStore.filters.page >= spacesStore.pagination.last_page"
          @click="changePage(spacesStore.filters.page + 1)"
        >
          Siguiente →
        </AppButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useRoute }    from 'vue-router'
import { useSpacesStore } from '../../stores/spaces.store.js'
import SpaceCard  from '../../components/space/SpaceCard.vue'
import AppButton  from '../../components/ui/AppButton.vue'
import AppSpinner from '../../components/ui/AppSpinner.vue'

const spacesStore = useSpacesStore()
const route       = useRoute()

const hasActiveFilters = computed(() =>
  spacesStore.filters.city ||
  spacesStore.filters.price_min ||
  spacesStore.filters.price_max
)

const fetchWithFilters = async () => {
  spacesStore.filters.page = 1
  await spacesStore.fetchSpaces()
}

const clearFilters = async () => {
  spacesStore.resetFilters()
  await spacesStore.fetchSpaces()
}

const changePage = async (page) => {
  spacesStore.filters.page = page
  await spacesStore.fetchSpaces()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(async () => {
  // Si viene con query params (ej: desde la búsqueda del hero)
  if (route.query.city) {
    spacesStore.filters.city = route.query.city
  }
  await spacesStore.fetchSpaces()
})
</script>