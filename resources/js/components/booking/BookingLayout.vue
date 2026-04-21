<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { BookingTenant } from '@/types/booking';

defineProps<{
    tenant: BookingTenant;
}>();
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <Head :title="tenant.name" />
        <header class="bg-white border-b border-gray-200 px-4 py-4">
            <div class="max-w-2xl mx-auto flex items-center gap-3">
                <img
                    v-if="tenant.logo_url"
                    :src="tenant.logo_url"
                    :alt="tenant.name"
                    class="h-8 w-auto object-contain"
                />
                <span class="text-lg font-semibold text-gray-900">
                    {{ tenant.name }}
                </span>
            </div>
        </header>
        <main class="max-w-2xl mx-auto px-4 py-8">
            <slot />
        </main>
    </div>
</template>
