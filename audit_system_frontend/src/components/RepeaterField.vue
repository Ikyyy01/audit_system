<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { evalFormula as evalFormulaShared, formatNumber, parseNumber } from '../lib/formula';

interface ColumnDef {
    key: string;
    label: string;
    type?: string;
    width?: string;
    options?: { value: string; label: string }[];
    readonly?: boolean;    // kolom hanya bisa dilihat (read-only), nilainya dipasok dari luar
    // Kolom turunan (read-only, dihitung otomatis, TIDAK diisi manual):
    formula?: string;      // ekspresi aritmatika antar-kolom di baris yang sama, mis. "jumlah_lembar * __multiplier__"
    percent_of?: string;   // key kolom lain — nilai cell ini = (kolom_itu / SUM(kolom_itu semua baris)) x 100%
    total?: boolean;       // tampilkan SUM kolom ini di baris Total footer
    total_label?: string; // custom label baris total (misal "Total Assets")
    prefix?: string;       // prefix tampilan (misal "Rp")
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
    responseId?: number | null;
    fieldId?: number;
    disableAdd?: boolean;
    disableRemove?: boolean;
    /** 'table' = layout tabel horizontal (default), 'stacked' = card vertikal per baris */
    mode?: 'table' | 'stacked';
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

// ── Upload file per cell (col.type === 'file') ──
const cellFileInput = ref<HTMLInputElement | null>(null);
const uploadingRowIndex = ref<number | null>(null);
const uploadingColKey = ref<string | null>(null);
const activeUploadRowIndex = ref<number | null>(null);
const activeUploadColKey = ref<string | null>(null);

function triggerCellFileInput(rowIndex: number, colKey: string) {
    if (!isEditable.value) return;
    activeUploadRowIndex.value = rowIndex;
    activeUploadColKey.value = colKey;
    cellFileInput.value?.click();
}

function cellFileUrl(path: any): string {
    if (!path) return '';
    return `/storage/${path}`;
}

function cellFileName(path: any): string {
    if (!path) return '';
    const parts = String(path).split('/');
    return parts[parts.length - 1];
}

async function handleCellFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const rIdx = activeUploadRowIndex.value;
    const colKey = activeUploadColKey.value;
    activeUploadRowIndex.value = null;
    activeUploadColKey.value = null;

    if (!input.files || input.files.length === 0 || rIdx === null || !colKey || !props.responseId || !props.fieldId) {
        return;
    }

    const file = input.files[0];
    input.value = '';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('field_id', String(props.fieldId));
    formData.append('row_index', String(rIdx));

    uploadingRowIndex.value = rIdx;
    uploadingColKey.value = colKey;

    try {
        const token = localStorage.getItem('token');
        const res = await fetch(`/api/v1/audit-form-responses/${props.responseId}/repeater-cell-upload`, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
            body: formData,
        });
        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Gagal mengunggah file.');
            return;
        }
        if (localRows.value[rIdx]) {
            localRows.value[rIdx][colKey] = data.path;
            emitChange();
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan saat mengunggah file.');
    } finally {
        uploadingRowIndex.value = null;
        uploadingColKey.value = null;
    }
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
const isActionVisible = computed(() => isEditable.value && !props.disableRemove);
const isAddVisible = computed(() => isEditable.value && !props.disableAdd);
const isStacked = computed(() => props.mode === 'stacked');

// Evaluator formula dipindah ke src/lib/formula.ts (dipakai bareng WorksheetTable.vue)
// biar gak ada 2 implementasi yang bisa divergen. Wrapper tipis di sini cuma
// buat nyuntik multiplierValue lokal komponen ini.
function evalFormula(expr: string, row: Record<string, any>): number {
    return evalFormulaShared(expr, row, Number(multiplierValue.value) || 0);
}

function columnTotal(key: string): number {
    // Cek apakah kolom ini punya formula — kalau iya, total = SUM(formula(row)) tiap baris
    const col = props.columns.find(c => c.key === key);
    if (col?.formula) {
        return localRows.value.reduce((sum, row) => sum + evalFormula(col.formula!, row), 0);
    }
    return localRows.value.reduce((sum, row) => {
        // Jangan jumlahkan jika baris ini berlabel 'Total' untuk mencegah double counting jika data lama masih punya baris total
        const firstVal = String(Object.values(row)[0] || '').toLowerCase().trim();
        if (firstVal.startsWith('total')) return sum;
        return sum + parseNumber(row[key]);
    }, 0);
}

function cellDisplayValue(row: Record<string, any>, col: ColumnDef): string {
    if (col.formula) {
        return formatNumber(evalFormula(col.formula, row));
    }
    if (col.percent_of) {
        const total = columnTotal(col.percent_of);
        const value = parseNumber(row[col.percent_of]);
        if (total === 0) return '0%';
        return (value / total * 100).toFixed(2) + '%';
    }
    const val = row[col.key];
    if (val === undefined || val === null || val === '') return '-';
    // Jika kolom total dan isian berupa angka murni tanpa format, tampilkan format rapi
    if (col.total && typeof val === 'number') {
        return formatNumber(val);
    }
    return String(val);
}

