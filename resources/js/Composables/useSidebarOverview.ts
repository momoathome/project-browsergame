import { ref, onMounted } from 'vue';

export default function useSidebarOverview(activeSidebar) {
    const showSidebarOverview = ref(false);

    onMounted(() => {
        const stored = localStorage.getItem('sideOverview');
        if (stored !== null) {
            showSidebarOverview.value = stored === 'true';
        }
    });

    function openSidebarOverview() {
        showSidebarOverview.value = true;
        activeSidebar.value = 'overview';
        localStorage.setItem('sideOverview', showSidebarOverview.value.toString());
    }

    function closeSidebarOverview() {
        showSidebarOverview.value = false;
        if (activeSidebar.value === 'overview') activeSidebar.value = null;
        localStorage.setItem('sideOverview', showSidebarOverview.value.toString());
    }

    return {
        showSidebarOverview,
        openSidebarOverview,
        closeSidebarOverview,
    };
}
