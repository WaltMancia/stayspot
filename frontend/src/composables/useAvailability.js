import { ref, computed } from 'vue'
import { getAvailability, getPriceEstimate } from '../services/space.service.js'

export function useAvailability(spaceId) {
    const blockedDates = ref([])
    const priceEstimate = ref(null)
    const loadingDates = ref(false)
    const loadingPrice = ref(false)

    const fetchBlockedDates = async () => {
        loadingDates.value = true
        try {
            const data = await getAvailability(spaceId.value || spaceId)
            blockedDates.value = data.blocked_dates
        } finally {
            loadingDates.value = false
        }
    }

    const fetchPriceEstimate = async (checkIn, checkOut) => {
        if (!checkIn || !checkOut) return
        loadingPrice.value = true
        try {
            priceEstimate.value = await getPriceEstimate(
                spaceId.value || spaceId,
                checkIn,
                checkOut
            )
        } catch {
            priceEstimate.value = null
        } finally {
            loadingPrice.value = false
        }
    }

    const isDateBlocked = (date) => {
        return blockedDates.value.includes(date)
    }

    return {
        blockedDates, priceEstimate,
        loadingDates, loadingPrice,
        fetchBlockedDates, fetchPriceEstimate,
        isDateBlocked,
    }
}