<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  repos: Array,
  githubConnected: Boolean,
  githubUsername: String,
});

const form = useForm({
  repo_full_name: '',
  branch: '',
  file_path: '',
  commit_message: '',
  file: null,
  recipient_ids: [],
});

const branches = ref([]);
const loadingBranches = ref(false);
const dragOver = ref(false);
const selectedFileName = ref('');
const allUsers = ref([]);
const loadingUsers = ref(false);

watch(() => form.repo_full_name, async (newVal) => {
  if (!newVal) {
    branches.value = [];
    form.branch = '';
    return;
  }
  form.branch = '';
  branches.value = [];
  loadingBranches.value = true;
  const [owner, repo] = newVal.split('/');
  try {
    const res = await axios.get('/push/branches', { params: { owner, repo } });
    branches.value = res.data.branches || [];
    const selectedRepo = props.repos?.find(r => r.full_name === newVal);
    if (selectedRepo && branches.value.includes(selectedRepo.default_branch)) {
      form.branch = selectedRepo.default_branch;
    } else if (branches.value.length > 0) {
      form.branch = branches.value[0];
    }
  } catch (e) {
    console.error('Branch listesi alınamadı:', e);
  } finally {
    loadingBranches.value = false;
  }
});

const handleFileSelect = (event) => {
  const file = event.target.files?.[0];
  if (file) {
    form.file = file;
    selectedFileName.value = file.name;
    if (!form.file_path) {
      form.file_path = file.name;
    }
  }
};

const handleDrop = (event) => {
  dragOver.value = false;
  const file = event.dataTransfer?.files?.[0];
  if (file) {
    form.file = file;
    selectedFileName.value = file.name;
    if (!form.file_path) {
      form.file_path = file.name;
    }
  }
};

onMounted(async () => {
  loadingUsers.value = true;
  try {
    const res = await axios.get('/api/users');
    allUsers.value = res.data || [];
  } catch (e) {
    console.warn('Ekip üyeleri listesi alınamadı:', e);
  } finally {
    loadingUsers.value = false;
  }
});

const toggleRecipient = (userId) => {
  const idx = form.recipient_ids.indexOf(userId);
  if (idx === -1) {
    form.recipient_ids.push(userId);
  } else {
    form.recipient_ids.splice(idx, 1);
  }
};

const submit = () => {
  form.post('/push', {
    forceFormData: true,
  });
};

const fileSizeMB = computed(() => {
  if (!form.file) return null;
  return (form.file.size / 1024 / 1024).toFixed(2);
});
</script>

