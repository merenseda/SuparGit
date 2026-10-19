<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  log: Object,
});
</script>

<template>
  <AppLayout>
    <template #header>🔍 Push Kaydı Detayı</template>
    
    <div class="max-w-4xl space-y-6">
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
          <div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ log.repo_full_name }}</h2>
            <p class="text-xs text-gray-500 mt-0.5">Branch: <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ log.branch }}</span></p>
          </div>
          <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', log.status === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
            {{ log.status === 'success' ? 'Başarılı' : 'Başarısız' }}
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 py-5 text-xs">
          <div>
            <span class="text-gray-400 block mb-0.5">Gönderen Kullanıcı</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 text-sm">{{ log.user.name }} (@{{ log.user.github_username }})</span>
          </div>
          <div>
            <span class="text-gray-400 block mb-0.5">İşlem Tarihi</span>
            <span class="font-medium text-gray-800 dark:text-gray-200">{{ log.created_at }}</span>
          </div>
          <div>
            <span class="text-gray-400 block mb-0.5">Dosya Adı & Konumu</span>
            <span class="font-mono text-gray-800 dark:text-gray-200 font-semibold">{{ log.original_filename }}</span>
            <p class="text-[11px] text-gray-400 font-mono mt-0.5">{{ log.file_path }}</p>
          </div>
          <div>
            <span class="text-gray-400 block mb-0.5">Commit SHA</span>
            <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold">{{ log.commit_sha || 'N/A' }}</span>
          </div>
        </div>

        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
          <span class="text-xs text-gray-400 block mb-1">Commit Açıklaması</span>
          <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-lg text-xs font-mono text-gray-800 dark:text-gray-200 border border-gray-100 dark:border-gray-800">
            {{ log.commit_message }}
          </div>
        </div>

        <div class="mt-6 flex gap-3">
          <a v-if="log.diff_url" :href="log.diff_url" target="_blank" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-sm">
            <span>🔍 GitHub'da Diff / Değişikliği İncele</span>
          </a>
          <a v-if="log.file_url" :href="log.file_url" target="_blank" class="px-4 py-2 bg-gray-900 hover:bg-black text-white rounded-lg text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-sm">
            <span>📄 GitHub Dosyasını Gör</span>
          </a>
          <Link href="/push/logs" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition-colors">
            ← Listeye Dön
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
