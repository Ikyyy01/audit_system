<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import DashboardLayout from './DashboardLayout.vue';
import WorksheetTable from './WorksheetTable.vue';
import RepeaterField from './RepeaterField.vue';

// Auto-resize textarea, pola sama kayak yang dipakai di Form1100.vue.
// Pasang listener 'input' langsung di elemen (bukan cuma andelin siklus
// update Vue) biar tingginya ngikutin ketikan secara instan & selalu akurat,
// termasuk pas nge-load isi lama yang udah beberapa baris.
const vAutoResize = {
    mounted(el: HTMLTextAreaElement) {
        const resize = () => {
            el.style.height = 'auto';
            el.style.height = el.scrollHeight + 'px';
        };
        resize();
        el.addEventListener('input', resize);
    },
    updated(el: HTMLTextAreaElement) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }
};

const router = useRouter();
const route = useRoute();
const code = computed(() => String(route.params.code || ''));

const user = JSON.parse(localStorage.getItem('user') || '{}');
const userRole = typeof user.role === 'object' && user.role !== null ? user.role.name : (user.role || 'Junior');
const selectedCompany = JSON.parse(localStorage.getItem('selectedCompany') || 'null');

interface FieldOption { value: string; label: string; }
interface RepeaterColumn { key: string; label: string; type?: string; width?: string; options?: { value: string; label: string }[]; }
interface Field {
    id: number;
    field_name: string;
    field_label: string;
    field_type: string;
    is_required: boolean;
    field_order: number;
    options_json: any;
}
interface Section { id: number; section_name: string; section_order: number; fields: Field[]; }
interface WorksheetColumn {
    id: number;
    column_key: string;
    column_label: string;
    data_type: 'text' | 'number' | 'currency' | 'formula';
    column_order: number;
    is_formula: boolean;
    formula_expression: string | null;
}
interface WorksheetRow { row_order: number; row_type: 'data' | 'subtotal' | 'total'; data: Record<string, string>; }
interface FormDef { id: number; code: string; name: string; render_type: 'checklist' | 'worksheet'; sections: Section[]; worksheetColumns: WorksheetColumn[]; }

const loading = ref(true);
const errorMsg = ref('');
const form = ref<FormDef | null>(null);
const engagement = ref<any>(null);
const responseId = ref<number | null>(null);
const status = ref('draft');
const answers = reactive<Record<number, any>>({});
const worksheetRows = ref<WorksheetRow[]>([]);
const reviewComment = ref('');
const reviewHistory = ref<any[]>([]);
const approvalHistory = ref<any[]>([]);
const saving = ref(false);
const exporting = ref(false);
const importing = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

// Label & badge status disamain persis dengan token di style.css
// (status-badge--draft / pending-review / reviewed / revision-required / approved)
const statusLabels: Record<string, string> = {
    draft: 'Draft',
    pending_review: 'Pending Review',
    reviewed: 'Direview — Menunggu Approval Partner',
    revision_required: 'Revisi Diperlukan',
    approved: 'Approved',
};

const badgeClass = computed(() => `status-badge status-badge--${status.value.replace(/_/g, '-')}`);
const canEdit = computed(() => status.value === 'draft' || status.value === 'revision_required');
const canReview = computed(() => status.value === 'pending_review' && userRole === 'Manager');
const canApprove = computed(() => status.value === 'reviewed' && userRole === 'Partner');

function authHeaders() {
    const token = localStorage.getItem('token');
    return { Authorization: `Bearer ${token}`, Accept: 'application/json' };
}

function sortedSections(): Section[] {
    if (!form.value) return [];
    return [...form.value.sections]
        .sort((a, b) => a.section_order - b.section_order)
        .map(s => ({ ...s, fields: [...s.fields].sort((a, b) => a.field_order - b.field_order) }));
}

function optionsFor(field: Field): FieldOption[] {
    return Array.isArray(field.options_json) ? field.options_json : [];
}

