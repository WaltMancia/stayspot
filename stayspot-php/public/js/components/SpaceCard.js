// En Vue sin build tools, definimos componentes como objetos JavaScript
// Cuando usemos Vite, serán archivos .vue (Single File Components)
const SpaceCard = {

    // Props — equivale a las props de React
    // Pero Vue te permite tiparlo y validarlo sin TypeScript
    props: {
        space: {
            type: Object,
            required: true,
        },
        // Prop con valor por defecto
        featured: {
            type: Boolean,
            default: false,
        },
    },

    // Emits — declara los eventos que este componente puede emitir
    // Equivale al callback pattern de React: onReserve={handleReserve}
    // En Vue es más explícito: definimos QUÉ eventos emitimos
    emits: ['reserve', 'view-details'],

    // setup() es el corazón de la Composition API
    // Equivale al cuerpo de un componente funcional de React
    setup(props, { emit }) {
        // Composition API — importamos desde Vue global en CDN
        const { computed, ref } = Vue

        // Estado local del componente
        const hovering = ref(false)
        const addingToFavorites = ref(false)

        // Computed — valor derivado de los props
        // Se recalcula automáticamente cuando cambia props.space.price
        const formattedPrice = computed(() =>
            new Intl.NumberFormat('es-GT', {
                style: 'currency',
                currency: 'USD',
            }).format(props.space.price_per_night)
        )

        const stockBadge = computed(() => {
            if (props.space.available_spots === 0)
                return { text: 'Sin disponibilidad', class: 'bg-red-100 text-red-700' }
            if (props.space.available_spots <= 3)
                return {
                    text: `¡Solo ${props.space.available_spots} disponibles!`,
                    class: 'bg-amber-100 text-amber-700'
                }
            return null
        })

        // Métodos — funciones normales de JavaScript
        const handleReserve = () => {
            // emit dispara un evento al componente padre
            // Equivale a props.onReserve(space) en React
            emit('reserve', props.space)
        }

        const handleViewDetails = () => {
            emit('view-details', props.space)
        }

        const toggleFavorite = async () => {
            addingToFavorites.value = true
            // Simulamos una llamada async
            await new Promise(resolve => setTimeout(resolve, 500))
            addingToFavorites.value = false
            // En el proyecto real llamaría a la API
        }

        // Lo que retornamos está disponible en el template
        return {
            hovering,
            addingToFavorites,
            formattedPrice,
            stockBadge,
            handleReserve,
            handleViewDetails,
            toggleFavorite,
        }
    },

    // Template — el JSX de Vue pero en HTML real
    template: `
        <div
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden
                   shadow-sm transition-all duration-300 cursor-pointer"
            :class="{ 'shadow-lg -translate-y-1': hovering, 'ring-2 ring-blue-500': featured }"
            @mouseenter="hovering = true"
            @mouseleave="hovering = false"
        >
            <!-- Imagen -->
            <div class="relative aspect-video overflow-hidden bg-gray-100">
                <img
                    v-if="space.image_url"
                    :src="space.image_url"
                    :alt="space.name"
                    class="w-full h-full object-cover transition-transform duration-500"
                    :class="{ 'scale-110': hovering }"
                    @click="handleViewDetails"
                >
                <div
                    v-else
                    class="w-full h-full flex items-center justify-center"
                    @click="handleViewDetails"
                >
                    <span class="text-4xl">🏠</span>
                </div>

                <!-- Badge de disponibilidad -->
                <div
                    v-if="stockBadge"
                    class="absolute top-3 left-3 px-2.5 py-1 rounded-full
                           text-xs font-semibold"
                    :class="stockBadge.class"
                >
                    {{ stockBadge.text }}
                </div>

                <!-- Botón favorito -->
                <button
                    class="absolute top-3 right-3 w-9 h-9 bg-white/90
                           backdrop-blur-sm rounded-full flex items-center
                           justify-center shadow-sm hover:bg-white
                           transition-colors"
                    @click.stop="toggleFavorite"
                    :disabled="addingToFavorites"
                >
                    <!-- .stop en @click evita que el click suba al padre -->
                    <!-- Equivale a e.stopPropagation() -->
                    <span>{{ addingToFavorites ? '⏳' : '🤍' }}</span>
                </button>
            </div>

            <!-- Contenido -->
            <div class="p-4" @click="handleViewDetails">
                <!-- Ubicación -->
                <p class="text-xs text-gray-500 font-medium uppercase
                          tracking-wide mb-1">
                    📍 {{ space.city }}
                </p>

                <!-- Nombre -->
                <h3 class="font-semibold text-gray-900 mb-1 line-clamp-1">
                    {{ space.name }}
                </h3>

                <!-- Host -->
                <p class="text-sm text-gray-500 mb-3">
                    por {{ space.host_name || 'Anfitrión' }}
                </p>

                <!-- Rating -->
                <div class="flex items-center gap-1 mb-3" v-if="space.rating">
                    <span class="text-yellow-400 text-sm">★</span>
                    <span class="text-sm font-medium text-gray-900">
                        {{ space.rating.toFixed(1) }}
                    </span>
                    <span class="text-sm text-gray-400">
                        ({{ space.reviews_count || 0 }} reseñas)
                    </span>
                </div>

                <!-- Precio y botón -->
                <div class="flex items-center justify-between mt-2">
                    <div>
                        <span class="text-xl font-bold text-gray-900">
                            {{ formattedPrice }}
                        </span>
                        <span class="text-sm text-gray-400"> / noche</span>
                    </div>
                    <button
                        class="px-4 py-2 bg-gray-900 text-white text-sm
                               font-medium rounded-xl hover:bg-gray-700
                               transition-colors disabled:opacity-50"
                        :disabled="space.available_spots === 0"
                        @click.stop="handleReserve"
                    >
                        {{ space.available_spots === 0 ? 'Agotado' : 'Reservar' }}
                    </button>
                </div>
            </div>
        </div>
    `,
}
