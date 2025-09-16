import { ref, watch } from 'vue'
import { api } from '@/Services/api'
import { usePage } from '@inertiajs/vue3'
import type { Spacecraft } from '@/types/types'

let spacecrafts: ReturnType<typeof ref<Spacecraft[]>> | null = null

export function useSpacecraftStore() {
	const page = usePage()
	if (!spacecrafts) {
		// Initialisiere mit Page-Props, falls vorhanden
		spacecrafts = ref<Spacecraft[]>(Array.isArray(page.props.spacecrafts) ? page.props.spacecrafts : [])
	}

	// Watch auf page.props.spacecrafts, um bei Inertia-Redirects zu aktualisieren
	watch(
		() => page.props.spacecrafts,
		(newSpacecrafts) => {
			spacecrafts!.value = Array.isArray(newSpacecrafts) ? newSpacecrafts : []
		}
	)

	async function refreshSpacecrafts() {
		// Hole aktuelle Spacecrafts vom Backend (API muss existieren)
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
