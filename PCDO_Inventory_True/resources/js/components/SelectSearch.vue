<!-- src/components/SelectSearch.vue -->

<template>
    <div class="relative w-full" ref="root">

        <div class="input-wrap">
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

            <!-- CLEAR BUTTON -->
            <button
                v-if="!disabled && searchValue"
                type="button"
                class="clear-btn"
                @click.stop="clearSelection"
                aria-label="Clear selection"
            >
                ×
            </button>
        </div>

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

.input-wrap {
  position: relative;
  width: 100%;
}

.select-input {
  width: 100%;
  border: 1px solid #989696;
  border-radius: 6px;
  padding: 10px;
  padding-right: 36px;
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

.clear-btn {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 18px;
  line-height: 1;
  color: #666;
  padding: 0;
}

.clear-btn:hover {
  color: #000;
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

const searchValue = ref<string>(String(props.modelValue ?? ''))
const open = ref(props.open)
const root = ref<HTMLElement | null>(null)

/**
 * Store previous selected value (safe restore)
 */
const previousLabel = ref<string>('')
const previousValue = ref<string | number | ''>('')

watch(() => props.open, v => {
	open.value = v
})

watch(
	() => props.modelValue,
	(val) => {
		if (val === '' || val === null || val === undefined) {
			searchValue.value = ''
			previousLabel.value = ''
			previousValue.value = ''
			return
		}

		const match = props.items.find(i => itemKey(i) === val)
		const label = match ? itemLabel(match) : ''

		searchValue.value = label
		previousLabel.value = label
		previousValue.value = val
	},
	{ immediate: true }
)

const filtered = computed(() => {
	if (!searchValue.value) return props.items

	const q = searchValue.value.toLowerCase()
	return props.items.filter(it => {
		const text = itemLabel(it).toLowerCase()
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
	previousLabel.value = label
	previousValue.value = id

	open.value = false
	emits('update:open', false)
	emits('update:search', label)
}

function onInput() {
	const q = searchValue.value.toLowerCase()

	const match = props.items.find(
		it => itemLabel(it).toLowerCase() === q
	)

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
		previousLabel.value = searchValue.value
		previousValue.value = props.modelValue

		searchValue.value = ''
		open.value = true
		emits('update:open', true)
	}
}

function restorePreviousIfNeeded() {
	if (!searchValue.value) {
		searchValue.value = previousLabel.value
		emits('update:modelValue', previousValue.value)
	}

	open.value = false
	emits('update:open', false)
}

function clearSelection() {
	searchValue.value = ''
	previousLabel.value = ''
	previousValue.value = ''

	emits('update:modelValue', '')
	emits('update:search', '')
	open.value = false
	emits('update:open', false)
}

function handleClickOutside(event: MouseEvent) {
	if (!root.value) return

	if (!root.value.contains(event.target as Node)) {
		restorePreviousIfNeeded()
	}
}

onMounted(() => {
	document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
	document.removeEventListener('mousedown', handleClickOutside)
})
</script>