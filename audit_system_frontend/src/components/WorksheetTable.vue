<script setup lang="ts">
import { computed } from 'vue';
import { evalFormula, formatNumber } from '../lib/formula';

interface WorksheetColumn {
    id: number;
    column_key: string;
    column_label: string;
    data_type: 'text' | 'number' | 'currency' | 'formula';
    column_order: number;
    is_formula: boolean;
    formula_expression: string | null;
}

interface WorksheetRow {
    row_order: number;
    row_type: 'data' | 'subtotal' | 'total';
    data: Record<string, string>;
}

const props = defineProps<{
    columns: WorksheetColumn[];
    rows: WorksheetRow[];
    editable: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:rows', rows: WorksheetRow[]): void;
}>();

const sortedColumns = computed(() => [...props.columns].sort((a, b) => a.column_order - b.column_order));
const isNumericType = (type: string) => type === 'number' || type === 'currency' || type === 'formula';

function cellValue(row: WorksheetRow, col: WorksheetColumn): string {
    if (col.is_formula && col.formula_expression) {
        return String(evalFormula(col.formula_expression, row.data));
    }
    return row.data[col.column_key] ?? '';
}

function updateCell(rowIndex: number, key: string, value: string) {
    const next = props.rows.map((r, i) => (i === rowIndex ? { ...r, data: { ...r.data, [key]: value } } : r));
    emit('update:rows', next);
}

function addRow() {
    const emptyData: Record<string, string> = {};
    for (const col of sortedColumns.value) {
        if (!col.is_formula) emptyData[col.column_key] = '';
    }
    emit('update:rows', [...props.rows, { row_order: props.rows.length + 1, row_type: 'data', data: emptyData }]);
}

function removeRow(rowIndex: number) {
    const next = props.rows.filter((_, i) => i !== rowIndex).map((r, i) => ({ ...r, row_order: i + 1 }));
    emit('update:rows', next);
}

function columnTotal(col: WorksheetColumn): number {
    if (!isNumericType(col.data_type)) return 0;
    return props.rows.reduce((sum, row) => sum + (Number(cellValue(row, col)) || 0), 0);
}
</script>

<template>
    <div class="worksheet-wrap">
        <table class="worksheet-table">
            <thead>
                <tr>
                    <th class="row-no-col">No</th>
                    <th v-for="col in sortedColumns" :key="col.id" :class="{ 'num-col': isNumericType(col.data_type) }">
                        {{ col.column_label }}
                        <span v-if="col.is_formula" class="formula-tag" title="Dihitung otomatis">fx</span>
                    </th>
                    <th v-if="editable" class="row-action-col"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, rowIndex) in rows" :key="rowIndex">
                    <td class="row-no-col">{{ rowIndex + 1 }}</td>
                    <td v-for="col in sortedColumns" :key="col.id" :class="{ 'num-col': isNumericType(col.data_type) }">
                        <input
                            v-if="editable && !col.is_formula"
                            :value="cellValue(row, col)"
                            :type="isNumericType(col.data_type) ? 'number' : 'text'"
                            @input="updateCell(rowIndex, col.column_key, ($event.target as HTMLInputElement).value)"
                        />
                        <span v-else-if="col.is_formula" class="formula-cell">{{ formatNumber(Number(cellValue(row, col)) || 0) }}</span>
                        <span v-else class="static-cell">{{ cellValue(row, col) || '-' }}</span>
                    </td>
                    <td v-if="editable" class="row-action-col">
                        <button type="button" class="btn-remove-row" title="Hapus baris" @click="removeRow(rowIndex)">&times;</button>
                    </td>
                </tr>
                <tr v-if="rows.length === 0">
                    <td :colspan="sortedColumns.length + (editable ? 2 : 1)" class="empty-rows">Belum ada baris. Klik &quot;Tambah Baris&quot; untuk mulai isi.</td>
                </tr>
            </tbody>
            <tfoot v-if="rows.length > 0">
                <tr>
                    <td class="row-no-col total-label">Total</td>
                    <td v-for="col in sortedColumns" :key="col.id" :class="{ 'num-col': isNumericType(col.data_type) }">
                        <strong v-if="isNumericType(col.data_type)">{{ formatNumber(columnTotal(col)) }}</strong>
                    </td>
                    <td v-if="editable" class="row-action-col"></td>
                </tr>
            </tfoot>
        </table>

        <button v-if="editable" type="button" class="btn-add-row" @click="addRow">+ Tambah Baris</button>
    </div>
</template>

<style scoped>
.worksheet-wrap { display: flex; flex-direction: column; gap: 0.75rem; }
.worksheet-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.worksheet-table th, .worksheet-table td { border: 1px solid var(--surface-border); padding: 0.5rem 0.6rem; text-align: left; vertical-align: middle; }
.worksheet-table thead th { background: var(--surface); color: #57606f; font-size: 0.78rem; font-weight: 700; white-space: nowrap; }
.formula-tag { display: inline-block; margin-left: 0.3rem; font-size: 0.65rem; font-weight: 800; color: var(--orange-600); background: color-mix(in srgb, var(--orange-600) 14%, white); padding: 0.05rem 0.3rem; border-radius: 3px; vertical-align: middle; }
.num-col { text-align: right; }
.row-no-col { width: 40px; text-align: center; color: #94a3b8; font-size: 0.78rem; }
.row-action-col { width: 40px; text-align: center; }
.worksheet-table input { width: 100%; padding: 0.35rem 0.5rem; border: 1px solid #ced6e0; border-radius: 4px; font-size: 0.85rem; box-sizing: border-box; text-align: inherit; }
.worksheet-table input:focus { outline: none; border-color: var(--orange-600); }
.formula-cell { font-weight: 600; color: var(--ink-900); }
.static-cell { color: #2f3542; }
.btn-remove-row { border: 0; background: transparent; color: var(--status-overdue); font-size: 1.1rem; line-height: 1; cursor: pointer; padding: 0.2rem 0.4rem; border-radius: 4px; }
.btn-remove-row:hover { background: color-mix(in srgb, var(--status-overdue) 12%, white); }
.empty-rows { text-align: center; color: #94a3b8; padding: 1.5rem; }
tfoot td { background: var(--surface); font-size: 0.85rem; }
.total-label { text-align: center; font-weight: 700; color: var(--ink-900); }
.btn-add-row { align-self: flex-start; background: #fff; color: var(--orange-600); border: 1px dashed var(--orange-600); padding: 0.5rem 1rem; border-radius: 6px; font-weight: 700; cursor: pointer; font-size: 0.85rem; }
.btn-add-row:hover { background: color-mix(in srgb, var(--orange-600) 8%, white); }
</style>
