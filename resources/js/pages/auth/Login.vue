<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { store } from '@/routes/login'

defineOptions({ layout: AuthLayout })
</script>

<template>
  <div class="rounded-2xl bg-white p-8 shadow-sm">
    <h1 class="mb-6 text-center text-2xl font-bold text-gray-900">
      Admin Login
    </h1>
    <Form
      v-slot="{ errors, processing }"
      v-bind="store.form()"
      :reset-on-success="['password']"
      class="space-y-4"
    >
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input
          id="email"
          name="email"
          type="email"
          autocomplete="email"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.email }"
        >
        <p v-if="errors.email" class="mt-1 text-xs text-red-600">
          {{ errors.email }}
        </p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
        <input
          id="password"
          name="password"
          type="password"
          autocomplete="current-password"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.password }"
        >
        <p v-if="errors.password" class="mt-1 text-xs text-red-600">
          {{ errors.password }}
        </p>
      </div>

      <div class="flex items-center">
        <input id="remember" name="remember" type="checkbox" class="mr-2 rounded border-gray-300">
        <label for="remember" class="text-sm text-gray-600">Remember me</label>
      </div>

      <button
        type="submit"
        :disabled="processing"
        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        Sign in
      </button>
    </Form>
  </div>
</template>
