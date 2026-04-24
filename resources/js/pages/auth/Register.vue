<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { register } from '@/routes'

defineOptions({ layout: AuthLayout })
</script>

<template>
  <div class="rounded-2xl bg-white p-8 shadow-sm">
    <h1 class="mb-6 text-center text-2xl font-bold text-gray-900">
      Create your account
    </h1>
    <Form
      v-slot="{ errors, processing }"
      v-bind="register.form()"
      class="space-y-4"
    >
      <div>
        <label for="business_name" class="block text-sm font-medium text-gray-700">Business name</label>
        <input
          id="business_name"
          name="business_name"
          type="text"
          autocomplete="organization"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.business_name }"
        >
        <p v-if="errors.business_name" class="mt-1 text-xs text-red-600">
          {{ errors.business_name }}
        </p>
      </div>

      <div>
        <label for="owner_name" class="block text-sm font-medium text-gray-700">Your name</label>
        <input
          id="owner_name"
          name="owner_name"
          type="text"
          autocomplete="name"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.owner_name }"
        >
        <p v-if="errors.owner_name" class="mt-1 text-xs text-red-600">
          {{ errors.owner_name }}
        </p>
      </div>

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
          autocomplete="new-password"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.password }"
        >
        <p v-if="errors.password" class="mt-1 text-xs text-red-600">
          {{ errors.password }}
        </p>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
        <input
          id="password_confirmation"
          name="password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
      </div>

      <button
        type="submit"
        :disabled="processing"
        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
      >
        Create account
      </button>
    </Form>

    <p class="mt-4 text-center text-sm text-gray-600">
      Already have an account?
      <a href="/admin/login" class="text-indigo-600 hover:underline">Sign in</a>
    </p>
  </div>
</template>
