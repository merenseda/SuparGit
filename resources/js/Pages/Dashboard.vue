<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  logs: Object,
  stats: Object,
});

const statusBadge = (status) => status === 'success' 
  ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' 
  : 'bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800';
</script>

<template>
  <AppLayout>
    <template #header>📜 Gösterge Paneli — Push Geçmişi</template>
    
    <div class="space-y-6 max-w-7xl">
      <!-- Özet İstatistik Çubuğu -->
      <div v-if="stats" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4 shadow-sm">
          <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-xl flex items-center justify-center text-2xl font-bold">
            🚀
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Toplam Gönderim</p>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ stats.total }}</p>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4 shadow-sm">
          <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-2xl font-bold">
            ✅
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Başarılı Push</p>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ stats.success }}</p>
          </div>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 flex items-center gap-4 shadow-sm">
          <div class="w-12 h-12 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center text-2xl font-bold">
            ⚠️
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Hatalı / Başarısız</p>
            <p class="text-xl font-extrabold text-gray-900 dark:text-white mt-0.5">{{ stats.failed }}</p>
          </div>
        </div>
      </div>

      <!-- Ana Push Geçmişi Tablosu -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">Ekip Git Push Kayıtları</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekip üyeleri tarafından Masaüstü Uygulaması ile yapılan tüm yüklemeler ve diff detayları</p>
          </div>
          <div class="text-xs text-gray-400">
            Toplam {{ logs?.total || logs?.data?.length || 0 }} işlem
          </div>
        </div>

        <div v-if="logs?.data?.length" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3.5">Gönderen</th>
                <th class="px-6 py-3.5">Repo & Branch</th>
                <th class="px-6 py-3.5">Yüklenen Dosya</th>
                <th class="px-6 py-3.5">Commit Mesajı</th>
                <th class="px-6 py-3.5">Durum</th>
                <th class="px-6 py-3.5">Tarih</th>
                <th class="px-6 py-3.5 text-right">Eylem</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50/60 dark:hover:bg-gray-700/30 transition-colors">
                <!-- Gönderen -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <img v-if="log.user?.github_avatar" :src="log.user.github_avatar" class="w-7 h-7 rounded-full border border-gray-200 dark:border-gray-700 object-cover" />
                    <div v-else class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white">
                      {{ (log.user?.name || 'U')[0].toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-bold text-gray-900 dark:text-white">{{ log.user?.name }}</p>
                      <p class="text-[11px] text-gray-400">@{{ log.user?.github_username || 'github' }}</p>
                    </div>
                  </div>
                </td>

                <!-- Repo & Branch -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <p class="font-semibold text-gray-800 dark:text-gray-200">{{ log.repo_full_name }}</p>
                  <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded font-mono text-[10px]">
                    🌿 {{ log.branch }}
                  </span>
                </td>

                <!-- Dosya -->
                <td class="px-6 py-4">
                  <p class="font-mono text-gray-800 dark:text-gray-200 font-medium">{{ log.original_filename }}</p>
                  <p class="font-mono text-gray-400 text-[11px] truncate max-w-xs mt-0.5">{{ log.file_path }}</p>
                </td>

                <!-- Commit Mesajı -->
                <td class="px-6 py-4 max-w-xs">
                  <p class="text-gray-700 dark:text-gray-300 truncate font-medium">{{ log.commit_message }}</p>
                  <a v-if="log.diff_url" :href="log.diff_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline mt-1">
                    <span>🔗 SHA:</span>
                    <span class="font-mono">{{ log.commit_sha }}</span>
                  </a>
                </td>

                <!-- Durum -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="['px-2.5 py-0.5 rounded-full font-semibold border text-[11px]', statusBadge(log.status)]">
                    {{ log.status === 'success' ? 'Başarılı' : 'Hata' }}
                  </span>
                </td>

                <!-- Tarih -->
                <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-[11px]">
                  <p>{{ log.created_at }}</p>
                  <p class="text-[10px] text-gray-400">{{ log.date }}</p>
                </td>

                <!-- Eylemler -->
                <td class="px-6 py-4 whitespace-nowrap text-right">
                  <div class="flex items-center justify-end gap-2">
                    <a v-if="log.diff_url" :href="log.diff_url" target="_blank" class="px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 rounded-md font-semibold hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors text-xs">
                      Diff ↗
                    </a>
                    <Link :href="`/push/logs/${log.id}`" class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-xs">
                      Detay
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Boş Durum -->
        <div v-else class="px-6 py-16 text-center text-gray-400">
          <p class="text-4xl mb-3">📦</p>
          <p class="text-base font-semibold text-gray-600 dark:text-gray-300">Henüz hiç push kaydı bulunmuyor.</p>
          <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
            Dosya ve klasör push işlemleri GitSüpar Masaüstü Uygulaması üzerinden gerçekleştirilir.
          </p>
        </div>

        <!-- Sayfalama -->
        <div v-if="logs?.last_page > 1" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex justify-center gap-1">
          <Link
            v-for="link in logs.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
              'px-3 py-1 rounded text-xs transition-colors',
              link.active ? 'bg-indigo-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
              !link.url ? 'opacity-40 pointer-events-none' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>
