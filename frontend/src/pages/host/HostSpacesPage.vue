<template>
  <div class="max-w-5xl mx-auto px-4 py-8 space-y-6">

    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-gray-900">Mis Espacios</h1>
      <RouterLink to="/host/espacios/nuevo">
        <AppButton>+ Nuevo espacio</AppButton>
      </RouterLink>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <div v-else-if="spaces.length === 0" class="text-center py-16">
      <p class="text-5xl mb-4">🏠</p>
      <p class="font-medium text-gray-700 mb-2">No tienes espacios publicados</p>
      <p class="text-sm text-gray-400 mb-6">
        Publica tu primer espacio y empieza a recibir huéspedes
      </p>
      <RouterLink to="/host/espacios/nuevo">
        <AppButton>Publicar espacio</AppButton>
      </RouterLink>
    </div>

    <div v-else class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3.5 text-gray-500 font-medium">
              Espacio
            </th>
            <th class="text-left px-5 py-3.5 text-gray-500 font-medium">
              Precio
            </th>
            <th class="text-left px-5 py-3.5 text-gray-500 font-medium">
              Rating
            </th>
            <th class="text-left px-5 py-3.5 text-gray-500 font-medium">
              Estado
            </th>
            <th class="px-5 py-3.5" />
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr
            v-for="space in spaces"
            :key="space.id"
            class="hover:bg-gray-50 transition-colors"
          >
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden
                            flex items-center justify-center flex-shrink-0">
                  <img
                    v-if="space.image_url"
                    :src="space.image_url"
                    class="w-full h-full object-cover"
                  >
                  <span v-else>🏠</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900 truncate max-w-48">
                    {{ space.name }}
                  </p>
                  <p class="text-xs text-gray-400">📍 {{ space.city }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 font-medium text-gray-900">
              {{ formatPrice(space.price_per_night) }}/noche
            </td>
            <td class="px-5 py-3.5">
              <span v-if="space.reviews_avg_rating" class="text-yellow-500">
                ★ {{ Number(space.reviews_avg_rating).toFixed(1) }}
                <span class="text-gray-400 text-xs">
                  ({{ space.reviews_count }})
                </span>
              </span>
              <span v-else class="text-gray-400">Sin reseñas</span>
            </td>
            <td class="px-5 py-3.5">
              <AppBadge :variant="space.is_active ? 'success' : 'default'">
                {{ space.is_active ? 'Activo' : 'Inactivo' }}
              </AppBadge>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center justify-end gap-1">
                <RouterLink :to="`/espacios/${space.id}`">
                  <button class="p-1.5 text-gray-400 hover:text-blue-600
                                 hover:bg-blue-50 rounded-lg transition-colors">
                    👁️
                  </button>
                </RouterLink>
                <RouterLink :to="`/host/espacios/${space.id}/editar`">
                  <button class="p-1.5 text-gray-400 hover:text-gray-700
                                 hover:bg-gray-100 rounded-lg transition-colors">
                    ✏️
                  </button>
                </RouterLink>
                <button
                  @click="handleDelete(space)"
                  :disabled="deletingId === space.id"
                  class="p-1.5 text-gray-400 hover:text-red-600
                         hover:bg-red-50 rounded-lg transition-colors
                         disabled:opacity-40"
                >
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted }  from 'vue'
import { getMySpaces, deleteSpace } from '../../services/space.service.js'
import { useGlobalToast }  from '../../composables/useToast.js'
import AppButton  from '../../components/ui/AppButton.vue'
import AppBadge   from '../../components/ui/AppBadge.vue'
import AppSpinner from '../../components/ui/AppSpinner.vue'

const toast     = useGlobalToast()
const spaces    = ref([])
const loading   = ref(true)
const deletingId = ref(null)

const formatPrice = (price) =>
  new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'USD' })
    .format(price)

const fetchSpaces = async () => {
  loading.value = true
  try {
    const data  = await getMySpaces()
    spaces.value = data.data
  } finally {
    loading.value = false
  }
}

const handleDelete = async (space) => {
  if (!confirm(`¿Eliminar "${space.name}"?`)) return
  deletingId.value = space.id
  try {
    await deleteSpace(space.id)
    toast.success('Espacio eliminado')
    await fetchSpaces()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Error al eliminar')
  } finally {
    deletingId.value = null
  }
}

onMounted(fetchSpaces)
</script>