<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  recentLogs: Array,
});

const statusBadge = (status) => status === 'success' 
  ? 'bg-emerald-100 text-emerald-800 border-emerald-200' 
  : 'bg-rose-100 text-rose-800 border-rose-200';
</script>

<template>
  <AppLayout>
    <template #header>Gösterge Paneli</template>
    
    <div class="space-y-6 max-w-6xl">
      <!-- Karşılama Kartı -->
      <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 rounded-2xl p-6 md:p-8 text-white shadow-lg">
        <h2 class="text-2xl md:text-3xl font-extrabold mb-2 tracking-tight">🚀 GitPush Panel'e Hoş Geldiniz!</h2>
        <p class="text-indigo-100 text-sm md:text-base max-w-2xl leading-relaxed">
          GitHub repolarınıza dosyalarınızı tek tıkla yükleyin, anında ekip arkadaşlarınıza otomatik e-posta ve mobil bildirim gönderin.
        </p>
      </div>
      
      <!-- Hızlı Eylem Kartları -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <Link href="/push/logs" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4 hover:shadow-md hover:border-emerald-300 transition-all group">
          <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
            📜
          </div>
          <div>
            <p class="font-bold text-gray-900 dark:text-white">Push Geçmişi & Diff</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ekip tarafından yapılan tüm push kayıtları ve diff inceleme</p>
          </div>
        </Link>

        <Link href="/profile" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center gap-4 hover:shadow-md hover:border-purple-300 transition-all group">
          <div class="w-12 h-12 bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-105 transition-transform">
            ⚙️
          </div>
          <div>
            <p class="font-bold text-gray-900 dark:text-white">Hesap ve GitHub Bağlantısı</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Profil bilgileri, parola ve GitHub hesabı</p>
          </div>
        </Link>
      </div>
      
      <!-- Son Push Kayıtları Listesi -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
          <h3 class="font-bold text-gray-800 dark:text-white text-base">🗓 Son Yapılan Push İşlemleri</h3>
          <Link href="/push/logs" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Tümünü Gör →</Link>
        </div>
        
        <div v-if="recentLogs?.length" class="divide-y divide-gray-100 dark:divide-gray-700/60">
          <div v-for="log in recentLogs" :key="log.id" class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
            <div class="flex items-center gap-3.5 min-w-0">
              <span class="text-xl flex-shrink-0">
                {{ log.status === 'success' ? '🚀' : '⚠️' }}
              </span>
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                  <span class="text-gray-600 dark:text-gray-400 font-normal">{{ log.user_name }}</span> 
                  <span class="text-indigo-600 dark:text-indigo-400 mx-1.5 font-bold">→</span> 
                  {{ log.repo_name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono truncate mt-0.5">
                  {{ log.commit_message }}
                </p>
              </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
              <span :class="['px-2.5 py-0.5 rounded-full text-[11px] font-semibold border', statusBadge(log.status)]">
                {{ log.status === 'success' ? 'Başarılı' : 'Hata' }}
              </span>
              <span class="text-xs text-gray-400 hidden sm:inline">{{ log.created_at }}</span>
            </div>
          </div>
        </div>
        <div v-else class="px-6 py-12 text-center text-gray-400 text-sm">
          Henüz hiç push kaydı yok. İlk dosyanızı yüklemek için "Dosya Push Et" sayfasını kullanabilirsiniz.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
