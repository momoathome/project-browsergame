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
const selectedUser = ref(null);
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
    <div class="bg-base rounded-xl w-full border-primary border-4 border-solid">
        <div class="flex justify-between items-center p-4 border-b border-primary bg-base-dark rounded-t-xl">
            <h2 class="text-xl font-semibold text-light">
                Users
            </h2>
            <SecondaryButton type="button" @click="showResetAllUsersModal = true">
                alle User-Daten zurücksetzen
            </SecondaryButton>
        </div>
        <table class="w-full text-light mt-1">
            <thead class="text-gray-400 border-b border-primary">
                <tr>
                    <th class="text-left p-2">ID</th>
                    <th class="text-left p-2">Name</th>
                    <th class="text-left p-2">Email</th>
                    <th class="text-left p-2">Status</th>
                    <th class="text-left p-2">Last Login</th>
                    <th class="text-left p-2">Action</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id" class="hover:bg-base-dark transition-colors group"
                @click="() => { router.visit(route('admin.user.show', { id: user.id })) }">
                    <td class="p-2 cursor-pointer">{{ user.id }}</td>
                    <td class="p-2 cursor-pointer">{{ user.name }}</td>
                    <td class="p-2 cursor-pointer">{{ user.email }}</td>
                    <td class="p-2 cursor-pointer">{{ user.status }}</td>
                    <td class="p-2 cursor-pointer">{{ user.last_login }}</td>
                    <td class="p-2 text-center">
                        <button @click.stop="openResetModal(user)" title="User-Daten zurücksetzen" class="text-red-500 hover:text-red-700 p-1 rounded transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" width="20" height="20" viewBox="0 0 24 24"><
                                <path fill="currentColor" d="M12 3c-4.963 0-9 4.038-9 9s4.037 9 9 9s9-4.038 9-9s-4.037-9-9-9m0 16c-3.859 0-7-3.14-7-7s3.141-7 7-7s7 3.14 7 7s-3.141 7-7 7m.707-7l2.646-2.646a.5.5 0 0 0 0-.707a.5.5 0 0 0-.707 0L12 11.293L9.354 8.646a.5.5 0 0 0-.707.707L11.293 12l-2.646 2.646a.5.5 0 0 0 .707.708L12 12.707l2.646 2.646a.5.5 0 1 0 .708-.706z"/>
                            </svg>
                        </button>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="border-t border-primary bg-primary rounded-b-xl">
                    <td class="px-2 py-3" colspan="6">
                        Total Users: {{ users.length }}
                    </td>
                </tr>
            </tfoot>
        </table>

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
                <SecondaryButton @click="showResetAllUsersModal = false">
                    Abbrechen
                </SecondaryButton>
                <PrimaryButton @click="resetAllUsersData">
                    Bestätigen
                </PrimaryButton>
                </div>
            </template>
        </ConfirmationModal>
    </div>
</template>
