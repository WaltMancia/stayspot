const ReservationModal = {

    props: {
        space: {
            type: Object,
            default: null,
        },
        // v-model para controlar visibilidad desde el padre
        modelValue: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:modelValue', 'confirm'],

    setup(props, { emit }) {
        const { ref, computed, watch } = Vue

        const fechaInicio = ref('')
        const fechaFin = ref('')
        const huespedes = ref(1)
        const error = ref('')

        // Hoy en formato YYYY-MM-DD para el min del date input
        const hoy = new Date().toISOString().split('T')[0]

        const noches = computed(() => {
            if (!fechaInicio.value || !fechaFin.value) return 0

            const inicio = new Date(fechaInicio.value)
            const fin = new Date(fechaFin.value)
            const diff = fin - inicio

            // Convertimos milisegundos a días
            return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
        })

        const totalPrice = computed(() => {
            if (!props.space || noches.value === 0) return 0
            return props.space.price_per_night * noches.value
        })

        const formattedTotal = computed(() =>
            new Intl.NumberFormat('es-GT', {
                style: 'currency',
                currency: 'USD',
            }).format(totalPrice.value)
        )

        // watch observa cambios en las fechas para validar
        // Equivale a useEffect con dependencias en React
        watch([fechaInicio, fechaFin], () => {
            error.value = ''

            if (fechaInicio.value && fechaFin.value) {
                if (fechaFin.value <= fechaInicio.value) {
                    error.value = 'La fecha de salida debe ser posterior a la de entrada'
                }
            }
        })

        const cerrar = () => {
            // Emitimos false para actualizar v-model del padre
            emit('update:modelValue', false)
            // Reseteamos el formulario
            fechaInicio.value = ''
            fechaFin.value = ''
            huespedes.value = 1
            error.value = ''
        }

        const confirmar = () => {
            if (!fechaInicio.value || !fechaFin.value) {
                error.value = 'Selecciona las fechas de tu estadía'
                return
            }

            if (noches.value === 0) {
                error.value = 'La estadía mínima es de 1 noche'
                return
            }

            emit('confirm', {
                space: props.space,
                checkIn: fechaInicio.value,
                checkOut: fechaFin.value,
                guests: huespedes.value,
                nights: noches.value,
                totalPrice: totalPrice.value,
            })

            cerrar()
        }

        return {
            fechaInicio, fechaFin, huespedes,
            error, hoy, noches,
            formattedTotal, totalPrice,
            cerrar, confirmar,
        }
    },

    template: `
        <!-- Teleport mueve el modal al body del DOM -->
        <!-- Evita problemas de z-index y overflow -->
        <!-- Equivale a createPortal() de React -->
        <Teleport to="body">
            <!-- v-if → el modal no existe en el DOM cuando está cerrado -->
            <div
                v-if="modelValue && space"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="cerrar"
                ></div>

                <!-- Modal -->
                <div
                    class="relative bg-white rounded-2xl w-full max-w-md
                           shadow-2xl z-10"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6
                               border-b border-gray-100">
                        <h2 class="font-bold text-gray-900 text-lg">
                            Reservar espacio
                        </h2>
                        <button
                            @click="cerrar"
                            class="w-8 h-8 flex items-center justify-center
                                   text-gray-400 hover:text-gray-700
                                   hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Espacio seleccionado -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 bg-gray-200 rounded-xl
                                       flex items-center justify-center text-2xl">
                                🏠
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">
                                    {{ space.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    📍 {{ space.city }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <div class="p-6 space-y-4">

                        <!-- Fechas -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium
                                             text-gray-700 mb-1.5">
                                    Check-in
                                </label>
                                <input
                                    v-model="fechaInicio"
                                    type="date"
                                    :min="hoy"
                                    class="w-full px-3 py-2.5 border border-gray-200
                                           rounded-xl text-sm focus:outline-none
                                           focus:ring-2 focus:ring-gray-900"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium
                                             text-gray-700 mb-1.5">
                                    Check-out
                                </label>
                                <input
                                    v-model="fechaFin"
                                    type="date"
                                    :min="fechaInicio || hoy"
                                    class="w-full px-3 py-2.5 border border-gray-200
                                           rounded-xl text-sm focus:outline-none
                                           focus:ring-2 focus:ring-gray-900"
                                >
                            </div>
                        </div>

                        <!-- Huéspedes -->
                        <div>
                            <label class="block text-sm font-medium
                                         text-gray-700 mb-1.5">
                                Huéspedes
                            </label>
                            <select
                                v-model="huespedes"
                                class="w-full px-3 py-2.5 border border-gray-200
                                       rounded-xl text-sm focus:outline-none
                                       focus:ring-2 focus:ring-gray-900 bg-white"
                            >
                                <!-- v-for en select options -->
                                <option
                                    v-for="n in (space.max_guests || 6)"
                                    :key="n"
                                    :value="n"
                                >
                                    {{ n }} {{ n === 1 ? 'huésped' : 'huéspedes' }}
                                </option>
                            </select>
                        </div>

                        <!-- Error -->
                        <div
                            v-if="error"
                            class="flex items-start gap-2 p-3 bg-red-50
                                   border border-red-100 rounded-xl"
                        >
                            <span class="text-red-500 flex-shrink-0">⚠️</span>
                            <p class="text-sm text-red-700">{{ error }}</p>
                        </div>

                        <!-- Resumen de precio -->
                        <div
                            v-if="noches > 0"
                            class="p-4 bg-gray-50 rounded-xl space-y-2"
                        >
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>
                                    \${{ space.price_per_night }}/noche
                                    × {{ noches }} {{ noches === 1 ? 'noche' : 'noches' }}
                                </span>
                                <span>{{ formattedTotal }}</span>
                            </div>
                            <div class="flex justify-between font-bold
                                       text-gray-900 border-t pt-2">
                                <span>Total</span>
                                <span>{{ formattedTotal }}</span>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex gap-3 pt-2">
                            <button
                                @click="confirmar"
                                :disabled="noches === 0 || !!error"
                                class="flex-1 py-3 bg-gray-900 text-white font-medium
                                       rounded-xl hover:bg-gray-700 transition-colors
                                       disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Confirmar reserva
                            </button>
                            <button
                                @click="cerrar"
                                class="px-5 py-3 border border-gray-200 text-gray-700
                                       font-medium rounded-xl hover:bg-gray-50
                                       transition-colors"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    `,
}
