<!-- src/components/SelectSearch.vue -->

<template>
    <div class="relative w-full" ref="root">
        <div class="input-wrap">
            <input
                v-bind="$attrs"
                :id="id"
                v-model="searchValue"
                :placeholder="placeholder"
                @focus="openLocal"
                @input="onInput"
                :disabled="disabled"
                class="select-input"
            />

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

        <div v-if="open && filtered.length > 0" class="select-dropdown">
            <ul>
                <li
                    v-for="item in filtered"
                    :key="itemKey(item)"
                    @mousedown.prevent="selectItem(item)"
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

const emits = defineEmits([
    'update:modelValue',
    'select',
    'update:open',
    'update:search',
])

const searchValue = ref<string>('')
const open = ref(props.open)
const root = ref<HTMLElement | null>(null)
const isTyping = ref(false)

watch(() => props.open, v => {
    open.value = v
})

function itemLabel(it: any) {
    return typeof it === 'string' ? it : it?.[props.itemLabelKey] ?? ''
}

function itemKey(it: any) {
    return typeof it === 'string' ? it : it?.[props.itemKeyProp] ?? itemLabel(it)
}

/**
 * Sync external selected value into input text,
 * but do not overwrite while user is typing.
 */
watch(
    () => [props.modelValue, props.items],
    ([val]) => {
        if (isTyping.value) return

        if (val === '' || val === null || val === undefined) {
            searchValue.value = ''
            return
        }

        const match = props.items.find(i => String(itemKey(i)) === String(val))
        searchValue.value = match ? String(itemLabel(match)) : String(val)
    },
    { immediate: true, deep: true }
)

const filtered = computed(() => {
    if (!searchValue.value) return props.items

    const q = searchValue.value.toLowerCase()
    return props.items.filter(it =>
        String(itemLabel(it)).toLowerCase().includes(q)
    )
})

function selectItem(it: any) {
    const label = String(itemLabel(it))
    const id = itemKey(it)

    isTyping.value = false
    searchValue.value = label

    emits('update:modelValue', id)
    emits('update:search', label)
    emits('select', { name: label, id })

    open.value = false
    emits('update:open', false)
}

function onInput() {
    isTyping.value = true

    const raw = searchValue.value
    const q = raw.toLowerCase()

    const exactMatch = props.items.find(
        it => String(itemLabel(it)).toLowerCase() === q
    )

    if (exactMatch) {
        const label = String(itemLabel(exactMatch))
        const id = itemKey(exactMatch)

        emits('update:modelValue', id)
        emits('update:search', label)
    } else {
        emits('update:modelValue', '')
        emits('update:search', raw)
    }

    open.value = true
    emits('update:open', true)
}

function openLocal() {
    if (props.disabled) return

    isTyping.value = true
    open.value = true
    emits('update:open', true)

    /**
     * Do NOT clear searchValue here.
     * Clearing here is what causes the first typed key issue.
     */
}

function restorePreviousIfNeeded() {
    isTyping.value = false

    /**
     * When leaving the field:
     * - if there is an actual selected modelValue, show its label again
     * - otherwise keep custom typed text as-is
     */
    if (props.modelValue !== '' && props.modelValue !== null && props.modelValue !== undefined) {
        const match = props.items.find(i => String(itemKey(i)) === String(props.modelValue))
        searchValue.value = match ? String(itemLabel(match)) : String(props.modelValue)
    }

    open.value = false
    emits('update:open', false)
}

function clearSelection() {
    isTyping.value = false
    searchValue.value = ''

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