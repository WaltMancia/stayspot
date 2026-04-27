<template>
  <button
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium',
      'rounded-xl transition-all duration-200 cursor-pointer',
      'disabled:cursor-not-allowed disabled:opacity-50',
      variants[variant],
      sizes[size],
      className,
    ]"
    :disabled="loading || disabled"
    v-bind="$attrs"
  >
    <span
      v-if="loading"
      class="w-4 h-4 border-2 border-current border-t-transparent
             rounded-full animate-spin"
    />
    <slot v-else />
  </button>
</template>

<script setup>
// defineProps y defineEmits no necesitan importarse en <script setup>
const props = defineProps({
  variant:  { type: String, default: 'primary' },
  size:     { type: String, default: 'md' },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  className: { type: String, default: '' },
})

const variants = {
  primary:   'bg-gray-900 text-white hover:bg-gray-700',
  secondary: 'bg-white text-gray-900 border border-gray-200 hover:bg-gray-50',
  danger:    'bg-red-600 text-white hover:bg-red-700',
  ghost:     'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
  success:   'bg-emerald-600 text-white hover:bg-emerald-700',
}

const sizes = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-6 py-3 text-base',
}
</script>