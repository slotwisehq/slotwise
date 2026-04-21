<script setup lang="ts">
import type { BookingStaff } from '@/types/booking';

defineProps<{
    staff: BookingStaff;
    href: string;
}>();
</script>

<template>
    <a
        :href="href"
        class="flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-indigo-400 hover:shadow-md"
    >
        <div
            class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100"
        >
            <img
                v-if="staff.avatar_url"
                :src="staff.avatar_url"
                :alt="staff.name"
                class="h-full w-full object-cover"
            />
            <span v-else class="text-lg font-semibold text-indigo-600">{{
                staff.name[0]
            }}</span>
        </div>
        <div>
            <p class="font-semibold text-gray-900">{{ staff.name }}</p>
            <p v-if="staff.bio" class="line-clamp-1 text-sm text-gray-500">
                {{ staff.bio }}
            </p>
        </div>
    </a>
</template>