function repeaterColumnsFor(field: Field): RepeaterColumn[] {
    if (!field.options_json) return [];
    if (Array.isArray(field.options_json)) return field.options_json;
    if (typeof field.options_json === 'string') {
        try {
            const parsed = JSON.parse(field.options_json);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }
    return [];
}

function inputType(fieldType: string): string {
    if (fieldType === 'number' || fieldType === 'currency' || fieldType === 'percentage') return 'number';
    if (fieldType === 'date') return 'date';
    if (fieldType === 'file') return 'file';
    return 'text';
}

async function loadEverything() {
    loading.value = true;
    errorMsg.value = '';
    // Reset answers dan state
    for (const key in answers) {
        delete answers[key];
    }
    form.value = null;
    engagement.value = null;
    responseId.value = null;
    status.value = 'draft';
    reviewHistory.value = [];
    approvalHistory.value = [];
    worksheetRows.value = [];

    try {
        if (!selectedCompany) {
            errorMsg.value = 'Belum ada perusahaan yang dipilih. Kembali ke dashboard dan pilih klien dulu.';
            return;
        }

        // 1. Ambil definisi form (section + field) berdasarkan code dari URL
        const formsRes = await fetch('/api/v1/audit-forms', { headers: authHeaders() });
        if (formsRes.status === 401) {
            localStorage.clear();
            router.push('/');
            return;
        }
        if (!formsRes.ok) {
            errorMsg.value = `Gagal mengambil daftar form (HTTP ${formsRes.status}). Pastikan backend berjalan dan token valid.`;
            return;
        }
        const forms = await formsRes.json();
        const matched = forms.find((f: any) => f.code === code.value);
        if (!matched) {
            errorMsg.value = `Form dengan kode ${code.value} tidak ditemukan di database (tabel audit_forms).`;
            return;
        }
        form.value = matched;

        // 2. Cari engagement aktif milik klien yang lagi dipilih
        const engRes = await fetch('/api/v1/engagements', { headers: authHeaders() });
        if (engRes.status === 401) {
            localStorage.clear();
            router.push('/');
            return;
        }
        if (!engRes.ok) {
            errorMsg.value = `Gagal mengambil daftar engagement (HTTP ${engRes.status}).`;
            return;
        }
        const engagements = await engRes.json();
        const matchedEng = engagements.find((e: any) => e.client?.id === selectedCompany.id || e.client_id === selectedCompany.id);
        if (!matchedEng) {
            errorMsg.value = `Belum ada engagement untuk ${selectedCompany.name}. Buat engagement dulu di backend.`;
            return;
        }
        engagement.value = matchedEng;

        // 3. Cari response yang udah ada buat form+engagement ini, kalau belum ada, buat draft baru
        const respRes = await fetch('/api/v1/audit-form-responses', { headers: authHeaders() });
        if (respRes.status === 401) {
            localStorage.clear();
            router.push('/');
            return;
        }
        if (!respRes.ok) {
            errorMsg.value = `Gagal mengambil response audit (HTTP ${respRes.status}).`;
            return;
        }
        const responses = await respRes.json();
        let existing = responses.find((r: any) => r.form_id === matched.id && r.engagement_id === matchedEng.id);

        if (!existing) {
            const createRes = await fetch('/api/v1/audit-form-responses', {
                method: 'POST',
                headers: { ...authHeaders(), 'Content-Type': 'application/json' },
                body: JSON.stringify({ form_id: matched.id, engagement_id: matchedEng.id, user_id: user.id }),
            });
            if (!createRes.ok) throw new Error('Gagal membuat response form baru.');
            const created = await createRes.json();
            // GET ulang dengan relasi lengkap (store() tidak menyertakan relasi answers/reviews/approvals)
            const fullRes = await fetch(`/api/v1/audit-form-responses/${created.id}`, { headers: authHeaders() });
            existing = fullRes.ok ? await fullRes.json() : created;
        }

        responseId.value = existing.id;
        status.value = existing.status;
        reviewHistory.value = existing.reviews || [];
        approvalHistory.value = existing.approvals || [];

        for (const ans of existing.answers || []) {
            answers[ans.field_id] = ans.response_value;
        }

        worksheetRows.value = (existing.worksheetRows || existing.worksheet_rows || [])
            .sort((a: any, b: any) => a.row_order - b.row_order)
            .map((r: any) => ({ row_order: r.row_order, row_type: r.row_type, data: r.data || {} }));
    } catch (e: any) {
        console.error(e);
        errorMsg.value = e?.message || 'Terjadi kesalahan saat memuat form.';
    } finally {
        loading.value = false;
    }
}

async function saveAnswers(): Promise<boolean> {
    if (!responseId.value) return false;
    saving.value = true;
    try {
        const payload = Object.entries(answers).map(([fieldId, value]) => ({
            field_id: Number(fieldId),
            response_value: value === null || value === undefined || value === '' ? null : String(value),
        }));

        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/answers`, {
            method: 'POST',
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ answers: payload }),
        });
        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Gagal menyimpan jawaban.');
            return false;
        }
        return true;
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan saat menyimpan.');
        return false;
    } finally {
        saving.value = false;
    }
}

async function saveWorksheetRows(): Promise<boolean> {
    if (!responseId.value) return false;
    saving.value = true;
    try {
        const payload = worksheetRows.value.map((r, i) => ({ row_order: i + 1, row_type: r.row_type, data: r.data }));
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/worksheet-rows`, {
            method: 'POST',
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ rows: payload }),
        });
        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Gagal menyimpan baris worksheet.');
            return false;
        }
        return true;
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan saat menyimpan.');
        return false;
    } finally {
        saving.value = false;
    }
}

