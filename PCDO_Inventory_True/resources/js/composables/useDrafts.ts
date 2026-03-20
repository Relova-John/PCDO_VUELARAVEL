import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import debounce from 'lodash/debounce'

export function useDrafts(form: any, key: string) {
    const STORAGE_KEY = `drafts_${key}`
    const drafts = ref<any[]>([])
    const currentDraftId = ref<string | null>(null)

    function serializeDraftData(data: any) {
        return {
            ...data,
            inventoryItem: (data.inventoryItem || []).map((item: any) => ({
                ...item,

                item_picture_meta: item.item_picture
                    ? {
                          name: item.item_picture.name,
                          size: item.item_picture.size,
                          type: item.item_picture.type
                      }
                    : null,

                moa_file_meta: item.moa_file
                    ? {
                          name: item.moa_file.name,
                          size: item.moa_file.size,
                          type: item.moa_file.type
                      }
                    : null,

                item_picture: null,
                moa_file: null
            }))
        }
    }

    function deserializeDraftData(data: any) {
        return {
            ...data,
            inventoryItem: (data.inventoryItem || []).map((item: any) => ({
                ...item,
                item_picture: null,
                moa_file: null
            }))
        }
    }

    function loadDrafts() {
        drafts.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
    }

    function saveDraftNow() {
        const raw = form.data()
        const data = serializeDraftData(raw)

        if (!data.name && (!data.inventoryItem || data.inventoryItem.length === 0)) return

        let allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')

        if (!currentDraftId.value) {
            currentDraftId.value = `draft_${Date.now()}_${Math.random().toString(36).slice(2)}`
        }

        let existing = allDrafts.find((d: any) => d.id === currentDraftId.value)

        if (!existing) {
            existing = {
                id: currentDraftId.value,
                name: data.name || 'Untitled Draft',
                data: {},
                savedAt: ''
            }
            allDrafts.push(existing)
        }

        existing.name = data.name || 'Untitled Draft'
        existing.data = data
        existing.savedAt = new Date().toLocaleString()

        localStorage.setItem(STORAGE_KEY, JSON.stringify(allDrafts))
        drafts.value = allDrafts
    }

    const saveDraft = debounce(saveDraftNow, 4000)

    watch(
        form,
        () => {
            saveDraft()
            console.log('Form changed, draft will be saved after debounce: ', form.data())
        },
        { deep: true }
    )

    function useDraft(draft: any) {
        currentDraftId.value = draft.id

        const restored = deserializeDraftData(JSON.parse(JSON.stringify(draft.data)))

        if (typeof form.reset === 'function') form.reset()

        if (typeof form.setData === 'function') {
            form.setData(restored)
        } else {
            Object.assign(form, restored)
        }
    }

    function deleteDraft(id: string) {
        const allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        const updated = allDrafts.filter((d: any) => d.id !== id)

        localStorage.setItem(STORAGE_KEY, JSON.stringify(updated))
        drafts.value = updated

        if (currentDraftId.value === id) {
            currentDraftId.value = null
        }
    }

    function clearDrafts() {
        localStorage.removeItem(STORAGE_KEY)
        drafts.value = []
        currentDraftId.value = null
    }

    onMounted(() => {
        loadDrafts()
        window.addEventListener('beforeunload', saveDraftNow)
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', saveDraftNow)
    })

    return {
        drafts,
        useDraft,
        deleteDraft,
        clearDrafts
    }
}