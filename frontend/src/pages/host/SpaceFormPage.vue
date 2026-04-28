<template>
  <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

    <button
      @click="$router.back()"
      class="flex items-center gap-2 text-sm text-gray-500
             hover:text-gray-900 transition-colors"
    >
      ← Volver
    </button>

    <div>
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEditing ? 'Editar espacio' : 'Publicar espacio' }}
      </h1>
      <p class="text-sm text-gray-500 mt-0.5">
        {{ isEditing
          ? 'Actualiza la información de tu espacio'
          : 'Completa la información para publicar' }}
      </p>
    </div>

    <div v-if="initialLoading" class="flex justify-center py-16">
      <AppSpinner size="lg" />
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-5">

      <!-- Información básica -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Información básica</h2>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Nombre del espacio *
          </label>
          <input
            v-model="form.name"
            required
            maxlength="150"
            placeholder="Casa Colonial en Antigua..."
            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                   text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
          >
          <p v-if="errors.name" class="text-xs text-red-600 mt-1">
            {{ errors.name[0] }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Ciudad *
          </label>
          <input
            v-model="form.city"
            required
            placeholder="Antigua Guatemala"
            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                   text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Descripción
          </label>
          <textarea
            v-model="form.description"
            rows="4"
            placeholder="Describe tu espacio..."
            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm
                   focus:outline-none focus:ring-2 focus:ring-gray-900 resize-none"
          />
        </div>
      </div>

      <!-- Precio y capacidad -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Precio y capacidad</h2>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Precio por noche (USD) *
            </label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2
                           text-gray-400 font-medium">$</span>
              <input
                v-model="form.price_per_night"
                type="number"
                required
                min="1"
                step="0.01"
                placeholder="0.00"
                class="w-full pl-8 pr-4 py-2.5 border border-gray-200
                       rounded-xl text-sm focus:outline-none focus:ring-2
                       focus:ring-gray-900"
              >
            </div>
            <p v-if="errors.price_per_night" class="text-xs text-red-600 mt-1">
              {{ errors.price_per_night[0] }}
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Max. huéspedes *
            </label>
            <input
              v-model="form.max_guests"
              type="number"
              required
              min="1"
              max="20"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Habitaciones *
            </label>
            <input
              v-model="form.bedrooms"
              type="number"
              required
              min="1"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">
              Baños *
            </label>
            <input
              v-model="form.bathrooms"
              type="number"
              required
              min="1"
              class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                     text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
            >
          </div>
        </div>
      </div>

      <!-- Amenidades -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Amenidades</h2>
        <div class="grid grid-cols-2 gap-2">
          <label
            v-for="amenity in amenityOptions"
            :key="amenity"
            class="flex items-center gap-2 text-sm text-gray-600
                   cursor-pointer hover:text-gray-900"
          >
            <input
              type="checkbox"
              :value="amenity"
              v-model="form.amenities"
              class="rounded"
            >
            {{ amenity }}
          </label>
        </div>
      </div>

      <!-- Imagen URL -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Imagen</h2>
        <div v-if="form.image_url" class="w-full aspect-video rounded-xl
             overflow-hidden bg-gray-100 mb-3">
          <img
            :src="form.image_url"
            class="w-full h-full object-cover"
            @error="form.image_url = ''"
          >
        </div>
        <input
          v-model="form.image_url"
          type="url"
          placeholder="https://ejemplo.com/imagen.jpg"
          class="w-full px-4 py-2.5 border border-gray-200 rounded-xl
                 text-sm focus:outline-none focus:ring-2 focus:ring-gray-900"
        >
        <p class="text-xs text-gray-400">
          Añade una URL de imagen (JPG, PNG, WebP).
          Puedes usar imágenes de Unsplash.com.
        </p>
      </div>

      <!-- Botones -->
      <div class="flex gap-3">
        <AppButton
          type="submit"
          :loading="loading"
          size="lg"
          class="flex-1"
        >
          {{ isEditing ? 'Guardar cambios' : 'Publicar espacio' }}
        </AppButton>
        <AppButton
          type="button"
          variant="secondary"
          size="lg"
          @click="$router.back()"
        >
          Cancelar
        </AppButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter }     from 'vue-router'
import { getSpace, createSpace, updateSpace } from '../../services/space.service.js'
import { useGlobalToast } from '../../composables/useToast.js'
import AppButton          from '../../components/ui/AppButton.vue'
import AppSpinner         from '../../components/ui/AppSpinner.vue'

const route  = useRoute()
const router = useRouter()
const toast  = useGlobalToast()

const isEditing    = computed(() => !!route.params.id)
const loading      = ref(false)
const initialLoading = ref(isEditing.value)
const errors       = ref({})

const form = reactive({
  name: '', city: '', address: '',
  description: '', price_per_night: '',
  max_guests: 2, bedrooms: 1, bathrooms: 1,
  amenities: [], image_url: '',
})

const amenityOptions = [
  'WiFi', 'Piscina', 'Estacionamiento', 'Cocina equipada',
  'Aire acondicionado', 'TV por cable', 'Lavadora',
  'Balcón', 'Jardín', 'Chimenea', 'Jacuzzi',
  'Desayuno incluido', 'Vista al lago', 'Acceso a la playa',
]

const handleSubmit = async () => {
  loading.value = true
  errors.value  = {}
  try {
    const payload = {
      ...form,
      price_per_night: parseFloat(form.price_per_night),
      max_guests:      parseInt(form.max_guests),
      bedrooms:        parseInt(form.bedrooms),
      bathrooms:       parseInt(form.bathrooms),
    }

    if (isEditing.value) {
      await updateSpace(route.params.id, payload)
      toast.success('Espacio actualizado')
    } else {
      await createSpace(payload)
      toast.success('Espacio publicado')
    }
    router.push('/host/espacios')
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
    toast.error(e.response?.data?.message || 'Error al guardar')
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  if (!isEditing.value) return
  try {
    const data = await getSpace(route.params.id)
    const space = data.data
    Object.assign(form, {
      name:            space.name,
      city:            space.city,
      address:         space.address || '',
      description:     space.description || '',
      price_per_night: space.price_per_night,
      max_guests:      space.max_guests,
      bedrooms:        space.bedrooms,
      bathrooms:       space.bathrooms,
      amenities:       space.amenities || [],
      image_url:       space.image_url || '',
    })
  } catch {
    toast.error('No se pudo cargar el espacio')
    router.push('/host/espacios')
  } finally {
    initialLoading.value = false
  }
})
</script>