async function handleSaveDraft() {
    const ok = form.value?.render_type === 'worksheet' ? await saveWorksheetRows() : await saveAnswers();
    if (ok) alert('Draft tersimpan.');
}

async function handleSubmit() {
    const ok = form.value?.render_type === 'worksheet' ? await saveWorksheetRows() : await saveAnswers();
    if (!ok) return;
    try {
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/submit`, {
            method: 'POST',
            headers: authHeaders(),
        });
        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Gagal submit form.');
            return;
        }
        const data = await res.json();
        status.value = data.status;
        alert('Form berhasil disubmit untuk review.');
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan.');
    }
}

async function handleReview(action: 'approve' | 'request_revision') {
    try {
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/review`, {
            method: 'POST',
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, comments: reviewComment.value || null }),
        });
        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Gagal mereview form.');
            return;
        }
        const data = await res.json();
        status.value = data.status;
        reviewComment.value = '';
        alert(action === 'approve' ? 'Review selesai, diteruskan ke Partner.' : 'Form dikembalikan untuk revisi.');
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan.');
    }
}

async function handleApprove(action: 'approve' | 'reject') {
    try {
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/approve`, {
            method: 'POST',
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, comments: reviewComment.value || null }),
        });
        if (!res.ok) {
            const err = await res.json();
            alert(err.message || 'Gagal approve form.');
            return;
        }
        const data = await res.json();
        status.value = data.status;
        reviewComment.value = '';
        alert(action === 'approve' ? 'Form disetujui final.' : 'Form ditolak, dikembalikan untuk revisi.');
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan.');
    }
}

async function handleExport() {
    if (!responseId.value) {
        alert('ID response form belum tersedia. Pastikan form telah dimuat.');
        return;
    }
    exporting.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/export`, {
            headers: { Authorization: `Bearer ${token}` },
        });
        if (res.ok) {
            const blob = await res.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            const clientClean = (selectedCompany?.name || 'Klien').replace(/[^a-zA-Z0-9_\-]/g, '_');
            const ext = form.value?.render_type === 'worksheet' ? 'xlsx' : 'docx';
            const prefix = form.value?.render_type === 'worksheet' ? 'Worksheet' : 'Audit';
            a.download = `${prefix}_${form.value?.code || 'form'}_${clientClean}.${ext}`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);
        } else {
            const err = await res.json().catch(() => ({}));
            alert((err as any).message || 'Gagal generate file.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan saat mengunduh file.');
    } finally {
        exporting.value = false;
    }
}

function triggerFileInput() {
    fileInput.value?.click();
}

async function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    input.value = ''; // Reset input so same file can be selected again

    if (!responseId.value) {
        alert('Form belum dimuat sempurna.');
        return;
    }

    const formData = new FormData();
    formData.append('file', file);

    importing.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await fetch(`/api/v1/audit-form-responses/${responseId.value}/import-excel`, {
            method: 'POST',
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
            body: formData,
        });

        const data = await res.json();
        if (!res.ok) {
            alert(data.message || 'Gagal mengimpor file Excel.');
            return;
        }

        if (Array.isArray(data.rows)) {
            worksheetRows.value = data.rows.map((r: any) => ({
                row_order: r.row_order,
                row_type: r.row_type,
                data: r.data || {},
            }));
        }
        alert(data.message || 'Berhasil mengimpor baris dari Excel.');
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan saat mengunggah file.');
    } finally {
        importing.value = false;
    }
}

onMounted(loadEverything);

// Re-fetch ketika user klik form lain di sidebar (route params berubah tapi komponen di-reuse)
watch(code, () => {
    loadEverything();
});
</script>

