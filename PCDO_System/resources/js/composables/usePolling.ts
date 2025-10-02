import { onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";

export function usePolling(props: string[], intervalMs = 15000, shouldReload?: () => boolean) {
    let interval: number;

    onMounted(() => {
        interval = window.setInterval(() => {
            if (shouldReload && shouldReload()) return;
            router.reload({ only: props });
        }, intervalMs);
    });

    onUnmounted(() => {
        clearInterval(interval);
    });
}
