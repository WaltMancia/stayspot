<template>
  <div
    :class="[
      'border-4 border-gray-100 border-t-gray-900 rounded-full animate-spin',
      sizes[size],
    ]"
  />
</template>

<script setup>
defineProps({ size: { type: String, default: 'md' } })
const sizes = { sm: 'w-4 h-4', md: 'w-8 h-8', lg: 'w-12 h-12' }
</script>