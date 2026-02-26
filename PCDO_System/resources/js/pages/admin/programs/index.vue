<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue'
import { BreadcrumbItem } from '@/types'
import { Head, Link, usePage, router } from '@inertiajs/vue3'
import { ref, onMounted, computed } from 'vue'
import PdfViewer from '@/components/PdfViewer.vue'
import type { Programs } from '@/types/cooperatives'
import { toast } from "vue-sonner"

const showFileModal = ref(false)
const pdfUrl = ref('/admin/programs/reports/monthly')
const closeFileModal = () => (showFileModal.value = false, pdfLoading.value = true, pdfFailed.value = false)
const pdfFailed = ref(false)
const openMenuId = ref<number | null>(null)

const toggleMenu = (id: number) => {
	openMenuId.value = openMenuId.value === id ? null : id
}

const closeMenu = () => {
	openMenuId.value = null
}

const props = defineProps<{
	programs: Programs[],
	breadcrumbs: BreadcrumbItem[]
}>()

const page = usePage();
const authUser = computed(() => (page.props.auth as any)?.user);
const userRoles = computed(() => authUser.value?.roles?.map((r: any) => r.name) || []);

// Dynamic gradients for each program
const fixedProgramGradients: Record<number, string> = {
	1: 'from-yellow-400 to-orange-500',
	2: 'from-blue-500 to-indigo-500',
	3: 'from-emerald-500 to-teal-600',
	4: 'from-red-400 to-pink-500',
	5: 'from-green-300 to-green-600'
}
const gradientPool = [
	'from-purple-500 to-violet-600',
	'from-cyan-500 to-blue-500',
	'from-fuchsia-500 to-pink-600',
	'from-amber-400 to-yellow-500',
	'from-lime-400 to-emerald-500',
	'from-sky-400 to-indigo-500',
	'from-rose-400 to-red-500',
	'from-teal-400 to-cyan-500',
]

const getProgramGradient = (id: number) => {
	if (fixedProgramGradients[id]) {
		return fixedProgramGradients[id]
	}

	const index = id % gradientPool.length
	return gradientPool[index]
}

function goToAddProgram() {
	router.visit('programs/create')
}

function goToEditProgram(programId: number) {
	router.visit(`programs/${programId}/edit`)
}

function archiveProgram(programId: number) {
	router.post(`programs/${programId}/archive`, {}, {
		preserveState: true,
		onSuccess: () => {
			toast.success(`${props.programs.find(p => p.id === programId)?.name} archived successfully!`)
		}
	})
}

function unarchiveProgram(programId: number) {
	router.post(`programs/${programId}/unarchive`, {}, {
		preserveState: true,
		onSuccess: () => {
			toast.success(`${props.programs.find(p => p.id === programId)?.name} unarchived successfully!`)
		}
	})

}

const selectedMonth = ref(new Date().toISOString().slice(0, 7))
const selectedProgram = ref('all')

const updateMonth = () => {
	pdfUrl.value = `programs/reports/monthly?month=${selectedMonth.value}&program_id=${selectedProgram.value}`
}

const pdfLoading = ref(true)

const isMobile = ref(false)

const activePrograms = computed(() => {
	return props.programs.filter(program => !program.archive)
})

const archivedPrograms = computed(() => {
	return props.programs.filter(program => program.archive)
})

onMounted(() => {
	const uaCheck = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
	const sizeCheck = window.matchMedia('(max-width: 768px)').matches
	isMobile.value = uaCheck || sizeCheck
})


</script>

