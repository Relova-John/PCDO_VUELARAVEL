import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'

export function useDrafts(form: any, type: string) {
    const drafts = ref<any[]>([])
    const STORAGE_KEY = `drafts_${type}`
    const sessionDraftId = ref<string>('')

    const ensureSessionDraftId = () => {
        if (!sessionDraftId.value) {
            sessionDraftId.value = `unsaved_${type}_${Date.now()}_${Math.random().toString(36).slice(2)}`
        }
        return sessionDraftId.value
    }

    const saveDraftNow = () => {
        const data = form.data()
        let draftName = ''
        if (type === 'members') {
            draftName = [data.first_name, data.middle_name, data.last_name].filter(Boolean).join(' ').trim()
        } else if (type === 'cooperatives') {
            draftName = (data.name || '').trim()
        }
        if (!data.id && !draftName) return

        const allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        const currentDraftId = data.id || sessionDraftId.value || ensureSessionDraftId()

        let draft = allDrafts.find((d: any) => d.id === currentDraftId)

        if (!draft && data.id) {
            const unsavedDraftIndex = allDrafts.findIndex((d: any) => d.id === sessionDraftId.value)
            if (unsavedDraftIndex !== -1) {
                draft = allDrafts[unsavedDraftIndex]
                draft.id = data.id
            }
        }

        if (!draft) {
            draft = { id: currentDraftId, type, data: {}, name: '', savedAt: '' }
            allDrafts.push(draft)
        }

        if (JSON.stringify(draft.data) === JSON.stringify(data)) return

        draft.data = { ...data }
        draft.name = draftName || 'Untitled'
        draft.savedAt = new Date().toLocaleString()

        const updated = allDrafts.map((d: any) => (d.id === draft.id ? draft : d))
        localStorage.setItem(STORAGE_KEY, JSON.stringify(updated))
        drafts.value = updated
    }

    const saveDraft = debounce(saveDraftNow, 5000)

    watch(() => form.data(), () => saveDraft(), { deep: true })

    onMounted(() => {
        drafts.value = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        window.addEventListener('beforeunload', saveDraftNow)
        router.on('start', () => saveDraftNow())
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', saveDraftNow)
    })

    const useDraft = (draft: any) => {
        sessionDraftId.value = draft.id
        if (typeof form.reset === 'function') form.reset()
        if (typeof form.setData === 'function') form.setData({ ...draft.data })
        else Object.assign(form, draft.data)
    }

    const deleteDraft = (draftId: string | number) => {
        const allDrafts = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
        const updated = allDrafts.filter((d: any) => String(d.id) !== String(draftId))
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
            (saveDraft as any).cancel?.()
            const draftId = form.data().id || sessionDraftId.value
            const originalOnSuccess = options.onSuccess

            options.onSuccess = (...args: any[]) => {
                setTimeout(() => {
                    if (draftId) deleteDraft(draftId)
                    sessionDraftId.value = ''
                }, 300)
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
