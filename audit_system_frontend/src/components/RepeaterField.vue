<script setup lang="ts">
import { ref, watch, computed } from 'vue';

interface ColumnDef {
    key: string;
    label: string;
    type?: string;
    width?: string;
    options?: { value: string; label: string }[];
    // Kolom turunan (read-only, dihitung otomatis, TIDAK diisi manual):
    formula?: string;      // ekspresi aritmatika antar-kolom di baris yang sama, mis. "jumlah_lembar * __multiplier__"
    percent_of?: string;   // key kolom lain — nilai cell ini = (kolom_itu / SUM(kolom_itu semua baris)) x 100%
    total?: boolean;       // tampilkan SUM kolom ini di baris Total footer
    multiplier?: {         // setting pengali yang bisa diatur auditor di atas tabel
        source: string;    // key kolom sumber (mis. "jumlah_lembar")
        label: string;     // label input, mis. "Nilai per Lembar (IDR)"
        default: number;   // default value, mis. 50
    };
}

const props = defineProps<{
    modelValue: any;
    columns: ColumnDef[];
    editable?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Detect multiplier config from columns
const multiplierConfig = computed(() => {
    for (const col of props.columns) {
        if (col.multiplier) return col.multiplier;
    }
    return null;
});
const multiplierValue = ref(multiplierConfig.value?.default ?? 1);

// Parse initial value (JSON string atau array, fallback parse string baris-per-baris)
function parseValue(val: any): Record<string, any>[] {
    if (!val) return [];
    if (Array.isArray(val)) return val;
    if (typeof val === 'string') {
        try {
            const parsed = JSON.parse(val);
            if (Array.isArray(parsed)) return parsed;
        } catch {
            // Jika data lama adalah string teks bernomor (mis. "1. Maurice Ganda Nainggolan (Partner KAP)\n2. Kenny..."),
            // urai otomatis per baris menjadi kolom nama & jabatan jika cocok dengan pola
            const lines = val.split('\n').map(l => l.trim()).filter(l => l !== '');
            const parsedRows: Record<string, any>[] = [];

            const firstKey = props.columns[0]?.key || 'nama';
            const secondKey = props.columns[1]?.key || 'jabatan';

            for (const line of lines) {
                // Hapus nomor urut di awal ("1. ", "2. ", dst)
                const cleanLine = line.replace(/^\d+[\.\)]\s*/, '').trim();
                // Cari teks dalam kurung sebagai jabatan: "Nama Person (Jabatan/Instansi)"
                const match = cleanLine.match(/^(.*?)\s*\((.*?)\)$/);
                if (match && props.columns.length >= 2) {
                    parsedRows.push({
                        [firstKey]: match[1].trim(),
                        [secondKey]: match[2].trim(),
                    });
                } else {
                    parsedRows.push({ [firstKey]: cleanLine });
                }
            }

            if (parsedRows.length > 0) {
                return parsedRows;
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
        // Kolom formula/percent_of gak usah diisi manual, biarin kosong di data
        // biar gak nyimpen nilai basi — dihitung ulang tiap render dari kolom sumbernya.
        if (col.formula || col.percent_of) continue;
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

// Evaluator aritmatika sederhana & aman (bukan eval bebas) — cuma dipakai buat
// kolom formula yang expression-nya didefinisikan di database (options_json),
// bukan input user, jadi aman dari injeksi.
function evalFormula(expr: string, row: Record<string, any>): number {
    const tokens = expr.match(/[a-zA-Z_][a-zA-Z0-9_]*|[-+*/().]|\d+(\.\d+)?/g) || [];
    const resolved = tokens.map((t) => {
        if (t === '__multiplier__') {
            return String(Number(multiplierValue.value) || 0);
        }
        if (/^[a-zA-Z_]/.test(t)) {
            const v = Number(row[t]);
            return Number.isFinite(v) ? String(v) : '0';
        }
        return t;
    });
    const joined = resolved.join(' ');
    if (!/^[\d\s+\-*/().]*$/.test(joined) || joined.trim() === '') return 0;
    try {
        // eslint-disable-next-line no-new-func
        const result = Function(`"use strict"; return (${joined});`)();
        return typeof result === 'number' && Number.isFinite(result) ? result : 0;
    } catch {
        return 0;
    }
}

function columnTotal(key: string): number {
    // Cek apakah kolom ini punya formula — kalau iya, total = SUM(formula(row)) tiap baris
    const col = props.columns.find(c => c.key === key);
    if (col?.formula) {
        return localRows.value.reduce((sum, row) => sum + evalFormula(col.formula!, row), 0);
    }
    return localRows.value.reduce((sum, row) => sum + (Number(row[key]) || 0), 0);
}

function cellDisplayValue(row: Record<string, any>, col: ColumnDef): string {
    if (col.formula) {
        return formatNumber(evalFormula(col.formula, row));
    }
    if (col.percent_of) {
        const total = columnTotal(col.percent_of);
        const value = Number(row[col.percent_of]) || 0;
        if (total === 0) return '0%';
        return (value / total * 100).toFixed(2) + '%';
    }
    return row[col.key] || '-';
}

function formatNumber(value: number): string {
    return value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}

const hasTotalRow = computed(() => props.columns.some(c => c.total) && localRows.value.length > 0);
</script>

<template>
    <div class="repeater-container">
        <!-- Multiplier Setting — tampil hanya jika ada kolom dengan config multiplier -->
        <div v-if="multiplierConfig && isEditable" class="multiplier-bar">
            <span class="multiplier-label">{{ multiplierConfig.label }}</span>
            <span class="multiplier-hint">×</span>
            <input
                type="number"
                class="multiplier-input"
                v-model.number="multiplierValue"
                min="1"
                step="1"
                placeholder="mis. 50"
            />
            <span class="multiplier-note">
                → Kolom <strong>{{ columns.find(c => c.multiplier)?.label }}</strong> = {{ multiplierConfig.source }} × nilai ini
            </span>
        </div>
        <div v-else-if="multiplierConfig && !isEditable" class="multiplier-bar multiplier-bar--readonly">
            <span class="multiplier-label">{{ multiplierConfig.label }}</span>
            <span class="multiplier-value-display">Rp {{ Number(multiplierValue).toLocaleString('id-ID') }} / lembar</span>
        </div>
        <div class="repeater-table-wrapper">
            <table class="repeater-table">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :style="{ width: col.width || 'auto' }"
                            :class="(col.formula || col.percent_of) ? 'col-formula-header' : 'col-editable-header'"
                        >
                            {{ col.label }}
                            <span v-if="col.formula || col.percent_of" class="formula-tag" title="Dihitung otomatis dari kolom lain">fx</span>
                            <span v-else-if="isEditable" class="editable-tag" title="Dapat diedit">✎</span>
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
                            <template v-if="col.formula || col.percent_of">
                                <span class="cell-formula">{{ cellDisplayValue(row, col) }}</span>
                            </template>
                            <template v-else-if="isEditable">
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
                <tfoot v-if="hasTotalRow">
                    <tr>
                        <td class="col-num total-label">&Sigma;</td>
                        <td v-for="col in columns" :key="col.key">
                            <strong v-if="col.total">{{ formatNumber(columnTotal(col.key)) }}</strong>
                        </td>
                        <td v-if="isEditable" class="col-action"></td>
                    </tr>
                </tfoot>
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

.multiplier-bar {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.5rem 0.85rem;
    background: #fffbeb;
    border-bottom: 1px solid #fde68a;
    flex-wrap: wrap;
}

.multiplier-bar--readonly {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.multiplier-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #92400e;
    white-space: nowrap;
}

.multiplier-hint {
    font-size: 1rem;
    font-weight: 700;
    color: #d97706;
}

.multiplier-input {
    width: 130px;
    padding: 0.3rem 0.5rem;
    font-size: 0.88rem;
    font-weight: 600;
    border: 1px solid #fbbf24;
    border-radius: 4px;
    background: #fff;
    color: #92400e;
    outline: none;
    text-align: right;
}

.multiplier-input:focus {
    border-color: #d97706;
    box-shadow: 0 0 0 2px #fde68a;
}

.multiplier-note {
    font-size: 0.75rem;
    color: #78716c;
}

.multiplier-value-display {
    font-size: 0.82rem;
    font-weight: 600;
    color: #475569;
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

.formula-tag {
    display: inline-block;
    margin-left: 0.3rem;
    font-size: 0.62rem;
    font-weight: 800;
    color: var(--orange-600, #ea580c);
    background: #fff7ed;
    padding: 0.05rem 0.3rem;
    border-radius: 3px;
    vertical-align: middle;
    text-transform: none;
}

.editable-tag {
    display: inline-block;
    margin-left: 0.3rem;
    font-size: 0.65rem;
    color: #64748b;
    vertical-align: middle;
}

.cell-formula {
    display: block;
    padding: 0.35rem 0.5rem;
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--ink-900, #1e293b);
    text-align: right;
}

.repeater-table tfoot td {
    background: #f8fafc;
    border-top: 2px solid var(--surface-border, #e2e8f0);
    padding: 0.5rem 0.75rem;
    text-align: right;
    font-size: 0.88rem;
}

.total-label {
    text-align: center !important;
    font-weight: 700;
    color: var(--ink-900, #1e293b);
}

.empty-cell {
    text-align: center;
    padding: 1.25rem !important;
    color: #94a3b8;
    font-size: 0.85rem;
}

.btn-remove-row {
    background: transparent !important;
    border: 1px solid transparent;
    color: #ef4444 !important;
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
    padding: 0 !important;
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
    background: #fff !important;
    border: 1px dashed var(--orange-600, #ea580c);
    color: var(--orange-600, #ea580c) !important;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem !important;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
}

.btn-add-row:hover {
    background: #fff7ed;
}
</style>
