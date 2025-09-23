import { ref } from 'vue'
import { api } from '@/Services/api'
import type { UserAttributes } from '@/types/types'

let userAttributes: ReturnType<typeof ref<UserAttributes[]>> | null = null

export function useAttributeStore() {
    if (!userAttributes) {
        userAttributes = ref<UserAttributes[]>([])
    }

    async function refreshAttributes() {
        const { data, error } = await api.userAttributes.getUserAttributes()
        if (!error) {
            if (Array.isArray(data)) {
                userAttributes!.value = data
            } else if (data && Array.isArray(data.userAttributes)) {
                userAttributes!.value = data.userAttributes
            } else {
                userAttributes!.value = []
            }
        }
    }

    return {
        userAttributes: userAttributes!,
        refreshAttributes,
    }
}
