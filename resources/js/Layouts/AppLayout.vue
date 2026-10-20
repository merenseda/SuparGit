<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const sidebarOpen = ref(false);

const navigation = [
  { name: 'Gösterge Paneli', href: '/dashboard', icon: '📜' },
  { name: 'Hesap / Profil', href: '/profile', icon: '👤' },
];

const adminNav = [
  { name: 'Admin Paneli', href: '/admin', icon: '⚙️' },
  { name: 'Kullanıcı Yönetimi', href: '/admin/users', icon: '👥' },
];

const isAdmin = page.props.auth?.user?.role === 'admin';
</script>

<template>
  <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex">
    <!-- Mobile sidebar overlay -->
    <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="sidebarOpen = false"></div>
    
    <!-- Sidebar -->
    <aside :class="['fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white transform transition-transform duration-300 lg:translate-x-0 lg:static flex flex-col', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
      <!-- Logo -->
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-800">
        <span class="text-2xl">🚀</span>
        <span class="text-xl font-bold text-white tracking-wide">GitSüpar</span>
      </div>
      
      <!-- Kullanıcı Bilgisi -->
      <div class="px-6 py-4 border-b border-gray-800 bg-gray-950/40">
        <div class="flex items-center gap-3">
          <img v-if="page.props.auth?.user?.github_avatar" :src="page.props.auth.user.github_avatar" class="w-10 h-10 rounded-full border border-gray-700 object-cover" />
          <div v-else class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-lg font-bold text-white shadow">
            {{ (page.props.auth?.user?.name || 'U')[0].toUpperCase() }}
          </div>
          <div class="overflow-hidden">
            <p class="text-sm font-semibold text-white truncate">{{ page.props.auth?.user?.name }}</p>
            <span class="inline-block px-2 py-0.5 text-[11px] font-medium rounded uppercase tracking-wider bg-gray-800 text-indigo-400 border border-gray-700">
              {{ page.props.auth?.user?.role || 'developer' }}
            </span>
          </div>
        </div>
        <div v-if="page.props.auth?.user?.github_username" class="mt-2.5 flex items-center gap-1.5 text-xs text-emerald-400">
          <span>✔</span>
          <span class="truncate">@{{ page.props.auth.user.github_username }}</span>
        </div>
        <div v-else class="mt-2.5">
          <a href="/auth/github" class="inline-flex items-center gap-1 text-xs text-amber-400 hover:text-amber-300 font-medium transition-colors">
            <span>⚡</span> GitHub Bağla
          </a>
        </div>
      </div>
      
      <!-- Navigation -->
      <nav class="flex-1 px-4 py-4 space-y-1 overflow-y-auto">
        <Link v-for="item in navigation" :key="item.name" :href="item.href"
          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors text-sm font-medium"
          :class="{ 'bg-indigo-600 text-white font-semibold': $page.url === item.href || ($page.url.startsWith(item.href) && item.href !== '/dashboard') }">
          <span class="text-base">{{ item.icon }}</span>
          <span>{{ item.name }}</span>
        </Link>
        
        <template v-if="isAdmin">
          <div class="pt-5 pb-1">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider px-3">Yönetim</p>
          </div>
          <Link v-for="item in adminNav" :key="item.name" :href="item.href"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-800 hover:text-white transition-colors text-sm font-medium"
            :class="{ 'bg-indigo-600 text-white font-semibold': $page.url === item.href || $page.url.startsWith(item.href) }">
            <span class="text-base">{{ item.icon }}</span>
            <span>{{ item.name }}</span>
          </Link>
        </template>
      </nav>
      
      <!-- Çıkış -->
      <div class="p-4 border-t border-gray-800">
        <Link href="/logout" method="post" as="button"
          class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-gray-400 hover:bg-red-950/50 hover:text-red-300 border border-transparent hover:border-red-900/40 transition-colors text-sm font-medium">
          <span>🚪</span> Çıkış Yap
        </Link>
      </div>
    </aside>
    
    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
      <!-- Top Bar -->
      <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-4">
          <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600 dark:text-gray-300 hover:text-gray-900 p-1 rounded-md">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <h1 class="text-xl font-bold text-gray-800 dark:text-white">
            <slot name="header">Gösterge Paneli</slot>
          </h1>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          Ekip İçi Hızlı Git Dağıtımı
        </div>
      </header>
      
      <!-- Flash Messages -->
      <div class="px-6 pt-4">
        <div v-if="$page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg px-4 py-3 flex items-center gap-2 mb-4 text-sm shadow-sm">
          <span class="text-base">✅</span>
          <span>{{ $page.props.flash.success }}</span>
        </div>
        <div v-if="$page.props.flash?.error" class="bg-rose-50 border border-rose-200 text-rose-800 rounded-lg px-4 py-3 flex items-center gap-2 mb-4 text-sm shadow-sm">
          <span class="text-base">❌</span>
          <span>{{ $page.props.flash.error }}</span>
        </div>
      </div>
      
      <!-- Slot Content -->
      <main class="flex-1 px-6 py-4">
        <slot />
      </main>
      
      <!-- Footer -->
      <footer class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 text-center text-xs text-gray-400">
        GitSüpar &copy; {{ new Date().getFullYear() }} — Ekip için GitHub & Bildirim Dağıtım Sistemi
      </footer>
    </div>
  </div>
</template>
