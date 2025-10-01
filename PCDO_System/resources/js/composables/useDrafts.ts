import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'

export function useDrafts(form: any, type: string) {
    const drafts = ref<any[]>([])
    const STORAGE_KEY = `drafts_${type}`

    const saveDraftNow = () => {
        const data = form.data()
        if ((!(data.id + '').trim()) && (!(data.name + '').trim())) return

        const allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        const draftId = data.id || form.id || form.tempId || Date.now().toString()

        let draft = allDrafts.find((d: any) => d.id === draftId && d.type === type)
        if (!draft) {
            draft = { id: draftId, type, data: {}, name: '', savedAt: '' }
            allDrafts.push(draft)
        }

        if (JSON.stringify(draft.data) === JSON.stringify(data)) return
        draft.data = { ...data }
        draft.name = data.name || 'Untitled'
        draft.savedAt = new Date().toLocaleString()

        localStorage.setItem(STORAGE_KEY, JSON.stringify(allDrafts))
        drafts.value = allDrafts
    }

    const saveDraft = debounce(saveDraftNow, 5000)

    watch(() => form.data(), () => saveDraft(), { deep: true })

    onMounted(() => {
        drafts.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        window.addEventListener('beforeunload', saveDraftNow)
        router.on('before', () => saveDraftNow())
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', saveDraftNow)
    })

    const useDraft = (draft: any) => {
        if (typeof form.reset === 'function') {
            form.reset()
        }

        if (typeof form.setData === 'function') {
            form.setData({ ...draft.data })
        } else {
            Object.assign(form, draft.data)
        }
    }

    const deleteDraft = (draftId: string) => {
        const allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        const updated = allDrafts.filter((d: any) => !(d.id === draftId && d.type === type))
        localStorage.setItem(STORAGE_KEY, JSON.stringify(updated))
        drafts.value = updated
    }

    const clearDrafts = () => {
        ; (saveDraft as any).cancel?.()
        localStorage.removeItem(STORAGE_KEY)
        drafts.value = []
    }

    const wrapSubmit = (method: 'post' | 'put' | 'patch' | 'delete') => {
        return (url: string, options: any = {}) => {
            const draftId = form.data().id || form.id || form.tempId
            const originalOnSuccess = options.onSuccess
            options.onSuccess = (...args: any[]) => {
                if (draftId) deleteDraft(draftId)
                if (originalOnSuccess) originalOnSuccess(...args)
            }
            return form[method](url, options)
        }
    }

    return {
        drafts,
        useDraft,
        deleteDraft,
        clearDrafts,
        post: wrapSubmit('post'),
        put: wrapSubmit('put'),
        patch: wrapSubmit('patch'),
        delete: wrapSubmit('delete'),
    }
}