// En Vue sin build tools, accedemos a la Composition API desde Vue global
const { ref, reactive, computed, watch, onMounted } = Vue

const App = {
    setup() {

        // ── Estado de la aplicación ──────────────────────────
        // reactive() para objetos — equivale a useState con objeto en React
        const state = reactive({
            espacios: [],
            cargando: false,
            error: null,
            busqueda: '',
            filtros: {
                ciudad: '',
                precioMin: '',
                precioMax: '',
                huespedes: '',
            },
            paginacion: {
                pagina: 1,
                porPagina: 6,
                totalPaginas: 1,
            },
        })

        // ref() para valores primitivos
        const mostrarModal = ref(false)
        const espacioSeleccionado = ref(null)
        const reservasDelUsuario = ref([])

        // Datos demo — los reemplazaremos con llamadas a la API en el proyecto real
        const espaciosDemo = [
            {
                id: 1,
                name: 'Casa Colonial en Antigua',
                city: 'Antigua Guatemala',
                price_per_night: 150,
                max_guests: 4,
                rating: 4.8,
                reviews_count: 24,
                available_spots: 2,
                host_name: 'Carlos Mendoza',
                image_url: 'https://images.unsplash.com/photo-1582268611958' +
                    '-ebfd161ef9cf?w=600&h=400&fit=crop',
            },
            {
                id: 2,
                name: 'Apartamento Moderno Zona 10',
                city: 'Ciudad de Guatemala',
                price_per_night: 80,
                max_guests: 2,
                rating: 4.5,
                reviews_count: 15,
                available_spots: 5,
                host_name: 'María López',
                image_url: 'https://images.unsplash.com/photo-1502672260266' +
                    '-1c1ef2d93688?w=600&h=400&fit=crop',
            },
            {
                id: 3,
                name: 'Cabaña en Lago Atitlán',
                city: 'Sololá',
                price_per_night: 200,
                max_guests: 6,
                rating: 4.9,
                reviews_count: 38,
                available_spots: 1,
                host_name: 'Ana García',
                image_url: 'https://images.unsplash.com/photo-1449158743715' +
                    '-0abbc851b579?w=600&h=400&fit=crop',
            },
            {
                id: 4,
                name: 'Villa con Piscina',
                city: 'Panajachel',
                price_per_night: 350,
                max_guests: 8,
                rating: 4.7,
                reviews_count: 12,
                available_spots: 3,
                host_name: 'Roberto Sánchez',
                image_url: 'https://images.unsplash.com/photo-1564013799919' +
                    '-ab600027ffc6?w=600&h=400&fit=crop',
            },
            {
                id: 5,
                name: 'Hostal Mayan Heritage',
                city: 'Quetzaltenango',
                price_per_night: 45,
                max_guests: 2,
                rating: 4.3,
                reviews_count: 42,
                available_spots: 0,
                host_name: 'Juan Pérez',
                image_url: 'https://images.unsplash.com/photo-1520250497591' +
                    '-112f2f40a3f4?w=600&h=400&fit=crop',
            },
            {
                id: 6,
                name: 'Eco-Lodge en Petén',
                city: 'Flores',
                price_per_night: 120,
                max_guests: 4,
                rating: 4.6,
                reviews_count: 19,
                available_spots: 4,
                host_name: 'Diana Ruiz',
                image_url: 'https://images.unsplash.com/photo-1566073771259' +
                    '-6a8506099945?w=600&h=400&fit=crop',
            },
        ]

        // ── Computeds ────────────────────────────────────────

        // espaciosFiltrados se recalcula automáticamente cuando
        // cambia state.busqueda, state.filtros o state.espacios
        const espaciosFiltrados = computed(() => {
            let resultado = [...state.espacios]

            if (state.busqueda) {
                const termino = state.busqueda.toLowerCase()
                resultado = resultado.filter(e =>
                    e.name.toLowerCase().includes(termino) ||
                    e.city.toLowerCase().includes(termino)
                )
            }

            if (state.filtros.precioMax) {
                resultado = resultado.filter(
                    e => e.price_per_night <= Number(state.filtros.precioMax)
                )
            }

            if (state.filtros.precioMin) {
                resultado = resultado.filter(
                    e => e.price_per_night >= Number(state.filtros.precioMin)
                )
            }

            return resultado
        })

        const totalResultados = computed(() => espaciosFiltrados.value.length)

        const hayFiltrosActivos = computed(() =>
            !!state.busqueda ||
            !!state.filtros.precioMin ||
            !!state.filtros.precioMax
        )

        const estadisticas = computed(() => ({
            total: state.espacios.length,
            disponibles: state.espacios.filter(e => e.available_spots > 0).length,
            precioPromedio: state.espacios.length > 0
                ? Math.round(state.espacios.reduce(
                    (sum, e) => sum + e.price_per_night, 0
                ) / state.espacios.length)
                : 0,
        }))

        // ── Métodos ──────────────────────────────────────────

        const cargarEspacios = async () => {
            state.cargando = true
            state.error = null

            try {
                // Simulamos delay de red
                await new Promise(resolve => setTimeout(resolve, 800))
                state.espacios = espaciosDemo
            } catch (e) {
                state.error = 'Error al cargar los espacios. Intenta de nuevo.'
            } finally {
                state.cargando = false
            }
        }

        const abrirModal = (espacio) => {
            espacioSeleccionado.value = espacio
            mostrarModal.value = true
        }

        const handleReservaConfirmada = (reservaData) => {
            reservasDelUsuario.value.push({
                id: Date.now(),
                ...reservaData,
                status: 'pending',
                createdAt: new Date().toISOString(),
            })

            // Notificación simple
            alert(`✅ ¡Reserva confirmada!\n\n` +
                `${reservaData.space.name}\n` +
                `Check-in: ${reservaData.checkIn}\n` +
                `Check-out: ${reservaData.checkOut}\n` +
                `Total: $${reservaData.totalPrice}`)
        }

        const limpiarFiltros = () => {
            state.busqueda = ''
            state.filtros.precioMin = ''
            state.filtros.precioMax = ''
        }

        // ── Lifecycle ────────────────────────────────────────
        // onMounted equivale a useEffect(() => {}, []) de React
        // Se ejecuta después de que el componente se monta en el DOM
        onMounted(() => {
            cargarEspacios()
        })

        // watch con multiple sources
        // Equivale a useEffect con dependencias en React
        watch(
            () => state.busqueda,
            (nuevoValor) => {
                // Reseteamos la paginación al buscar
                state.paginacion.pagina = 1
            }
        )

        return {
            state,
            mostrarModal,
            espacioSeleccionado,
            reservasDelUsuario,
            espaciosFiltrados,
            totalResultados,
            hayFiltrosActivos,
            estadisticas,
            abrirModal,
            handleReservaConfirmada,
            limpiarFiltros,
            cargarEspacios,
        }
    },

    template: `
        <div class="min-h-screen bg-gray-50">

            <!-- Navbar -->
            <nav class="bg-white border-b border-gray-100 sticky top-0 z-40">
                <div class="max-w-7xl mx-auto px-4 py-4 flex items-center
                           justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">🏠</span>
                        <span class="font-bold text-gray-900 text-xl">StaySpot</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span
                            v-if="reservasDelUsuario.length > 0"
                            class="text-sm text-gray-600"
                        >
                            📋 {{ reservasDelUsuario.length }}
                            {{ reservasDelUsuario.length === 1
                               ? 'reserva' : 'reservas' }}
                        </span>
                        <button class="px-4 py-2 bg-gray-900 text-white text-sm
                                      font-medium rounded-xl hover:bg-gray-700
                                      transition-colors">
                            Iniciar sesión
                        </button>
                    </div>
                </div>
            </nav>

            <!-- Hero -->
            <section class="bg-gray-900 text-white py-16 px-4">
                <div class="max-w-3xl mx-auto text-center mb-10">
                    <h1 class="text-4xl font-bold mb-3">
                        Encuentra tu espacio perfecto
                    </h1>
                    <p class="text-gray-400 text-lg">
                        {{ estadisticas.total }} espacios en Guatemala
                        desde \${{ estadisticas.precioPromedio }}/noche
                    </p>
                </div>

                <!-- Buscador -->
                <div class="max-w-2xl mx-auto">
                    <SearchBar
                        v-model="state.busqueda"
                        :loading="state.cargando"
                        @search="(val) => state.busqueda = val"
                    />
                </div>
            </section>

            <!-- Filtros -->
            <section class="bg-white border-b border-gray-100 px-4 py-4">
                <div class="max-w-7xl mx-auto flex items-center gap-4 flex-wrap">
                    <span class="text-sm font-medium text-gray-700">Filtros:</span>

                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Precio mín:</label>
                        <input
                            v-model="state.filtros.precioMin"
                            type="number"
                            placeholder="$0"
                            class="w-24 px-3 py-1.5 border border-gray-200
                                   rounded-lg text-sm focus:outline-none
                                   focus:ring-2 focus:ring-gray-900"
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600">Precio máx:</label>
                        <input
                            v-model="state.filtros.precioMax"
                            type="number"
                            placeholder="$999"
                            class="w-24 px-3 py-1.5 border border-gray-200
                                   rounded-lg text-sm focus:outline-none
                                   focus:ring-2 focus:ring-gray-900"
                        >
                    </div>

                    <button
                        v-if="hayFiltrosActivos"
                        @click="limpiarFiltros"
                        class="flex items-center gap-1 px-3 py-1.5 text-sm
                               text-gray-600 hover:text-gray-900
                               border border-gray-200 rounded-lg
                               hover:bg-gray-50 transition-colors"
                    >
                        ✕ Limpiar filtros
                    </button>
                </div>
            </section>

            <!-- Contenido principal -->
            <main class="max-w-7xl mx-auto px-4 py-8">

                <!-- Estado de carga -->
                <div v-if="state.cargando"
                     class="flex flex-col items-center justify-center py-20">
                    <div class="w-12 h-12 border-4 border-gray-200
                               border-t-gray-900 rounded-full animate-spin mb-4">
                    </div>
                    <p class="text-gray-500">Cargando espacios...</p>
                </div>

                <!-- Error -->
                <div v-else-if="state.error"
                     class="text-center py-20">
                    <p class="text-4xl mb-4">😕</p>
                    <p class="text-gray-700 font-medium mb-4">{{ state.error }}</p>
                    <button
                        @click="cargarEspacios"
                        class="px-5 py-2.5 bg-gray-900 text-white rounded-xl
                               hover:bg-gray-700 transition-colors"
                    >
                        Reintentar
                    </button>
                </div>

                <!-- Sin resultados -->
                <div v-else-if="espaciosFiltrados.length === 0"
                     class="text-center py-20">
                    <p class="text-5xl mb-4">🔍</p>
                    <p class="text-gray-700 font-medium mb-2">
                        No encontramos espacios
                    </p>
                    <p class="text-gray-400 text-sm mb-6">
                        Prueba con otros filtros o términos de búsqueda
                    </p>
                    <button
                        v-if="hayFiltrosActivos"
                        @click="limpiarFiltros"
                        class="px-5 py-2.5 border border-gray-200 text-gray-700
                               rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        Limpiar filtros
                    </button>
                </div>

                <!-- Grid de espacios -->
                <div v-else>
                    <p class="text-sm text-gray-500 mb-5">
                        {{ totalResultados }}
                        {{ totalResultados === 1 ? 'espacio encontrado' : 'espacios encontrados' }}
                        <span v-if="hayFiltrosActivos" class="text-gray-400">
                            (filtros aplicados)
                        </span>
                    </p>

                    <!-- Grid responsivo -->
                    <div class="grid grid-cols-1 sm:grid-cols-2
                               lg:grid-cols-3 gap-6">
                        <SpaceCard
                            v-for="espacio in espaciosFiltrados"
                            :key="espacio.id"
                            :space="espacio"
                            :featured="espacio.rating >= 4.8"
                            @reserve="abrirModal"
                            @view-details="abrirModal"
                        />
                    </div>
                </div>
            </main>

            <!-- Modal de reserva -->
            <ReservationModal
                v-model="mostrarModal"
                :space="espacioSeleccionado"
                @confirm="handleReservaConfirmada"
            />
        </div>
    `,
}
