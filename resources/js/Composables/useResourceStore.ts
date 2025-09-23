import { ref } from 'vue'
import { api } from '@/Services/api'
import type { UserResources } from '@/types/types'

let userResources: ReturnType<typeof ref<UserResources[]>> | null = null

export function useResourceStore() {
    if (!userResources) {
        userResources = ref<UserResources[]>([])
    }

    async function refreshResources() {
        const { data, error } = await api.userResources.getUserResources()
        if (!error) {
            if (Array.isArray(data)) {
                userResources!.value = data
            } else if (data && Array.isArray(data.userResources)) {
                userResources!.value = data.userResources
            } else {
                userResources!.value = []
            }
        }
    }

    return {
        userResources: userResources!,
        refreshResources,
    }
}
