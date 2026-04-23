<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
    <Head title="Login" />
    <div class="w-full max-w-md">
      <slot />
    </div>
  </div>
</template>
