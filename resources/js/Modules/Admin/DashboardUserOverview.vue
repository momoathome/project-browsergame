<script lang="ts" setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import type { User } from '@/types/types';

const props = defineProps<{
    users: User[];
}>()

const showResetModal = ref(false);
const selectedUser = ref<User | null>(null);
const showResetAllUsersModal = ref(false);

const userResetForm = useForm({
    user_id: null
});

function openResetModal(user) {
    selectedUser.value = user;
    showResetModal.value = true;
}

function resetUserData() {
    if (!selectedUser.value) return;
    userResetForm.user_id = selectedUser.value.id;
    userResetForm.post(route('admin.user.reset', userResetForm.user_id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            userResetForm.reset();
            showResetModal.value = false;
            selectedUser.value = null;
        },
        onError: () => {
            // Fehlerbehandlung
        },
    });
}

function resetAllUsersData() {
  router.post(route('admin.users.reset'), {
    preserveState: true,
    preserveScroll: true,
  });
  showResetAllUsersModal.value = false;
}
</script>

<template>
    <div class="bg-base rounded-xl w-full h-max border border-primary/40 shadow-xl">
        <div class="flex justify-between items-center p-6 border-b border-primary/30 bg-base-dark rounded-t-xl">
            <h2 class="text-xl font-semibold text-light">Users</h2>
            <SecondaryButton type="button" @click="showResetAllUsersModal = true">
                Alle User-Daten zurücksetzen
            </SecondaryButton>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-light border-spacing-y-2">
                <thead class="text-secondary border-b border-primary">
                    <tr class="bg-base/40">
                        <th class="text-left px-4 py-2 font-medium">ID</th>
                        <th class="text-left px-4 py-2 font-medium">Name</th>
                        <th class="text-left px-4 py-2 font-medium">Email</th>
                        <th class="text-left px-4 py-2 font-medium">Status</th>
                        <th class="text-left px-4 py-2 font-medium">Last Login</th>
                        <th class="text-center px-4 py-2 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users" :key="user.id"
                        class="group bg-base/60 hover:bg-primary/10 transition-colors rounded-xl cursor-pointer"
                        @click="() => { router.visit(route('admin.user.show', { id: user.id })) }">
                        <td class="px-4 py-3 rounded-l-xl">{{ user.id }}</td>
                        <td class="px-4 py-3">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.email }}</td>
                        <td class="px-4 py-3">{{ user.status }}</td>
                        <td class="px-4 py-3">{{ user.last_login }}</td>
                        <td class="px-4 py-3 text-center rounded-r-xl">
                            <button @click.stop="openResetModal(user)" title="User-Daten zurücksetzen"
                                class="text-red-500 hover:text-red-700 p-2 rounded-lg transition-colors bg-base-dark/60 hover:bg-red-900/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="20" height="20" viewBox="0 0 24 24">
                                    <path fill="currentColor" d="M12 3c-4.963 0-9 4.038-9 9s4.037 9 9 9s9-4.038 9-9s-4.037-9-9-9m0 16c-3.859 0-7-3.14-7-7s3.141-7 7-7s7 3.14 7 7s-3.141 7-7 7m.707-7l2.646-2.646a.5.5 0 0 0 0-.707a.5.5 0 0 0-.707 0L12 11.293L9.354 8.646a.5.5 0 0 0-.707.707L11.293 12l-2.646 2.646a.5.5 0 0 0 .707.708L12 12.707l2.646 2.646a.5.5 0 1 0 .708-.706z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-primary/20 rounded-b-xl">
                        <td class="px-4 py-4 font-semibold text-secondary" colspan="6">
                            Total Users: {{ users.length }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <ConfirmationModal :show="showResetModal" @close="showResetModal = false">
            <template #title>
                User-Daten zurücksetzen
            </template>
            <template #content>
                <span v-if="selectedUser">
                    Bist du sicher, dass du die Daten von <b>{{ selectedUser.name }}</b> (ID: {{ selectedUser.id }}) zurücksetzen möchtest?
                </span>
            </template>
            <template #footer>
                <div class="flex gap-2">
                    <SecondaryButton @click="showResetModal = false">Abbrechen</SecondaryButton>
                    <PrimaryButton @click="resetUserData">Bestätigen</PrimaryButton>
                </div>
            </template>
        </ConfirmationModal>

        <ConfirmationModal :show="showResetAllUsersModal" @close="showResetAllUsersModal = false">
            <template #title>
                Alle User-Daten zurücksetzen
            </template>
            <template #content>
                Bist du sicher, dass du <b>alle</b> User-Daten zurücksetzen möchtest? Diese Aktion kann nicht rückgängig gemacht werden.
            </template>
            <template #footer>
                <div class="flex gap-2">
                    <SecondaryButton @click="showResetAllUsersModal = false">Abbrechen</SecondaryButton>
                    <PrimaryButton @click="resetAllUsersData">Bestätigen</PrimaryButton>
                </div>
            </template>
        </ConfirmationModal>
    </div>
</template>