<template>
  <AppLayout>
    <template #header>🚀 Dosya Push Et</template>
    
    <!-- GitHub Hesabı Bağlı Değilse -->
    <div v-if="!githubConnected" class="max-w-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start gap-4">
        <span class="text-3xl">⚠️</span>
        <div>
          <h3 class="text-base font-bold text-amber-900 dark:text-amber-200">GitHub Hesabınız Bağlı Değil</h3>
          <p class="text-sm text-amber-700 dark:text-amber-400 mt-1 leading-relaxed">
            Repolarınıza erişebilmek ve dosya push edebilmek için GitHub yetkilendirmesi yapmanız gerekmektedir.
          </p>
          <a href="/auth/github"
            class="mt-4 inline-flex items-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-black text-white text-sm font-semibold rounded-xl shadow transition-colors">
            <span>⚡ GitHub ile Hesabımı Bağla</span>
          </a>
        </div>
      </div>
    </div>
    
    <!-- Push Formu -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">
      <!-- Sol 2 Kolon: Form Alanları -->
      <div class="lg:col-span-2 space-y-5">
        
        <!-- Repo Seçimi -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
            1. Hedef Repository
          </label>
          <select
            v-model="form.repo_full_name"
            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5 px-3"
          >
            <option value="">-- Bir repo seçiniz --</option>
            <option v-for="repo in repos" :key="repo.id" :value="repo.full_name">
              {{ repo.full_name }} {{ repo.private ? '🔒 (Gizli)' : '🌐 (Açık)' }}
            </option>
          </select>
          <p v-if="form.errors.repo_full_name" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.repo_full_name }}</p>
        </div>
        
        <!-- Branch Seçimi -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
            2. Hedef Branch
          </label>
          <select
            v-model="form.branch"
            :disabled="!form.repo_full_name || loadingBranches"
            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 py-2.5 px-3 disabled:opacity-50 disabled:bg-gray-100 dark:disabled:bg-gray-900"
          >
            <option value="">{{ loadingBranches ? 'Branch listesi alınıyor...' : '-- Branch seçiniz --' }}</option>
            <option v-for="branch in branches" :key="branch" :value="branch">
              🌿 {{ branch }}
            </option>
          </select>
          <p v-if="form.errors.branch" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.branch }}</p>
        </div>
        
        <!-- Dosya Seçimi -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
            3. Yüklenecek Dosya
          </label>
          <div
            :class="['border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-colors', dragOver ? 'border-indigo-500 bg-indigo-50/50' : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400']"
            @dragover.prevent="dragOver = true"
            @dragleave="dragOver = false"
            @drop.prevent="handleDrop"
          >
            <input type="file" id="fileInput" class="hidden" @change="handleFileSelect" />
            <label for="fileInput" class="cursor-pointer block">
              <div v-if="!selectedFileName">
                <div class="text-3xl mb-1">📄</div>
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Dosya seçmek için tıklayın veya sürükleyin</p>
                <p class="text-xs text-gray-400 mt-1">Maksimum 25 MB (GitHub API limiti)</p>
              </div>
              <div v-else class="flex items-center justify-center gap-3">
                <span class="text-2xl">📦</span>
                <div class="text-left">
                  <p class="text-sm font-bold text-gray-900 dark:text-white">{{ selectedFileName }}</p>
                  <p class="text-xs text-gray-500">{{ fileSizeMB }} MB</p>
                </div>
              </div>
            </label>
          </div>
          <p v-if="form.errors.file" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.file }}</p>

          <!-- Dosya Yolu (Repo İçi Path) -->
          <div class="mt-4">
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1">
              Repo İçi Kayıt Yolu (Dosya Adı Dahil)
            </label>
            <input
              v-model="form.file_path"
              type="text"
              placeholder="örn: src/app/index.js veya config.json"
              class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm px-3 py-2 font-mono"
            />
            <p v-if="form.errors.file_path" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.file_path }}</p>
          </div>
        </div>
        
        <!-- Commit Mesajı -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
            4. Commit Mesajı <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="form.commit_message"
            rows="3"
            placeholder="Yapılan değişikliği anlatan açıklama..."
            class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg text-sm p-3 focus:ring-indigo-500 focus:border-indigo-500"
          ></textarea>
          <div class="flex justify-between items-center text-xs text-gray-400 mt-1">
            <span>Zorunlu alan</span>
            <span>{{ form.commit_message.length }}/500</span>
          </div>
          <p v-if="form.errors.commit_message" class="text-rose-600 text-xs mt-1 font-medium">{{ form.errors.commit_message }}</p>
        </div>
        
        <!-- Push Butonu -->
        <button
          type="button"
          @click="submit"
          :disabled="form.processing || !form.repo_full_name || !form.branch || !form.file || !form.commit_message"
          class="w-full py-3.5 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shadow-md disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2"
        >
          <span v-if="form.processing">⏳ Push Yapılıyor ve Bildirimler Hazırlanıyor...</span>
          <span v-else>🚀 GitHub'a Gönder & Bildirimleri İlet</span>
        </button>
      </div>
      
      <!-- Sağ 1 Kolon: Ekip Bildirim Alıcıları -->
      <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm h-fit">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white uppercase tracking-wider mb-1 flex items-center gap-2">
          <span>🔔</span> Ekip Bildirimi
        </h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
          Push tamamlandıktan sonra bu kullanıcılara otomatik e-posta ve mobil bildirim iletilir.
        </p>

        <div v-if="allUsers.length > 0" class="space-y-2 max-h-96 overflow-y-auto">
          <label
            v-for="user in allUsers"
            :key="user.id"
            class="flex items-center gap-3 p-2.5 rounded-lg border border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors"
          >
            <input
              type="checkbox"
              :value="user.id"
              :checked="form.recipient_ids.includes(user.id)"
              @change="toggleRecipient(user.id)"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
            />
            <div class="min-w-0">
              <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ user.name }}</p>
              <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ user.email }}</p>
            </div>
          </label>
        </div>
        <div v-else-if="loadingUsers" class="text-center py-6 text-xs text-gray-400">
          Kullanıcılar yükleniyor...
        </div>
        <div v-else class="text-center py-6 text-xs text-gray-400 bg-gray-50 dark:bg-gray-900/40 rounded-lg p-3">
          Sistemde kayıtlı başka ekip üyesi bulunamadı.
        </div>
      </div>
    </div>
  </AppLayout>
</template>
