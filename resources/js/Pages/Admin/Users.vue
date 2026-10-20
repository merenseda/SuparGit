<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  users: Array,
});

const editingUser = ref(null);

const form = useForm({
  role: '',
  allowed_branches: '',
});

const openEditModal = (user) => {
  editingUser.value = user;
  form.role = user.role;
  form.allowed_branches = user.allowed_branches || '*';
};

const closeEditModal = () => {
  editingUser.value = null;
  form.reset();
};

const savePermissions = () => {
  if (!editingUser.value) return;

  form.patch(`/admin/users/${editingUser.value.id}/role`, {
    preserveScroll: true,
    onSuccess: () => {
      closeEditModal();
    },
  });
};

const quickSetBranch = (val) => {
  if (val === '*') {
    form.allowed_branches = '*';
    return;
  }
  let current = form.allowed_branches.trim();
  if (current === '*' || current === '') {
    form.allowed_branches = val;
  } else {
    const parts = current.split(',').map(p => p.trim());
    if (!parts.includes(val)) {
      parts.push(val);
      form.allowed_branches = parts.join(', ');
    }
  }
};
</script>

<template>
  <AppLayout>
    <template #header>👥 Kullanıcı ve Branch İzinleri Yönetimi</template>
    
    <div class="space-y-6 max-w-6xl">
      <!-- Bilgilendirme Kartı -->
      <div class="bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900 rounded-xl p-4 flex items-start gap-3">
        <span class="text-xl">🛡️</span>
        <div class="text-xs text-indigo-900 dark:text-indigo-200">
          <p class="font-bold">Branch Yükleme Koruması ve İzin Kontrolü:</p>
          <p class="mt-0.5 leading-relaxed">
            Yöneticiler (Admin) tüm branch'lere kısıtlama olmaksızın dosya push edebilir. Diğer kullanıcılar (Developer ve Viewer) yalnızca burada belirlenen branch'lere dosya yükleyebilir.
            Örn: <code class="px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900 rounded font-mono">dev, staging, feature/*</code> veya <code class="px-1.5 py-0.5 bg-indigo-100 dark:bg-indigo-900 rounded font-mono">*</code> (Tüm branch'ler).
          </p>
        </div>
      </div>

      <!-- Kullanıcılar Tablosu -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
          <div>
            <h3 class="font-bold text-gray-800 dark:text-white text-base">Ekip Üyeleri Listesi</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Rol ve branch push yetkilerini buradan düzenleyebilirsiniz.</p>
          </div>
          <span class="text-xs text-gray-400">{{ users.length }} Üye</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3.5">Üye</th>
                <th class="px-6 py-3.5">E-posta</th>
                <th class="px-6 py-3.5">GitHub</th>
                <th class="px-6 py-3.5">Rol</th>
                <th class="px-6 py-3.5">İzin Verilen Branch'ler</th>
                <th class="px-6 py-3.5 text-right">İşlemler</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
              <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <!-- Üye -->
                <td class="px-6 py-4">
                  <div class="flex items-center gap-2.5">
                    <img v-if="user.github_avatar" :src="user.github_avatar" class="w-7 h-7 rounded-full border border-gray-200 dark:border-gray-700 object-cover" />
                    <div v-else class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white">
                      {{ user.name[0].toUpperCase() }}
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">{{ user.name }}</span>
                  </div>
                </td>

                <!-- E-posta -->
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                  {{ user.email }}
                </td>

                <!-- GitHub -->
                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                  <span v-if="user.github_username" class="text-emerald-600 dark:text-emerald-400 font-mono">@{{ user.github_username }}</span>
                  <span v-else class="text-gray-400 italic">Bağlı değil</span>
                </td>

                <!-- Rol -->
                <td class="px-6 py-4">
                  <span :class="[
                    'px-2.5 py-1 rounded font-bold uppercase tracking-wider text-[10px] border',
                    user.role === 'admin' 
                      ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800'
                      : user.role === 'developer'
                      ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200'
                  ]">
                    {{ user.role }}
                  </span>
                </td>

                <!-- Branch İzinleri -->
                <td class="px-6 py-4">
                  <div v-if="user.role === 'admin'" class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                    <span>👑</span>
                    <span>Tüm Branch'ler (Sınırsız)</span>
                  </div>
                  <div v-else>
                    <span v-if="user.allowed_branches === '*' || !user.allowed_branches" class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 font-mono text-[11px] border border-emerald-200 dark:border-emerald-800">
                      * (Tüm Branch'ler)
                    </span>
                    <span v-else class="px-2 py-0.5 rounded bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 font-mono text-[11px] border border-amber-200 dark:border-amber-800">
                      🌿 {{ user.allowed_branches }}
                    </span>
                  </div>
                </td>

                <!-- Eylemler -->
                <td class="px-6 py-4 text-right">
                  <button
                    @click="openEditModal(user)"
                    class="px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 font-bold rounded-lg transition-colors"
                  >
                    Yetkileri Düzenle ✎
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Yetki Düzenleme Modal -->
    <div v-if="editingUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 max-w-lg w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
          <div>
            <h3 class="font-bold text-gray-900 dark:text-white text-base">Kullanıcı Yetkilerini Düzenle</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ editingUser.name }} ({{ editingUser.email }})</p>
          </div>
          <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">
            ✕
          </button>
        </div>

        <form @submit.prevent="savePermissions" class="mt-5 space-y-5">
          <!-- Rol Seçimi -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Kullanıcı Rolü</label>
            <select
              v-model="form.role"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl text-sm py-2.5 px-3 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="admin">Admin (Tüm yetkiler & sınırsız push)</option>
              <option value="developer">Developer (Sadece izin verilen branch'lere push)</option>
              <option value="viewer">Viewer (Sadece izleme)</option>
            </select>
          </div>

          <!-- Branch İzinleri -->
          <div v-if="form.role !== 'admin'">
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                İzin Verilen Branch'ler (Allowed Branches)
              </label>
              <span class="text-[11px] text-gray-400">Virgülle ayırın</span>
            </div>

            <input
              v-model="form.allowed_branches"
              type="text"
              placeholder="örn: dev, staging, feature/* veya * (hepsi)"
              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl text-sm py-2.5 px-3 font-mono focus:ring-2 focus:ring-indigo-500"
            />

            <!-- Hızlı Ekleme Çipleri -->
            <div class="flex flex-wrap gap-1.5 mt-2.5 items-center">
              <span class="text-[11px] text-gray-400">Hızlı Ekle:</span>
              <button
                type="button"
                @click="quickSetBranch('*')"
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-[11px] font-mono text-gray-700 dark:text-gray-200"
              >
                * (Tümü)
              </button>
              <button
                type="button"
                @click="quickSetBranch('dev')"
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-[11px] font-mono text-gray-700 dark:text-gray-200"
              >
                + dev
              </button>
              <button
                type="button"
                @click="quickSetBranch('develop')"
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-[11px] font-mono text-gray-700 dark:text-gray-200"
              >
                + develop
              </button>
              <button
                type="button"
                @click="quickSetBranch('staging')"
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-[11px] font-mono text-gray-700 dark:text-gray-200"
              >
                + staging
              </button>
              <button
                type="button"
                @click="quickSetBranch('feature/*')"
                class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded text-[11px] font-mono text-gray-700 dark:text-gray-200"
              >
                + feature/*
              </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">
              Kullanıcı sadece burada listelenen branch'lere masaüstü uygulaması üzerinden dosya push edebilir. <code class="font-mono text-indigo-500">main</code> branch'ini yazmazsanız, doğrudan production/main branch'ine push yapamaz.
            </p>
          </div>

          <div v-else class="p-3 bg-purple-50 dark:bg-purple-950/40 rounded-xl text-xs text-purple-700 dark:text-purple-300">
            👑 Yönetici rolündeki kullanıcılar güvenlik gereği tüm repo ve branch'lere sınırsız yükleme yetkisine sahiptir.
          </div>

          <!-- Butonlar -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <button
              type="button"
              @click="closeEditModal"
              class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-semibold transition-colors"
            >
              Vazgeç
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-colors disabled:opacity-50"
            >
              {{ form.processing ? 'Kaydediliyor...' : 'Yetkileri Kaydet' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>
