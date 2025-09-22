import { ref } from 'vue'
import { api } from '@/Services/api'
import type { Spacecraft } from '@/types/types'

let spacecrafts: ReturnType<typeof ref<Spacecraft[]>> | null = null

export function useSpacecraftStore() {
	if (!spacecrafts) {
		spacecrafts = ref<Spacecraft[]>([])
	}

	async function refreshSpacecrafts() {
		const { data, error } = await api.spacecrafts.getSpacecrafts()
		if (!error) {
			if (Array.isArray(data)) {
				spacecrafts!.value = data
			} else if (data && Array.isArray(data.spacecrafts)) {
				spacecrafts!.value = data.spacecrafts
			} else {
				spacecrafts!.value = []
			}
		}
	}

	return {
		spacecrafts: spacecrafts!,
		refreshSpacecrafts,
	}
}
