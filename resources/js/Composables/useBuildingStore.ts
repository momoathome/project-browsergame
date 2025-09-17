import { ref } from 'vue'
import { api } from '@/Services/api'
import type { Building } from '@/types/types'

let buildings: ReturnType<typeof ref<Building[]>> | null = null

export function useBuildingStore() {
    if (!buildings) {
        buildings = ref<Building[]>([])
    }

    async function refreshBuildings() {
        const { data, error } = await api.buildings.getBuildings()
        if (!error) {
            if (Array.isArray(data)) {
                buildings!.value = data
            } else if (data && Array.isArray(data.buildings)) {
                buildings!.value = data.buildings
            } else {
                buildings!.value = []
            }
        }
    }

    return {
        buildings: buildings!,
        refreshBuildings,
    }
}
