<script setup lang="ts">
import type { AdminTenant } from '@/types/admin'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineProps<{ title?: string }>()

const page = usePage()
const tenant = computed(() => page.props.tenant as AdminTenant | null)
const user = computed(() => page.props.auth?.user)

const nav = [
  { label: 'Dashboard', href: '/admin', exact: true },
  { label: 'Services', href: '/admin/services', exact: false },
  { label: 'Staff', href: '/admin/staff', exact: false },
  { label: 'Bookings', href: '/admin/bookings', exact: false },
]

function isActive(href: string, exact: boolean): boolean {
  if (exact) {
    return page.url === href || page.url === href + '/'
  }
  return page.url.startsWith(href)
}
</script>

<template>
  <div class="flex min-h-screen bg-gray-100">
    <Head :title="title ?? tenant?.name ?? 'Admin'" />

    <!-- Sidebar -->
    <aside class="flex w-64 shrink-0 flex-col bg-white shadow-sm">
      <!-- Tenant header -->
      <div class="flex items-center gap-3 border-b border-gray-200 px-4 py-5">
        <img
          v-if="tenant?.logo_path"
          :src="`/storage/${tenant.logo_path}`"
          :alt="tenant?.name"
          class="h-8 w-8 rounded-full object-cover"
        >
        <div
          v-else
          class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
        >
          {{ tenant?.name?.charAt(0) ?? '?' }}
        </div>
        <span class="truncate text-sm font-semibold text-gray-900">{{ tenant?.name ?? 'Admin' }}</span>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4">
        <ul class="space-y-1">
          <li v-for="item in nav" :key="item.href">
            <Link
              :href="item.href"
              class="flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors"
              :class="
                isActive(item.href, item.exact)
                  ? 'bg-indigo-50 text-indigo-700'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              "
            >
              {{ item.label }}
            </Link>
          </li>
        </ul>
      </nav>

      <!-- User footer -->
      <div class="border-t border-gray-200 px-4 py-4">
        <p class="mb-2 truncate text-xs text-gray-500">
          {{ user?.name }}
        </p>
        <Link
          href="/logout"
          method="post"
          as="button"
          class="text-xs text-gray-500 hover:text-red-600"
        >
          Sign out
        </Link>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 overflow-auto p-8">
      <slot />
    </main>
  </div>
</template>
