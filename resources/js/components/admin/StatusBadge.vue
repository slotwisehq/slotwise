<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: 'pending' | 'confirmed' | 'cancelled' | 'no_show' }>()

const colours: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
  no_show: 'bg-gray-100 text-gray-700',
}

const labels: Record<string, string> = {
  pending: 'Pending',
  confirmed: 'Confirmed',
  cancelled: 'Cancelled',
  no_show: 'No Show',
}

const cls = computed(() => colours[props.status] ?? 'bg-gray-100 text-gray-700')
const label = computed(() => labels[props.status] ?? props.status)
</script>

<template>
  <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium" :class="cls">
    {{ label }}
  </span>
</template>