<template>
    <DashboardLayout>
        <div class="dynamic-form" v-if="loading">
            <p class="hint">Memuat form...</p>
        </div>

        <div class="dynamic-form" v-else-if="errorMsg">
            <div class="card empty-state">
                <p>{{ errorMsg }}</p>
            </div>
        </div>

        <div class="dynamic-form" v-else-if="form">
            <header class="form-header card">
                <div>
                    <p class="eyebrow">Form {{ form.code }}</p>
                    <h2>{{ form.name }}</h2>
                    <p class="hint" v-if="engagement">{{ engagement.client?.name }} &middot; {{ engagement.engagement_code }}</p>
                </div>
                <div class="header-right">
                    <span :class="badgeClass">{{ statusLabels[status] || status }}</span>
                    <!-- Worksheet: Import + Export Excel -->
                    <template v-if="form.render_type === 'worksheet'">
                        <button
                            v-if="canEdit"
                            class="btn export-btn import-btn"
                            :disabled="importing"
                            @click="triggerFileInput"
                        >
                            {{ importing ? 'Mengimpor...' : 'Import Excel' }}
                        </button>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            style="display:none"
                            @change="handleFileChange"
                        />
                        <button class="btn export-btn" :disabled="exporting" @click="handleExport">
                            {{ exporting ? 'Mengunduh...' : 'Export Excel' }}
                        </button>
                    </template>
                    <!-- Checklist: Generate Word -->
                    <template v-else>
                        <button class="btn export-btn" :disabled="exporting" @click="handleExport">
                            {{ exporting ? 'Mengunduh...' : 'Generate Word' }}
                        </button>
                    </template>
                </div>
            </header>

            <div v-if="form.render_type === 'checklist' && (!form.sections || form.sections.length === 0)" class="card empty-state">
                <p>Form ini belum punya section/pertanyaan di database. Tambahkan lewat seeder atau admin dulu.</p>
            </div>

            <div v-if="form.render_type === 'worksheet' && (!form.worksheetColumns || form.worksheetColumns.length === 0)" class="card empty-state">
                <p>Form worksheet ini belum punya kolom di database. Tambahkan lewat seeder dulu.</p>
            </div>

            <template v-if="form.render_type === 'worksheet'">
                <div v-if="form.worksheetColumns && form.worksheetColumns.length > 0" class="card worksheet-card">
                    <WorksheetTable
                        :columns="form.worksheetColumns"
                        :rows="worksheetRows"
                        :editable="canEdit"
                        @update:rows="worksheetRows = $event"
                    />
                </div>
            </template>

            <template v-else>
                <div v-for="section in sortedSections()" :key="section.id" class="card section-card">
                    <h4>{{ section.section_name }}</h4>

                    <div v-for="field in section.fields" :key="field.id" class="field-row">
                        <label class="field-label">{{ field.field_label }}<span v-if="field.is_required" class="required-mark"> *</span></label>

                        <!-- Field Repeater (Tabel Dinamis dengan Tambah/Hapus Baris) -->
                        <RepeaterField
                            v-if="field.field_type === 'repeater'"
                            :model-value="answers[field.id]"
                            :columns="repeaterColumnsFor(field)"
                            :editable="canEdit"
                            @update:model-value="answers[field.id] = $event"
                        />

                        <!-- Field Standard Lainnya -->
                        <template v-else-if="canEdit">
                            <select v-if="field.field_type === 'dropdown'" v-model="answers[field.id]">
                                <option value="">-- Pilih --</option>
                                <option v-for="opt in optionsFor(field)" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>

                            <textarea
                                v-else-if="field.field_type === 'textarea'"
                                v-model="answers[field.id]"
                                v-auto-resize
                                placeholder="Isi jawaban..."
                            ></textarea>

                            <label v-else-if="field.field_type === 'checkbox'" class="checkbox-inline">
                                <input type="checkbox" v-model="answers[field.id]" true-value="true" false-value="false" />
                                <span>Ya</span>
                            </label>

                            <input v-else :type="inputType(field.field_type)" v-model="answers[field.id]" />
                        </template>
                        <p v-else class="answer-display">{{ answers[field.id] || '-' }}</p>
                    </div>
                </div>
            </template>

            <!-- Review Feedback Log -->
            <section v-if="reviewHistory.length > 0 || approvalHistory.length > 0" class="card history-card">
                <h4>Riwayat Review &amp; Approval</h4>
                <div class="log-list">
                    <div v-for="log in [...reviewHistory, ...approvalHistory]" :key="log.id" class="log-item">
                        <small>{{ log.reviewed_at || log.approval_date }} oleh {{ log.reviewer?.name || log.approver?.name }}</small>
                        <p><strong>{{ (log.review_status || log.approval_status).toUpperCase() }}</strong>: {{ log.comments || '-' }}</p>
                    </div>
                </div>
            </section>

            <footer class="action-footer card">
                <div class="action-left">
                    <p class="role-hint">Role akun login: <strong>{{ userRole }}</strong></p>
                </div>
                <div class="action-right">
                    <template v-if="canEdit">
                        <button class="btn ghost" :disabled="saving" @click="handleSaveDraft">
                            {{ saving ? 'Menyimpan...' : 'Simpan Draft' }}
                        </button>
                        <button class="btn primary" :disabled="saving" @click="handleSubmit">
                            Submit ke Manager
                        </button>
                    </template>

                    <template v-else-if="canReview">
                        <textarea class="comment-input" v-model="reviewComment" v-auto-resize placeholder="Komentar review (opsional)..."></textarea>
                        <div class="action-right">
                            <button class="btn danger" @click="handleReview('request_revision')">Minta Revisi ke Auditor</button>
                            <button class="btn primary" @click="handleReview('approve')">Review Selesai &amp; Teruskan ke Partner</button>
                        </div>
                    </template>

                    <template v-else-if="canApprove">
                        <textarea class="comment-input" v-model="reviewComment" v-auto-resize placeholder="Komentar approval (opsional)..."></textarea>
                        <div class="action-right">
                            <button class="btn danger" @click="handleApprove('reject')">Tolak / Minta Revisi</button>
                            <button class="btn success" @click="handleApprove('approve')">Approve Final (Partner)</button>
                        </div>
                    </template>

                    <p v-else class="hint">{{ status === 'approved' ? 'Form sudah final (Approved).' : 'Menunggu aksi dari role lain.' }}</p>
                </div>
            </footer>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.dynamic-form { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.card { background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

.form-header { display: flex; justify-content: space-between; align-items: flex-start; border-left: 5px solid var(--orange-600); }
.form-header h2 { margin: 0.25rem 0; font-size: 1.3rem; }
.header-right { display: flex; align-items: center; gap: 0.75rem; }
.export-btn { background: var(--surface); color: var(--ink-900); border: 1px solid var(--surface-border); font-size: 0.82rem; padding: 0.45rem 0.9rem; border-radius: 4px; font-weight: bold; cursor: pointer; }
.export-btn:hover { background: #e2e8f0; }
.import-btn { background: #ebf5fb; color: #1a6a9a; border-color: #aed6f1; }
.import-btn:hover { background: #d6eaf8; }
.eyebrow { margin: 0; font-size: 0.85rem; color: #7f8c8d; letter-spacing: 0.5px; }

.section-card h4 { margin: 0 0 1.25rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--surface-border); font-family: var(--font-body); color: var(--ink-900); }
.worksheet-card { overflow-x: auto; }

.field-row { margin-bottom: 1.25rem; }
.field-row:last-child { margin-bottom: 0; }
.field-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--ink-900); margin-bottom: 0.4rem; white-space: pre-line; line-height: 1.5; }
.required-mark { color: var(--status-overdue); }
.answer-display { margin: 0; font-size: 0.9rem; color: #2f3542; white-space: pre-line; line-height: 1.5; }

.checkbox-inline { display: flex; align-items: center; gap: 0.5rem; }
.checkbox-inline input { width: auto; }

.empty-state { text-align: center; color: #7f8c8d; }

.action-footer { display: flex; justify-content: space-between; align-items: center; position: sticky; bottom: 1rem; z-index: 5; flex-wrap: wrap; gap: 0.75rem; }
.role-hint { margin: 0; font-size: 0.9rem; color: #7f8c8d; }
.action-left { display: flex; align-items: center; }
.action-right { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; }
.comment-input { min-height: 60px; width: 100%; margin-bottom: 0.5rem; }

.btn { padding: 0.6rem 1.2rem; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; font-size: 0.9rem; }
.btn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn.primary { background: var(--orange-600); color: #fff; }
.btn.primary:hover { background: var(--orange-600-hover); }
.btn.success { background: var(--green-700); color: #fff; }
.btn.success:hover { background: var(--green-700-hover); }
.btn.danger { background: var(--status-overdue); color: #fff; }
.btn.danger:hover { background: #953f32; }
.btn.ghost { background: #fff; color: var(--ink-900); border: 1px solid var(--surface-border); }
.btn.ghost:hover { background: var(--surface); }
</style>
