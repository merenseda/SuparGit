<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  users: Array,
});

const form = useForm({
  role: '',
});

const changeRole = (user, newRole) => {
  form.role = newRole;
  form.patch(`/admin/users/${user.id}/role`, {
    preserveScroll: true,
  });
};
</script>

<template>
  <AppLayout>
    <template #header>👥 Kullanıcı ve Rol Yönetimi</template>
    
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden max-w-5xl">
      <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
        <h3 class="font-bold text-gray-800 dark:text-white text-base">Ekip Üyeleri</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kullanıcıların erişim rollerini (Admin, Developer, Viewer) buradan yönetebilirsiniz.</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-xs">
          <thead>
            <tr class="bg-gray-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
              <th class="px-6 py-3">Üye</th>
              <th class="px-6 py-3">E-posta</th>
              <th class="px-6 py-3">GitHub</th>
              <th class="px-6 py-3">Mevcut Rol</th>
              <th class="px-6 py-3">Rol Değiştir</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
              <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                {{ user.name }}
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                {{ user.email }}
              </td>
              <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                <span v-if="user.github_username" class="text-emerald-600 dark:text-emerald-400 font-mono">@{{ user.github_username }}</span>
                <span v-else class="text-gray-400 italic">Bağlı değil</span>
              </td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded font-bold uppercase tracking-wider text-[10px] bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                  {{ user.role }}
                </span>
              </td>
              <td class="px-6 py-4">
                <select
                  :value="user.role"
                  @change="changeRole(user, $event.target.value)"
                  class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded text-xs py-1 px-2 focus:ring-indigo-500"
                >
                  <option value="admin">Admin</option>
                  <option value="developer">Developer</option>
                  <option value="viewer">Viewer</option>
                </select>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayout>
</template>
