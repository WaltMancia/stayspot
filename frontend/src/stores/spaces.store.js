import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { getSpaces, getSpace } from '../services/space.service.js'

export const useSpacesStore = defineStore('spaces', () => {
    const spaces = ref([])
    const current = ref(null)
    const loading = ref(false)
    const pagination = ref(null)

    const filters = reactive({
        city: '',
        price_min: '',
        price_max: '',
        guests: '',
        check_in: '',
        check_out: '',
        sort: '',
        page: 1,
        per_page: 12,
    })

    const fetchSpaces = async () => {
        loading.value = true
        try {
            // Limpiamos los filtros vacíos antes de enviar
            const cleanFilters = Object.fromEntries(
                Object.entries(filters).filter(([_, v]) => v !== '' && v !== null)
            )
            const data = await getSpaces(cleanFilters)
            spaces.value = data.data
            pagination.value = data.meta
        } finally {
            loading.value = false
        }
    }

    const fetchSpace = async (id) => {
        loading.value = true
        try {
            const data = await getSpace(id)
            current.value = data.data
        } finally {
            loading.value = false
        }
    }

    const resetFilters = () => {
        Object.assign(filters, {
            city: '', price_min: '', price_max: '',
            guests: '', check_in: '', check_out: '',
            sort: '', page: 1,
        })
    }

    return {
        spaces, current, loading, pagination, filters,
        fetchSpaces, fetchSpace, resetFilters,
    }
})