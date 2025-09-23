<!-- src/components/SelectSearch.vue -->
<template>
	<div class="relative w-full" v-click-outside="onOutside" ref="root">
		<input
			:id="id"
			v-model="searchValue"
			:placeholder="placeholder"
			@focus="openLocal()"
			@input="onInput"
			:disabled="disabled"
				class="pl-9 pr-3 w-full p-3 rounded-xl border border-gray-300 dark:border-gray-700
				bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
				focus:outline-none focus:ring-2 focus:ring-indigo-500"
		/>
		<div v-if="open && filtered.length > 0" class="absolute w-full mt-1 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg max-h-60 overflow-y-auto rounded-md z-10">
			<ul>
				<li v-for="item in filtered" :key="itemKey(item)" @click="selectItem(item)" class="px-4 py-2 cursor-pointer hover:bg-gray-200">
					{{ itemLabel(item) }}
				</li>
			</ul>
		</div>
	</div>
</template>

<script setup lang="ts">
	import { ref, watch, computed } from 'vue'

	const props = defineProps({
		id: { type: String, default: '' },
		items: { type: Array as () => any[], default: () => [] },
		placeholder: { type: String, default: 'Search' },
		disabled: { type: Boolean, default: false },
		modelValue: { type: [String, Number], default: '' },
		open: { type: Boolean, default: false },
		itemLabelKey: { type: String, default: 'name' },
		itemKeyProp: { type: String, default: 'id' },
	})

	const emits = defineEmits(['update:modelValue', 'select', 'update:open', 'update:search'])
	const searchValue = ref(props.modelValue ?? '')
	const open = ref(props.open)

	watch(() => props.open, v => open.value = v)
	watch(() => props.modelValue, v => searchValue.value = v ?? '')
	watch(() => props.modelValue, (val) => {
		searchValue.value = val
			? props.items.find(i => itemKey(i) === val)?.[props.itemLabelKey] ?? ''
			: ''
	})
	
	const filtered = computed(() => {
		if (!searchValue.value) return props.items
			const q = searchValue.value.toString().toLowerCase()
			return props.items.filter(it => {
				const text = itemLabel(it).toString().toLowerCase()
				return text.includes(q)
			})
	})

	function itemLabel(it: any) {
		return typeof it === 'string' ? it : it[props.itemLabelKey]
	}

	function itemKey(it: any) {
		return typeof it === 'string' ? it : it[props.itemKeyProp] ?? itemLabel(it)
	}

	function selectItem(it: any) {
		const label = itemLabel(it)
		const id = itemKey(it)
		emits('update:modelValue', id)
		emits('select', { name: label, id })
		searchValue.value = label
		open.value = false
		emits('update:open', false)
		emits('update:search', label)
	}

	function onInput() {
		const q = searchValue.value.toString().toLowerCase()
		const match = props.items.find(it => itemLabel(it).toString().toLowerCase() === q)
		if (match) {
			selectItem(match)
		} else {
			emits('update:modelValue', '')
			emits('update:search', searchValue.value)
			open.value = true
			emits('update:open', true)
		}
	}
	function openLocal() {
		if (!props.disabled) {
		searchValue.value = ''
		open.value = true
		emits('update:open', true)
		}
	}
	function onOutside() {
		open.value = false
		emits('update:open', false)
	}
</script>

