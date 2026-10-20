<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  logs: Object,
});

const statusBadge = (status) => status === 'success'
  ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
  : 'bg-rose-100 text-rose-800 border-rose-200';
</script>

<template>
  <AppLayout>
    <template #header>📜 Push Geçmişi</template>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden max-w-6xl">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
        <div>
          <h3 class="font-bold text-gray-800 dark:text-white text-base">Tüm Gönderim Kayıtları</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekip tarafından yapılan tüm push ve bildirim kayıtları</p>
        </div>
      </div>
      
      <div v-if="logs?.data?.length" class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
              <th class="px-6 py-3">Gönderen</th>
              <th class="px-6 py-3">Repo & Branch</th>
              <th class="px-6 py-3">Dosya</th>
              <th class="px-6 py-3">Commit Mesajı</th>
              <th class="px-6 py-3">Durum</th>
              <th class="px-6 py-3">Tarih</th>
              <th class="px-6 py-3 text-right">Eylemler</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-6 py-4 whitespace-nowrap">
                <p class="font-bold text-gray-900 dark:text-white">{{ log.user?.name }}</p>
                <p class="text-[11px] text-gray-400">@{{ log.user?.github_username || 'github' }}</p>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ log.repo_full_name }}</p>
                <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded font-mono text-[10px]">
                  🌿 {{ log.branch }}
                </span>
              </td>
              <td class="px-6 py-4">
                <p class="font-mono text-gray-800 dark:text-gray-200 font-medium">{{ log.original_filename }}</p>
                <p class="font-mono text-gray-400 text-[11px] truncate max-w-xs mt-0.5">{{ log.file_path }}</p>
              </td>
              <td class="px-6 py-4 max-w-xs">
                <p class="text-gray-700 dark:text-gray-300 truncate font-medium">{{ log.commit_message }}</p>
                <a v-if="log.diff_url" :href="log.diff_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline mt-1">
                  <span>🔗 SHA:</span>
                  <span class="font-mono">{{ log.commit_sha }}</span>
                </a>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="['px-2.5 py-0.5 rounded-full font-semibold border text-[11px]', statusBadge(log.status)]">
                  {{ log.status === 'success' ? 'Başarılı' : 'Hata' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-gray-400 text-[11px]">
                {{ log.created_at }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right">
                <Link :href="`/push/logs/${log.id}`" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                  İncele →
                </Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      
      <div v-else class="px-6 py-16 text-center text-gray-400">
        <p class="text-4xl mb-3">📦</p>
        <p class="text-sm font-medium">Kayıtlı push işlemi bulunamadı.</p>
      </div>

      <!-- Sayfalama -->
      <div v-if="logs?.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-center gap-1">
        <Link
          v-for="link in logs.links"
          :key="link.label"
          :href="link.url || '#'"
          :class="[
            'px-3 py-1 rounded text-xs transition-colors',
            link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200',
            !link.url ? 'opacity-40 pointer-events-none' : ''
          ]"
          v-html="link.label"
        />
      </div>
    </div>
  </AppLayout>
</template>
