const SearchBar = {

    // v-model en componentes personalizados usa modelValue + update:modelValue
    // Esto permite que el padre haga: <SearchBar v-model="busqueda">
    props: {
        modelValue: {
            type: String,
            default: '',
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },

    emits: ['update:modelValue', 'search'],

    setup(props, { emit }) {
        const { ref, watch } = Vue

        const localValue = ref(props.modelValue)

        // debounce manual — espera 400ms antes de buscar
        // Evita una petición API por cada tecla presionada
        let debounceTimer = null

        const handleInput = (event) => {
            localValue.value = event.target.value

            // Emitimos para actualizar el v-model del padre
            emit('update:modelValue', localValue.value)

            // Debounce para la búsqueda
            clearTimeout(debounceTimer)
            debounceTimer = setTimeout(() => {
                emit('search', localValue.value)
            }, 400)
        }

        const handleSubmit = () => {
            clearTimeout(debounceTimer)
            emit('search', localValue.value)
        }

        const clear = () => {
            localValue.value = ''
            emit('update:modelValue', '')
            emit('search', '')
        }

        return { localValue, handleInput, handleSubmit, clear }
    },

    template: `
        <form
            @submit.prevent="handleSubmit"
            class="flex gap-2 w-full"
        >
            <div class="relative flex-1">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2
                             text-gray-400 pointer-events-none">
                    🔍
                </span>
                <input
                    :value="localValue"
                    @input="handleInput"
                    type="text"
                    placeholder="Buscar por ciudad o nombre..."
                    class="w-full pl-10 pr-10 py-3 border border-gray-200
                           rounded-xl text-sm focus:outline-none focus:ring-2
                           focus:ring-gray-900 bg-white"
                >
                <!-- Botón limpiar — v-show mantiene el DOM pero oculta -->
                <button
                    v-show="localValue"
                    @click="clear"
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2
                           text-gray-400 hover:text-gray-600 transition-colors"
                >
                    ✕
                </button>
            </div>

            <button
                type="submit"
                :disabled="loading"
                class="px-5 py-3 bg-gray-900 text-white text-sm font-medium
                       rounded-xl hover:bg-gray-700 transition-colors
                       disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="loading">⏳</span>
                <span v-else>Buscar</span>
            </button>
        </form>
    `,
}
