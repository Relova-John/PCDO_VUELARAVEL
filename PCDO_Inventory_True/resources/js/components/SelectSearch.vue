<!-- src/components/SelectSearch.vue -->

<template>
    <div class="relative w-full" ref="root">

        <input
            v-bind="$attrs"
            :id="id"
            v-model="searchValue"
            :placeholder="placeholder"
            @focus="openLocal()"
            @input="onInput"
            :disabled="disabled"
            class="select-input"
        />

        <div
            v-if="open && filtered.length > 0"
            class="select-dropdown"
        >
            <ul>
                <li
                    v-for="item in filtered"
                    :key="itemKey(item)"
                    @click="selectItem(item)"
                    class="select-option"
                >
                    {{ itemLabel(item) }}
                </li>
            </ul>
        </div>

    </div>
</template>

<style scoped>

.select-search {
  position: relative;
  width: 100%;
}

.select-input {
  width: 100%;
  border: 1px solid #989696;
  border-radius: 6px;
  padding: 10px;
  font-size: 14px;
}

.select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid #dadce0;
  border-radius: 6px;
  margin-top: 4px;
  max-height: 220px;
  overflow-y: auto;
  z-index: 1000;
  box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.select-option {
  padding: 8px 12px;
  cursor: pointer;
}

.select-option:hover {
  background: #f1f3f4;
}

.select-input:focus {
  outline: none;
  border-color: #673ab7;
  box-shadow: 0 0 0 2px rgba(103,58,183,0.15);
}

</style>


<script setup lang="ts">
import { ref, watch, computed, onMounted, onUnmounted } from 'vue'

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
watch(
	() => props.modelValue,
	(val) => {
		if (!val) {
			searchValue.value = ''
			return
		}
		const match = props.items.find(i => itemKey(i) === val)
		searchValue.value = match ? itemLabel(match) : ''
	},
	{ immediate: true }
)
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

const root = ref<HTMLElement | null>(null)

function handleClickOutside(event: MouseEvent) {
    if (!root.value) return
    if (!root.value.contains(event.target as Node)) {
        open.value = false
        emits('update:open', false)
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside)
})
</script>