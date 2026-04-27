import api from './api.js'

export const getSpaces = (params = {}) =>
    api.get('/spaces', { params }).then(r => r.data)

export const getSpace = (id) =>
    api.get(`/spaces/${id}`).then(r => r.data)

export const createSpace = (data) =>
    api.post('/spaces', data).then(r => r.data)

export const updateSpace = (id, data) =>
    api.put(`/spaces/${id}`, data).then(r => r.data)

export const deleteSpace = (id) =>
    api.delete(`/spaces/${id}`)

export const getAvailability = (spaceId) =>
    api.get(`/spaces/${spaceId}/availability`).then(r => r.data)

export const getPriceEstimate = (spaceId, checkIn, checkOut) =>
    api.get(`/spaces/${spaceId}/price-estimate`, {
        params: { check_in: checkIn, check_out: checkOut },
    }).then(r => r.data)

export const getMySpaces = () =>
    api.get('/spaces/my-spaces').then(r => r.data)

export const getHostStats = () =>
    api.get('/spaces/stats').then(r => r.data)