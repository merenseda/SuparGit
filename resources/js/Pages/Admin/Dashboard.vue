<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  stats: Object,
});
</script>

<template>
  <AppLayout>
    <template #header>⚙️ Yönetim Paneli</template>
    
    <div class="space-y-6 max-w-6xl">
      <!-- İstatistikler Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Toplam Üye</p>
          <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ stats?.total_users || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Developer</p>
          <p class="text-2xl font-extrabold text-indigo-600 dark:text-indigo-400 mt-1">{{ stats?.total_developers || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Toplam Push</p>
          <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats?.total_pushes || 0 }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bugün Push</p>
          <p class="text-2xl font-extrabold text-purple-600 dark:text-purple-400 mt-1">{{ stats?.pushes_today || 0 }}</p>
        </div>
      </div>

      <!-- Hızlı Bağlantı -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm flex items-center justify-between">
        <div>
          <h4 class="font-bold text-gray-800 dark:text-white text-sm">Kullanıcı Rolleri & Ekip Listesi</h4>
          <p class="text-xs text-gray-500 mt-0.5">Üyeleri görüntüleyin, rollerini developer/viewer/admin olarak güncelleyin.</p>
        </div>
        <Link href="/admin/users" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors">
          Kullanıcıları Yönet →
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
