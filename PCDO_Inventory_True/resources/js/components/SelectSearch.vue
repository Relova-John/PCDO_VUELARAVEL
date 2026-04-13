<template>
    <div class="select-search" ref="root">
        <div class="input-wrap">
            <input
                ref="inputEl"
                v-bind="$attrs"
                :id="id"
                :value="searchValue"
                :placeholder="placeholder"
                :disabled="disabled"
                class="select-input"
                autocomplete="off"
                @focus="handleFocus"
                @input="handleInput"
                @keydown.down.prevent="highlightNext"
                @keydown.up.prevent="highlightPrev"
                @keydown.enter.prevent="handleEnter"
                @keydown.esc.prevent="closeDropdown"
            />

            <button
                v-if="!disabled && searchValue"
                type="button"
                class="clear-btn"
                aria-label="Clear selection"
                @mousedown.prevent
                @click.stop="clearSelection"
            >
                ×
            </button>
        </div>

        <div v-if="open && filteredItems.length > 0" class="select-dropdown">
            <ul>
                <li
                    v-for="(item, index) in filteredItems"
                    :key="String(itemKey(item))"
                    class="select-option"
                    :class="{ active: index === highlightedIndex }"
                    @mousedown.prevent="selectItem(item)"
                    @mousemove="highlightedIndex = index"
                >
                    {{ itemLabel(item) }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

type Item = Record<string, any> | string | number

const props = defineProps({
    id: { type: String, default: '' },
    items: { type: Array as () => Item[], default: () => [] },
    placeholder: { type: String, default: 'Search' },
    disabled: { type: Boolean, default: false },

    modelValue: { type: [String, Number, null], default: '' },
    open: { type: Boolean, default: false },

    itemLabelKey: { type: String, default: 'name' },
    itemKeyProp: { type: String, default: 'id' },

    freeInput: { type: Boolean, default: false },
    clearOnFocus: { type: Boolean, default: false },
})

const emit = defineEmits([
    'update:modelValue',
    'update:open',
    'update:search',
    'select',
])

const root = ref<HTMLElement | null>(null)
const inputEl = ref<HTMLInputElement | null>(null)

const open = ref(props.open)
const searchValue = ref('')
const highlightedIndex = ref(-1)
const isFocused = ref(false)
const suppressRestoreOnce = ref(false)

watch(
    () => props.open,
    (value) => {
        open.value = value
        if (!value) {
            highlightedIndex.value = -1
        }
    }
)

function itemLabel(item: Item) {
    if (typeof item === 'string' || typeof item === 'number') return String(item)
    return String(item?.[props.itemLabelKey] ?? '')
}

function itemKey(item: Item) {
    if (typeof item === 'string' || typeof item === 'number') return item
    return item?.[props.itemKeyProp] ?? itemLabel(item)
}

function findExactMatchByValue(value: string | number | null | undefined) {
    if (value === '' || value === null || value === undefined) return null
    return props.items.find(item => String(itemKey(item)) === String(value)) ?? null
}

function findExactMatchByLabel(label: string) {
    return props.items.find(
        item => itemLabel(item).toLowerCase() === label.toLowerCase()
    ) ?? null
}

function getDisplayValueFromModel(value: string | number | null | undefined) {
    if (value === '' || value === null || value === undefined) return ''

    const matched = findExactMatchByValue(value)
    if (matched) return itemLabel(matched)

    return String(value)
}

watch(
    () => props.modelValue,
    (value) => {
        if (suppressRestoreOnce.value) return
        searchValue.value = getDisplayValueFromModel(value)
    },
    { immediate: true }
)

watch(
    () => props.items,
    () => {
        if (!isFocused.value && !suppressRestoreOnce.value) {
            searchValue.value = getDisplayValueFromModel(props.modelValue)
        }
    },
    { deep: true }
)

const filteredItems = computed(() => {
    const q = searchValue.value.trim().toLowerCase()

    if (!q) return props.items

    return props.items.filter(item =>
        itemLabel(item).toLowerCase().includes(q)
    )
})

function setOpen(value: boolean) {
    open.value = value
    emit('update:open', value)

    if (!value) {
        highlightedIndex.value = -1
    } else if (filteredItems.value.length > 0) {
        highlightedIndex.value = 0
    }
}

function handleFocus() {
    if (props.disabled) return

    isFocused.value = true

    if (props.clearOnFocus) {
        searchValue.value = ''
        emit('update:search', '')
    }

    setOpen(true)
}

function handleInput(event: Event) {
    const target = event.target as HTMLInputElement
    const raw = target.value

    suppressRestoreOnce.value = false
    searchValue.value = raw
    emit('update:search', raw)

    if (props.freeInput) {
        emit('update:modelValue', raw)
    } else {
        const exactMatch = findExactMatchByLabel(raw)

        if (exactMatch) {
            const value = itemKey(exactMatch)
            emit('update:modelValue', value)
        }
    }

    setOpen(true)
}

function selectItem(item: Item) {
    const label = itemLabel(item)
    const value = itemKey(item)

    suppressRestoreOnce.value = false
    searchValue.value = label

    emit('update:modelValue', value)
    emit('update:search', label)
    emit('select', { id: value, name: label })

    setOpen(false)
    inputEl.value?.blur()
}

function closeDropdown() {
    if (!open.value) return

    if (props.freeInput) {
        const trimmed = searchValue.value.trim()
        searchValue.value = trimmed
        emit('update:modelValue', trimmed)
        emit('update:search', trimmed)
    } else {
        if (suppressRestoreOnce.value) {
            emit('update:search', '')
        } else {
            searchValue.value = getDisplayValueFromModel(props.modelValue)
            emit('update:search', searchValue.value)
        }
    }

    setOpen(false)
}

function clearSelection() {
    suppressRestoreOnce.value = true
    searchValue.value = ''
    emit('update:modelValue', '')
    emit('update:search', '')
    emit('select', { id: '', name: '' })
    setOpen(false)

    if (!props.clearOnFocus) {
        inputEl.value?.focus()
    }
}

function highlightNext() {
    if (!open.value) {
        setOpen(true)
        return
    }

    if (!filteredItems.value.length) return

    highlightedIndex.value =
        highlightedIndex.value < filteredItems.value.length - 1
            ? highlightedIndex.value + 1
            : 0
}

function highlightPrev() {
    if (!open.value) {
        setOpen(true)
        return
    }

    if (!filteredItems.value.length) return

    highlightedIndex.value =
        highlightedIndex.value > 0
            ? highlightedIndex.value - 1
            : filteredItems.value.length - 1
}

function handleEnter() {
    if (!open.value) return

    if (
        highlightedIndex.value >= 0 &&
        highlightedIndex.value < filteredItems.value.length
    ) {
        selectItem(filteredItems.value[highlightedIndex.value])
        return
    }

    if (props.freeInput) {
        closeDropdown()
    }
}

function handleClickOutside(event: MouseEvent) {
    if (!root.value) return
    if (root.value.contains(event.target as Node)) return

    isFocused.value = false
    closeDropdown()
}

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside)
})
</script>

<style scoped>
.select-search {
    position: relative;
    width: 100%;
}

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

.select-input:focus {
    outline: none;
    border-color: #673ab7;
    box-shadow: 0 0 0 2px rgba(103, 58, 183, 0.15);
}

.select-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 100%;
    background: white;
    border: 1px solid #dadce0;
    border-radius: 6px;
    max-height: 220px;
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.select-option {
    padding: 8px 12px;
    cursor: pointer;
}

.select-option:hover,
.select-option.active {
    background: #f1f3f4;
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