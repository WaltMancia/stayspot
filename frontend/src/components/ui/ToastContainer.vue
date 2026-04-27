<template>
  <!-- Teleport al body — igual que en el Paso 2 -->
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-50 space-y-2">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="[
            'flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg',
            'text-sm font-medium min-w-64 max-w-sm',
            typeClasses[toast.type],
          ]"
        >
          <span>{{ icons[toast.type] }}</span>
          <span>{{ toast.message }}</span>
          <button
            @click="remove(toast.id)"
            class="ml-auto text-current opacity-60 hover:opacity-100"
          >
            ✕
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useGlobalToast } from '../../composables/useToast.js'

const { toasts, remove } = useGlobalToast()

const typeClasses = {
  success: 'bg-emerald-600 text-white',
  error:   'bg-red-600 text-white',
  info:    'bg-blue-600 text-white',
}

const icons = { success: '✅', error: '❌', info: 'ℹ️' }
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from   { opacity: 0; transform: translateX(100%); }
.toast-leave-to     { opacity: 0; transform: translateX(100%); }
</style>