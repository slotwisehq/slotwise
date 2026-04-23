<script setup lang="ts">
import type { BookingTenant } from '@/types/booking'
import { Head } from '@inertiajs/vue3'

defineProps<{
  tenant: BookingTenant
}>()
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Head :title="tenant.name" />
    <header class="border-b border-gray-200 bg-white px-4 py-4">
      <div class="mx-auto flex max-w-2xl items-center gap-3">
        <img
          v-if="tenant.logo_url"
          :src="tenant.logo_url"
          :alt="tenant.name"
          class="h-8 w-auto object-contain"
        >
        <span class="text-lg font-semibold text-gray-900">
          {{ tenant.name }}
        </span>
      </div>
    </header>
    <main class="mx-auto max-w-2xl px-4 py-8">
      <slot />
    </main>
  </div>
</template>
