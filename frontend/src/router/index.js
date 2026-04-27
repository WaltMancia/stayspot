import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth.store.js'

// Lazy loading — cada página se carga solo cuando se necesita
// Equivale a React.lazy()
const HomePage = () => import('../pages/HomePage.vue')
const SpacesPage = () => import('../pages/spaces/SpacesPage.vue')
const SpaceDetailPage = () => import('../pages/spaces/SpaceDetailPage.vue')
const LoginPage = () => import('../pages/auth/LoginPage.vue')
const RegisterPage = () => import('../pages/auth/RegisterPage.vue')
const ReservationsPage = () => import('../pages/reservations/ReservationsPage.vue')
const ReservationDetailPage = () => import('../pages/reservations/ReservationDetailPage.vue')
const HostDashboardPage = () => import('../pages/host/HostDashboardPage.vue')
const HostSpacesPage = () => import('../pages/host/HostSpacesPage.vue')
const SpaceFormPage = () => import('../pages/host/SpaceFormPage.vue')

const routes = [
    { path: '/', component: HomePage },
    { path: '/espacios', component: SpacesPage },
    { path: '/espacios/:id', component: SpaceDetailPage },
    { path: '/login', component: LoginPage },
    { path: '/registro', component: RegisterPage },

    // Rutas protegidas — requieren autenticación
    {
        path: '/mis-reservas',
        component: ReservationsPage,
        meta: { requiresAuth: true },
    },
    {
        path: '/mis-reservas/:id',
        component: ReservationDetailPage,
        meta: { requiresAuth: true },
    },

    // Rutas de host
    {
        path: '/host',
        component: HostDashboardPage,
        meta: { requiresAuth: true, requiresHost: true },
    },
    {
        path: '/host/espacios',
        component: HostSpacesPage,
        meta: { requiresAuth: true, requiresHost: true },
    },
    {
        path: '/host/espacios/nuevo',
        component: SpaceFormPage,
        meta: { requiresAuth: true, requiresHost: true },
    },
    {
        path: '/host/espacios/:id/editar',
        component: SpaceFormPage,
        meta: { requiresAuth: true, requiresHost: true },
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
    // Scroll al top en cada navegación
    scrollBehavior: () => ({ top: 0 }),
})

// Navigation Guard — equivale a ProtectedRoute de React
// Se ejecuta antes de cada navegación
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        // Guarda la ruta a la que iba para redirigir después del login
        next({ path: '/login', query: { redirect: to.fullPath } })
        return
    }

    if (to.meta.requiresHost && !authStore.isHost && !authStore.isAdmin) {
        next('/')
        return
    }

    next()
})

export default router