<script setup lang="ts">
import type { BookingService } from '@/types/booking';

defineProps<{
    service: BookingService;
    href: string;
}>();
</script>

<template>
    <a
        :href="href"
        class="block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition-all hover:border-indigo-400 hover:shadow-md"
    >
        <h3 class="text-base font-semibold text-gray-900">
            {{ service.name }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ service.duration_minutes }} min · €{{ service.price }}
        </p>
    </a>
</template>

