import api from './api.js'

export const login = (credentials) =>
    api.post('/auth/login', credentials).then(r => r.data)

export const register = (data) =>
    api.post('/auth/register', data).then(r => r.data)

export const logout = () =>
    api.post('/auth/logout')

export const getMe = () =>
    api.get('/auth/me').then(r => r.data)

export const updateProfile = (data) =>
    api.put('/users/profile', data).then(r => r.data)