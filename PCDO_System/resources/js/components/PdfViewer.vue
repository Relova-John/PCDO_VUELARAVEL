<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import * as pdfjsLib from 'pdfjs-dist'

pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.mjs'

const emit = defineEmits<{
    (e: 'error', err: any): void
    (e: 'load'): void
}>()

const props = defineProps<{
    url: string
    type?: 'member' | 'documentation' | 'checklist' | 'report'
    programId?: number | string
    cooperativeId?: number | string
    memberId?: number | string
    fileId?: number | string
}>()

const container = ref<HTMLDivElement | null>(null)

const loadPdf = async (url: string) => {
    if (!url) return
    if (container.value) container.value.innerHTML = ''

    try {
        const pdf = await pdfjsLib.getDocument(url).promise

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum)
            const viewport = page.getViewport({ scale: 1.2 })

            const canvas = document.createElement('canvas')
            const context = canvas.getContext('2d')!
            canvas.height = viewport.height
            canvas.width = viewport.width
            canvas.classList.add('max-w-full', 'block', 'mx-auto', 'mb-4')

            container.value?.appendChild(canvas)

            await page.render({
                canvasContext: context,
                viewport,
                canvas,
            }).promise
            const separator = document.createElement('div')
            separator.classList.add('border-t', 'border-gray-300', 'my-6')
            container.value?.appendChild(separator)
        }
        emit('load')
    } catch (err) {
        console.error('Failed to load PDF:', err)
        emit('error', err)
    }
}

onMounted(() => loadPdf(props.url))
watch(() => props.url, (newUrl) => loadPdf(newUrl))

// Dynamic download URL
const downloadUrl = computed(() => {
    if (props.type === 'member' && props.cooperativeId && props.memberId && props.fileId) {
        return `/cooperatives/${props.cooperativeId}/members/${props.memberId}/files/${props.fileId}/download`
    }
    if (props.type === 'checklist' && props.programId && props.cooperativeId && props.fileId) {
        return `/programs/${props.programId}/cooperatives/${props.cooperativeId}/checklist/${props.fileId}/download`
    }
    return `${props.url}?download=1`
})

const downloadPdf = () => {
    window.location.href = downloadUrl.value
}
</script>

<template>
    <div class="flex justify-center">
        <div class="relative border rounded-lg shadow-md max-h-[80vh] w-full max-w-4xl bg-white flex flex-col">
            <!-- Sticky Top Bar -->
            <div
                class="sticky top-0 z-10 flex items-center justify-between bg-gray-100 border-b border-gray-300 px-4 py-2">
                <span class="text-sm font-medium text-gray-700">PDF Preview</span>
                <button @click="downloadPdf"
                    class="px-3 py-1.5 text-sm font-semibold bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    Download
                </button>
            </div>

            <!-- Scrollable PDF container -->
            <div ref="container" class="overflow-y-auto flex-1 p-4"></div>
        </div>
    </div>
</template>