<template>

	<Head title="Programs" />

	<AdminLayout :breadcrumbs="breadcrumbs">
		<div class="bg-gray-100/90 dark:bg-gray-900 rounded-3xl min-h-screen">
			<div class="px-5 md:px-8 pt-5 grid gap-6 md:grid-rows-[auto_1fr]">
				<div class="px-5 md:px-8 pt-5">
					<div class="flex flex-col gap-6 p-6">
						<template>
							<h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
								Programs Management
							</h1>

							<p class="text-gray-700 dark:text-gray-300">
								Manage and oversee all cooperative programs. Click on a program to view detailed
								information and
								performance metrics.
							</p>

						</template>
						<div class="flex flex-col sm:flex-row sm:justify-end sm:items-center gap-3 sm:gap-4 w-full">

							<!-- Button -->
							<button @click="showFileModal = true"
								class="w-full sm:w-auto px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium shadow transition">
								View Monthly Report
							</button>
						</div>

						<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
							<Link v-for="program in props.programs" :key="program.id"
								:href="`/admin/programs/${program.id}`" class="group relative rounded-2xl shadow-md border border-gray-300 dark:border-gray-700 
								bg-white dark:bg-gray-800 
								hover:shadow-2xl hover:-translate-y-1.5 
								transition-all duration-300 
								flex flex-col overflow-hidden">
								<!-- Dynamic Gradient Top Bar -->
								<div :class="`h-3 rounded-t-2xl bg-gradient-to-r ${getProgramGradient(program.id)}`">
								</div>

								<!-- Edit Menu Button -->
								<button @click.prevent="toggleMenu(program.id)"
									class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-semibold z-20 bg-white dark:bg-gray-700 px-2 py-1 rounded shadow">
									⋮
								</button>

								<!-- Dropdown Menu -->
								<div v-if="openMenuId === program.id"
									class="absolute top-10 right-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-md z-30 w-40">

									<button
										class="w-full text-left px-4 py-2 text-sm text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
										@click.prevent="() => { goToEditProgram(program.id); closeMenu() }">
										✏️ Edit Program
									</button>

									<button
										class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700"
										@click.prevent="() => { archiveProgram(program.id); closeMenu() }">
										📦 Archive Program
									</button>
								</div>

								<div class="p-5 flex flex-col flex-grow">
									<h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">
										{{ program.name }}
									</h2>

									<p class="text-gray-800 dark:text-gray-300 text-sm leading-relaxed mb-4">
										{{ program.details }}
									</p>

									<div class="mt-auto flex items-center justify-between gap-2">
										<span
											class="text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
											<Handshake class="w-4 h-4 text-gray-600 dark:text-gray-400" /> Active
											Cooperatives
										</span>
										<span class="px-3 py-1 rounded-full text-xs font-semibold 
									bg-blue-200 text-blue-800 
									dark:bg-blue-900 dark:text-blue-200 shadow-sm">
											{{ program.cooperatives_count }}
										</span>
									</div>
								</div>
							</Link>
							<button @click.prevent="goToAddProgram" class="group relative rounded-2xl shadow-md border border-dashed border-blue-400
							bg-gradient-to-br from-blue-300 to-indigo-100
							dark:from-blue-900/20 dark:to-indigo-900/20
							hover:shadow-2xl hover:-translate-y-1.5
							transition-all duration-300
							flex flex-col items-center justify-center
							min-h-[140px]">

								<div class="text-4xl text-blue-400 group-hover:scale-110 transition">
									+
								</div>
								<span class="mt-2 text-blue-700 font-semibold">
									Add Program
								</span>
							</button>
						</div>
					</div>
				</div>
				<Transition name="fade">
					<div v-if="showFileModal"
						class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4 sm:p-0"
						@click.self="closeFileModal">
						<div
							class="bg-white dark:bg-gray-900 rounded-2xl shadow-lg w-full max-w-4xl max-h-[90vh] overflow-hidden sm:m-0 m-auto">
							<!-- Header -->
							<header
								class="flex flex-wrap justify-between items-center border-b border-gray-200 dark:border-gray-700 p-4 gap-4">
								<h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">
									Monthly Program Report
								</h2>
								<button v-if="isMobile" @click="closeFileModal"
									class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl leading-none">
									✕
								</button>
								<div class="flex items-center gap-3 flex-wrap justify-end">
									<!-- Program Selector -->
									<select v-model="selectedProgram" @change="updateMonth" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
					text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
										<option value="all">All Programs</option>
										<option v-for="program in props.programs" :key="program.id" :value="program.id">
											{{ program.name }}
										</option>
									</select>

									<!-- Month Selector -->
									<input type="month" v-model="selectedMonth" @change="updateMonth" class="px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800
					text-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none" />

									<button v-if="!isMobile" @click="closeFileModal"
										class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xl leading-none">
										✕
									</button>
								</div>
							</header>

							<!-- Content -->
							<div class="p-4 overflow-auto max-h-[80vh] bg-gray-50 dark:bg-gray-800 rounded-b-2xl">
								<div v-if="pdfLoading"
									class="flex justify-center items-center h-[80vh] text-gray-600 dark:text-gray-300">
									<div
										class="animate-spin rounded-full h-10 w-10 border-4 border-gray-400 border-t-transparent">
									</div>
								</div>

								<!-- Desktop PDF -->
								<iframe v-if="!isMobile" :src="`${pdfUrl}`" class="w-full h-[75vh] rounded" key="pdfUrl"
									@load="pdfLoading = false"></iframe>

								<!-- Mobile PDF -->
								<template v-else>
									<PdfViewer v-if="!pdfFailed" :url="`${pdfUrl}`" type="report"
										@error="pdfFailed = true; pdfLoading = false" :key="pdfUrl"
										@load="pdfLoading = false" />

									<div v-else class="text-center text-gray-600 dark:text-gray-400">
										<p class="mb-2">PDF preview not supported on your device.</p>
										<a :href="`${pdfUrl}?download=1`"
											class="text-blue-600 hover:underline font-medium">Download the PDF
											file</a>
									</div>
								</template>
							</div>
						</div>
					</div>
				</Transition>
			</div>
			<div class="bg-gray-100/90 dark:bg-gray-900 rounded-3xl p-5 px-22">
				<h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100 mb-3">Archived Programs</h2>
				<div v-if="archivedPrograms.length === 0" class="text-gray-500 dark:text-gray-400">
					No archived programs
				</div>
				<div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
					<div v-for="program in archivedPrograms" :key="program.id" class="rounded-2xl shadow-md border border-gray-300 dark:border-gray-700
							bg-gray-50 dark:bg-gray-600
							hover:shadow-2xl hover:-translate-y-0.5 transform transition-all block relative">
						<!-- Dynamic Gradient Top Bar -->
						<div :class="`h-3 rounded-t-2xl bg-gradient-to-r ${getProgramGradient(program.id)}`">
						</div>

						<!-- Edit Menu Button -->
						<button @click.prevent="toggleMenu(program.id)"
							class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm font-semibold z-20 bg-white dark:bg-gray-700 px-2 py-1 rounded shadow">
							⋮
						</button>

						<!-- Dropdown Menu -->
						<div v-if="openMenuId === program.id"
							class="absolute top-10 right-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-md z-30 w-40">

							<button
								class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700"
								@click.prevent="() => { unarchiveProgram(program.id); closeMenu() }">
								📦 Unarchive Program
							</button>
						</div>

						<div class="p-5 flex flex-col h-full">
							<h2 class="text-xl font-bold mb-2 text-gray-900 dark:text-gray-100">
								{{ program.name }}
							</h2>

							<p class="text-gray-800 dark:text-gray-300 text-sm leading-relaxed mb-4">
								{{ program.details }}
							</p>

							<div class="mt-auto flex items-center justify-between gap-2">
								<span
									class="text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center gap-1">
									<Handshake class="w-4 h-4 text-gray-600 dark:text-gray-400" /> Recorded
									Cooperatives
								</span>
								<span class="px-3 py-1 rounded-full text-xs font-semibold 
									bg-blue-200 text-blue-800 
									dark:bg-blue-900 dark:text-blue-200 shadow-sm">
									{{ program.active_coop_count }}
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</AdminLayout>
</template>
