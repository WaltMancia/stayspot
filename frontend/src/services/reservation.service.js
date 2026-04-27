import api from './api.js'

export const getReservations = () =>
    api.get('/reservations').then(r => r.data)

export const getReservation = (id) =>
    api.get(`/reservations/${id}`).then(r => r.data)

export const createReservation = (data) =>
    api.post('/reservations', data).then(r => r.data)

export const cancelReservation = (id, reason) =>
    api.patch(`/reservations/${id}/cancel`, { reason }).then(r => r.data)

export const confirmReservation = (id) =>
    api.patch(`/reservations/${id}/confirm`).then(r => r.data)

export const createPaymentIntent = (id) =>
    api.post(`/reservations/${id}/payment-intent`).then(r => r.data)

export const getPaymentStatus = (id) =>
    api.get(`/reservations/${id}/payment-status`).then(r => r.data)