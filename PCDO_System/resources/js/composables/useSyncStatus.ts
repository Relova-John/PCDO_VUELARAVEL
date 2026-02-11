import { ref, onMounted, onUnmounted } from 'vue'

export function useSyncStatus(
    url = '/ping',
    intervalMs = 10000
) {
    const isOnline = ref(false)
    let intervalId: ReturnType<typeof setInterval> | null = null

    async function refreshStatus() {
        try {
            const res = await fetch(url, { cache: 'no-cache' })
            if (!res.ok) throw new Error()
            const data = await res.json()
            isOnline.value = data.pong === true
        } catch {
            isOnline.value = false
        }
    }

    onMounted(() => {
        refreshStatus()
        if (!intervalId)
            intervalId = setInterval(refreshStatus, intervalMs)
    })

    onUnmounted(() => {
        if (intervalId) {
            clearInterval(intervalId)
            intervalId = null
        }
    })

    return { isOnline, refreshStatus }
}
