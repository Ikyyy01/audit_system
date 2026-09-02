<script setup lang="ts">
import { ref, watch, computed } from 'vue';

interface ColumnDef {
    key: string;
    label: string;
    type?: string;
    width?: string;
    options?: { value: string; label: string }[];
}

const props = defineProps<{
    modelValue: any;
    columns: ColumnDef[];
    editable?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Parse initial value (JSON string atau array, fallback parse string baris-per-baris)
function parseValue(val: any): Record<string, any>[] {
    if (!val) return [];
    if (Array.isArray(val)) return val;
    if (typeof val === 'string') {
        try {
            const parsed = JSON.parse(val);
            if (Array.isArray(parsed)) return parsed;
        } catch {
            // Jika data lama adalah string teks biasa (mis. "1. PT Indo: 69%..."),
            // simpan di baris pertama kolom pertama sebagai fallback agar tidak hilang
            if (val.trim() !== '') {
                const firstKey = props.columns[0]?.key || 'value';
                return [{ [firstKey]: val }];
            }
        }
    }
    return [];
}

const localRows = ref<Record<string, any>[]>(parseValue(props.modelValue));

watch(() => props.modelValue, (newVal) => {
    const parsed = parseValue(newVal);
    // Hindari infinite loop jika value sama
    if (JSON.stringify(parsed) !== JSON.stringify(localRows.value)) {
        localRows.value = parsed;
    }
});

function emitChange() {
    emit('update:modelValue', JSON.stringify(localRows.value));
}

function addRow() {
    const newRow: Record<string, any> = {};
    for (const col of props.columns) {
        newRow[col.key] = '';
    }
    // Jika ada kolom 'no', isi nomor urut otomatis
    if ('no' in newRow || props.columns.some(c => c.key === 'no')) {
        newRow['no'] = localRows.value.length + 1;
    }
    localRows.value.push(newRow);
    emitChange();
}

function removeRow(index: number) {
    localRows.value.splice(index, 1);
    // Perbarui no urut jika ada kolom 'no'
    if (props.columns.some(c => c.key === 'no')) {
        localRows.value.forEach((r, idx) => {
            r['no'] = idx + 1;
        });
    }
    emitChange();
}

function handleInput() {
    emitChange();
}

const isEditable = computed(() => props.editable !== false);
</script>

<template>
    <div class="repeater-container">
        <div class="repeater-table-wrapper">
            <table class="repeater-table">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :style="{ width: col.width || 'auto' }"
                        >
                            {{ col.label }}
                        </th>
                        <th v-if="isEditable" class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="localRows.length === 0">
                        <td :colspan="columns.length + (isEditable ? 2 : 1)" class="empty-cell">
                            Belum ada data. <span v-if="isEditable">Klik <strong>+ Tambah Baris</strong> di bawah untuk menambahkan.</span>
                        </td>
                    </tr>
                    <tr v-for="(row, rIdx) in localRows" :key="rIdx">
                        <td class="col-num">{{ rIdx + 1 }}</td>
                        <td v-for="col in columns" :key="col.key">
                            <template v-if="isEditable">
                                <select
                                    v-if="col.options && col.options.length > 0"
                                    v-model="row[col.key]"
                                    @change="handleInput"
                                    class="cell-input"
                                >
                                    <option value="">-- Pilih --</option>
                                    <option v-for="opt in col.options" :key="opt.value" :value="opt.value">
                                        {{ opt.label }}
                                    </option>
                                </select>
                                <input
                                    v-else
                                    :type="col.type === 'number' ? 'number' : col.type === 'date' ? 'date' : 'text'"
                                    step="any"
                                    v-model="row[col.key]"
                                    @input="handleInput"
                                    class="cell-input"
                                    :placeholder="col.label"
                                />
                            </template>
                            <span v-else class="cell-text">
                                {{ row[col.key] || '-' }}
                            </span>
                        </td>
                        <td v-if="isEditable" class="col-action">
                            <button
                                type="button"
                                class="btn-remove-row"
                                @click="removeRow(rIdx)"
                                title="Hapus Baris"
                            >
                                &times;
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="isEditable" class="repeater-footer">
            <button type="button" class="btn-add-row" @click="addRow">
                + Tambah Baris
            </button>
        </div>
    </div>
</template>

<style scoped>
.repeater-container {
    border: 1px solid var(--surface-border, #e2e8f0);
    border-radius: 6px;
    background: #fff;
    overflow: hidden;
    margin-top: 0.35rem;
}

.repeater-table-wrapper {
    overflow-x: auto;
}

.repeater-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
}

.repeater-table thead th {
    background: #f8fafc;
    color: var(--ink-900, #1e293b);
    font-weight: 600;
    text-align: left;
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid var(--surface-border, #e2e8f0);
    border-right: 1px solid var(--surface-border, #e2e8f0);
    white-space: nowrap;
}

.repeater-table thead th:last-child {
    border-right: none;
}

.repeater-table tbody td {
    padding: 0.4rem 0.6rem;
    border-bottom: 1px solid var(--surface-border, #f1f5f9);
    border-right: 1px solid var(--surface-border, #f1f5f9);
    vertical-align: middle;
}

.repeater-table tbody td:last-child {
    border-right: none;
}

.repeater-table tbody tr:hover {
    background: #fafbfc;
}

.col-num {
    width: 40px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.8rem;
}

.col-action {
    width: 50px;
    text-align: center;
}

.cell-input {
    width: 100%;
    padding: 0.35rem 0.5rem;
    font-size: 0.88rem;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    background: #fff;
    box-sizing: border-box;
    outline: none;
    transition: border-color 0.15s;
}

.cell-input:focus {
    border-color: var(--orange-600, #ea580c);
    box-shadow: 0 0 0 1px var(--orange-600, #ea580c);
}

.cell-text {
    font-size: 0.88rem;
    color: #334155;
    white-space: pre-line;
}

.empty-cell {
    text-align: center;
    padding: 1.25rem !important;
    color: #94a3b8;
    font-size: 0.85rem;
}

.btn-remove-row {
    background: transparent;
    border: 1px solid transparent;
    color: #ef4444;
    font-size: 1.25rem;
    line-height: 1;
    width: 26px;
    height: 26px;
    border-radius: 4px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.btn-remove-row:hover {
    background: #fee2e2;
    border-color: #fca5a5;
}

.repeater-footer {
    padding: 0.5rem 0.75rem;
    background: #f8fafc;
    border-top: 1px solid var(--surface-border, #e2e8f0);
}

.btn-add-row {
    background: #fff;
    border: 1px dashed var(--orange-600, #ea580c);
    color: var(--orange-600, #ea580c);
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-add-row:hover {
    background: #fff7ed;
}
</style>