const totalRowLabel = computed(() => {
    for (const col of props.columns) {
        if (col.total_label) return col.total_label;
    }
    return 'Total Assets';
});

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
        <div class="repeater-table-wrapper" v-if="!isStacked">
            <table class="repeater-table">
                <thead>
                    <tr>
                        <th class="col-num">#</th>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            :style="{ width: col.width || 'auto' }"
                            :class="(col.formula || col.percent_of || col.readonly) ? 'col-formula-header' : 'col-editable-header'"
                        >
                            {{ col.label }}
                            <span v-if="col.formula || col.percent_of" class="formula-tag" title="Dihitung otomatis dari kolom lain">fx</span>
                            <span v-else-if="col.readonly" class="formula-tag" title="Otomatis dari daftar nama">auto</span>
                            <span v-else-if="isEditable" class="editable-tag" title="Dapat diedit">✎</span>
                        </th>
                        <th v-if="isActionVisible" class="col-action">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="localRows.length === 0">
                        <td :colspan="columns.length + (isActionVisible ? 2 : 1)" class="empty-cell">
                            Belum ada data. <span v-if="isAddVisible">Klik <strong>+ Tambah Baris</strong> di bawah untuk menambahkan.</span>
                        </td>
                    </tr>
                    <tr v-for="(row, rIdx) in localRows" :key="rIdx">
                        <td class="col-num">{{ rIdx + 1 }}</td>
                        <td v-for="col in columns" :key="col.key">
                            <template v-if="col.formula || col.percent_of || col.readonly">
                                <span class="cell-formula" :class="{ 'cell-text--readonly': col.readonly }">{{ cellDisplayValue(row, col) }}</span>
                            </template>
                            <template v-else-if="col.type === 'file'">
                                <div class="cell-file-box">
                                    <button
                                        v-if="isEditable"
                                        type="button"
                                        class="btn-cell-upload"
                                        :disabled="uploadingRowIndex === rIdx && uploadingColKey === col.key"
                                        @click="triggerCellFileInput(rIdx, col.key)"
                                    >
                                        {{ (uploadingRowIndex === rIdx && uploadingColKey === col.key) ? 'Uploading...' : (row[col.key] ? 'Ganti' : 'Upload') }}
                                    </button>
                                    <a
                                        v-if="row[col.key]"
                                        :href="cellFileUrl(row[col.key])"
                                        target="_blank"
                                        class="cell-file-link"
                                        title="Lihat file / paraf"
                                    >
                                        📎 {{ cellFileName(row[col.key]) }}
                                    </a>
                                    <span v-else-if="!isEditable" class="cell-text">-</span>
                                </div>
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
                                <textarea
                                    v-else-if="col.type === 'textarea'"
                                    v-model="row[col.key]"
                                    @input="handleInput"
                                    class="cell-input cell-textarea"
                                    rows="3"
                                    :placeholder="col.label"
                                ></textarea>
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
                            <span v-else class="cell-text" :class="{ 'cell-text--multiline': col.type === 'textarea' }">
                                {{ cellDisplayValue(row, col) }}
                            </span>
                        </td>
                        <td v-if="isActionVisible" class="col-action">
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
                    <tr class="repeater-total-row">
                        <td class="col-num total-label">&Sigma;</td>
                        <td v-for="(col, cIdx) in columns" :key="col.key">
                            <template v-if="col.total">
                                <strong><span class="total-currency">Rp </span>{{ formatNumber(columnTotal(col.key)) }}</strong>
                            </template>
                            <template v-else-if="cIdx === 0">
                                <strong class="total-row-label">{{ totalRowLabel }}</strong>
                            </template>
                        </td>
                        <td v-if="isActionVisible" class="col-action"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Stacked Card Layout (Mode Kebawah / Per Baris Bertumpuk) -->
        <div class="repeater-stacked-wrapper" v-else>
            <div v-if="localRows.length === 0" class="empty-stacked">
                Belum ada data. <span v-if="isAddVisible">Klik <strong>+ Tambah Item</strong> di bawah untuk menambahkan.</span>
            </div>

            <div
                v-for="(row, rIdx) in localRows"
                :key="rIdx"
                class="stacked-item-card"
            >
                <div class="stacked-item-header">
                    <span class="stacked-item-badge">#{{ rIdx + 1 }}</span>
                    <button
                        v-if="isActionVisible"
                        type="button"
                        class="btn-remove-row"
                        @click="removeRow(rIdx)"
                        title="Hapus Item"
                    >
                        &times;
                    </button>
                </div>

                <div class="stacked-item-grid">
                    <div
                        v-for="col in columns"
                        :key="col.key"
                        class="stacked-field-group"
                        :class="{
                            'stacked-field-group--full': col.type === 'textarea' || (col.width && parseInt(col.width) >= 20),
                            'stacked-field-group--compact': col.width && parseInt(col.width) < 10
                        }"
                    >
                        <label class="stacked-field-label">
                            {{ col.label }}
                            <span v-if="col.formula || col.percent_of" class="formula-tag" title="Dihitung otomatis">fx</span>
                            <span v-else-if="col.readonly" class="formula-tag" title="Otomatis">auto</span>
                        </label>

                        <div class="stacked-field-control">
                            <template v-if="col.formula || col.percent_of || col.readonly">
                                <span class="cell-formula cell-formula--stacked" :class="{ 'cell-text--readonly': col.readonly }">
                                    {{ cellDisplayValue(row, col) }}
                                </span>
                            </template>
                            <template v-else-if="col.type === 'file'">
                                <div class="cell-file-box">
                                    <button
                                        v-if="isEditable"
                                        type="button"
                                        class="btn-cell-upload"
                                        :disabled="uploadingRowIndex === rIdx && uploadingColKey === col.key"
                                        @click="triggerCellFileInput(rIdx, col.key)"
                                    >
                                        {{ (uploadingRowIndex === rIdx && uploadingColKey === col.key) ? 'Uploading...' : (row[col.key] ? 'Ganti' : 'Upload') }}
                                    </button>
                                    <a
                                        v-if="row[col.key]"
                                        :href="cellFileUrl(row[col.key])"
                                        target="_blank"
                                        class="cell-file-link"
                                        title="Lihat file / paraf"
                                    >
                                        📎 {{ cellFileName(row[col.key]) }}
                                    </a>
                                    <span v-else-if="!isEditable" class="cell-text">-</span>
                                </div>
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
                                <textarea
                                    v-else-if="col.type === 'textarea' || (col.width && parseInt(col.width) >= 15)"
                                    v-model="row[col.key]"
                                    @input="handleInput"
                                    class="cell-input cell-textarea"
                                    rows="2"
                                    :placeholder="'Isi ' + col.label.toLowerCase() + '...'"
                                ></textarea>
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
                            <span v-else class="cell-text" :class="{ 'cell-text--multiline': col.type === 'textarea' }">
                                {{ cellDisplayValue(row, col) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="isAddVisible" class="repeater-footer">
            <button type="button" class="btn-add-row" @click="addRow">
                + Tambah Baris
            </button>
        </div>

        <input
            ref="cellFileInput"
            type="file"
            accept=".pdf,.png,.jpg,.jpeg"
            style="display: none"
            @change="handleCellFileChange"
        />
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
    font-weight: 600;
    color: #1e293b;
    font-variant-numeric: tabular-nums;
    text-align: right;
}

.cell-file-box {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.btn-cell-upload {
    padding: 0.2rem 0.5rem;
    font-size: 0.75rem;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    color: #334155;
}

.btn-cell-upload:hover:not(:disabled) {
    background: #e2e8f0;
}

.btn-cell-upload:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.cell-file-link {
    font-size: 0.78rem;
    color: #0284c7;
    text-decoration: none;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: inline-block;
}

.cell-file-link:hover {
    text-decoration: underline;
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

.cell-text--readonly {
    color: var(--ink-900, #0f172a);
    font-weight: 600;
    text-align: left;
    display: inline-block;
}

.cell-textarea {
    resize: vertical;
    min-height: 68px;
    font-family: inherit;
    line-height: 1.45;
    padding: 6px 8px;
}

.cell-text--multiline {
    white-space: pre-wrap;
    word-break: break-word;
    display: block;
    line-height: 1.45;
    text-align: left;
}

.repeater-total-row {
    background-color: #f1f5f9;
    font-weight: 600;
}

.repeater-total-row td {
    padding: 8px 10px !important;
    border-top: 2px solid #cbd5e1;
    border-bottom: 2px solid #cbd5e1;
}

.total-row-label {
    color: var(--ink-900, #0f172a);
    font-size: 0.88rem;
    letter-spacing: 0.01em;
}

.total-currency {
    color: #64748b;
    font-weight: 500;
    margin-right: 2px;
}

/* ── Stacked Mode (Card per Baris Kebawah) ── */
.repeater-stacked-wrapper {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    padding: 0.85rem;
    background: #f8fafc;
}

.empty-stacked {
    text-align: center;
    padding: 1.5rem;
    color: #94a3b8;
    font-size: 0.88rem;
    background: #fff;
    border-radius: 6px;
    border: 1px dashed #cbd5e1;
}

.stacked-item-card {
    background: #fff;
    border: 1px solid var(--surface-border, #e2e8f0);
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
}

.stacked-item-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
}

.stacked-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.85rem;
    padding-bottom: 0.45rem;
    border-bottom: 1px solid #f1f5f9;
}

.stacked-item-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.15rem 0.55rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--orange-600, #ea580c);
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 4px;
}

.stacked-item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem 1rem;
}

.stacked-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.stacked-field-group--full {
    grid-column: 1 / -1;
}

.stacked-field-group--compact {
    max-width: 160px;
}

.stacked-field-label {
    font-size: 0.78rem;
    font-weight: 600;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.stacked-field-control {
    width: 100%;
}

.cell-formula--stacked {
    display: inline-block;
    padding: 0.35rem 0.6rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 4px;
    font-size: 0.85rem;
}
</style>
