<template>
  <div>
    <AppNavbar v-if="!isAuthRoute" />
    <main :class="isAuthRoute ? '' : 'min-h-screen bg-gray-50'">
      <!-- RouterView renderiza la página actual -->
      <!-- Equivale a <Outlet> de React Router -->
      <RouterView />
    </main>
    <ToastContainer />
  </div>
</template>

<script setup>
import { computed }     from 'vue'
import { useRoute }     from 'vue-router'
import AppNavbar        from './components/AppNavbar.vue'
import ToastContainer   from './components/ui/ToastContainer.vue'

const route = useRoute()
const isAuthRoute = computed(() =>
  ['/login', '/registro'].includes(route.path)
)
</script>
