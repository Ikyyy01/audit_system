<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DashboardLayout from './DashboardLayout.vue';

interface AuditResult {
    id: number;
    client: string;
    client_id: number | null;
    engagement: string;
    form_code: string;
    form_name: string;
    status: 'draft' | 'pending_review' | 'reviewed' | 'revision_required' | 'approved';
    updated_at: string | null;
}

interface FolderNode {
    title: string;
    code?: string;
    children?: FolderNode[];
}

const router = useRouter();
const results = ref<AuditResult[]>([]);
const loading = ref(false);
const selectedStatus = ref('all');
const searchQuery = ref('');
const selectedResult = ref<AuditResult | null>(null);
const openFolders = ref<Record<string, boolean>>({ 'PT Indo American Seafoods Tbk': true });

const folderTree: FolderNode[] = [
    {
        title: 'PT Indo American Seafoods Tbk',
        children: [
            {
                title: '1000 Risk Assessment',
                children: [
                    { title: '1100 Memo Penerimaan', code: '1100' },
                    { title: '1110 Survey Klien', code: '1110' },
                    { title: '1130 Evaluasi Independensi', code: '1130' }
                ]
            },
            {
                title: '2000 Risk Response',
                children: [
                    { title: '2100 Strategi Audit', code: '2100' },
                    { title: '2400 Pemeriksaan IT', code: '2400' }
                ]
            },
            {
                title: '3000 Audit Evidence',
                children: [{ title: '3100 Balance Sheet', code: '3100' }]
            },
            {
                title: '4000 Representation and Consultation',
                children: [
                    { title: '4100 Konsultasi', code: '4100' },
                    { title: '4300 Konsultasi Report', code: '4300' },
                    { title: '4400 Konsultasi Ahli', code: '4400' }
                ]
            },
            {
                title: '5000 Reporting',
                children: [
                    { title: '5100 WBS', code: '5100' },
                    { title: '5200 CaLK', code: '5200' },
                    { title: '5903 Finalisasi', code: '5903' }
                ]
            },
            {
                title: 'PMPJ',
                children: [
                    { title: '1 Surat Konfirmasi', code: '1' },
                    { title: '3 Formulir Hubungan Usaha', code: '3' },
                    { title: '4 Laporan PMPJ', code: '4' }
                ]
            }
        ]
    }
];

const filteredResults = computed(() => {
    return results.value.filter((item) => {
        const matchStatus = selectedStatus.value === 'all' || item.status === selectedStatus.value;
        const query = searchQuery.value.trim().toLowerCase();
        const matchSearch = !query || [item.client, item.engagement, item.form_code, item.form_name, item.status].some((value) => value.toLowerCase().includes(query));
        return matchStatus && matchSearch;
    });
});

async function fetchResults() {
    loading.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await fetch('/api/v1/audit-form-responses', {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        if (res.ok) {
            const raw = await res.json();
            // API balikin struktur nested (item.form.code, item.engagement.client.name, dst),
            // di-flatten di sini biar gampang dipakai buat search/filter/tampilan.
            results.value = raw.map((item: any): AuditResult => ({
                id: item.id,
                client: item.engagement?.client?.name || '-',
                client_id: item.engagement?.client?.id ?? null,
                engagement: item.engagement ? `${item.engagement.engagement_code ?? '-'} · ${item.engagement.engagement_year ?? '-'}` : '-',
                form_code: item.form?.code || '-',
                form_name: item.form?.name || '-',
                status: item.status,
                updated_at: item.submitted_at || item.created_at || null,
            }));
            selectedResult.value = results.value[0] || null;
        }
    } finally {
        loading.value = false;
    }
}

function statusLabel(status: string): string {
    return status.replaceAll('_', ' ');
}

function formatDate(value: string | null): string {
    if (!value) return '-';
    const d = new Date(value);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' · ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function toggleFolder(title: string): void {
    openFolders.value = { ...openFolders.value, [title]: !openFolders.value[title] };
}

function isFolderOpen(title: string): boolean {
    return openFolders.value[title] ?? false;
}

function selectResult(item: AuditResult): void {
    selectedResult.value = item;
}

function selectByCode(code: string): void {
    const found = results.value.find((item) => item.form_code === code);
    if (found) {
        selectedResult.value = found;
    }
}

// Buka form hasil terpilih: set klien aktif ke localStorage lalu navigasi ke halaman form-nya,
// pakai flow yang sama kayak alur normal (pilih klien -> isi form).
function openResult(item: AuditResult): void {
    if (item.client_id && item.client !== '-') {
        localStorage.setItem('selectedCompany', JSON.stringify({ id: item.client_id, name: item.client }));
    }
    if (item.form_code === '1100') {
        router.push('/form/1100');
    } else if (item.form_code && item.form_code !== '-') {
        router.push(`/form/dynamic/${item.form_code}`);
    }
}

onMounted(fetchResults);
</script>

<template>
    <DashboardLayout>
        <div class="admin-folder-drive">
            <div class="page-header">
                <div>
                    <span class="eyebrow">Admin workspace</span>
                    <h2>Folder Drive Admin</h2>
                    <p>Ruang pantau hasil form audit yang sudah masuk ke workflow submit, review, dan approval.</p>
                </div>
                <div class="toolbar">
                    <input v-model="searchQuery" class="search-input" type="search" placeholder="Cari client, form, status..." />
                    <select v-model="selectedStatus" class="status-filter">
                        <option value="all">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="pending_review">Pending Review</option>
                        <option value="reviewed">Reviewed</option>
                        <option value="revision_required">Revision Required</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
            </div>

            <div class="grid">
                <section class="card tree-panel">
                    <h3>Struktur Folder</h3>
                    <ul class="tree">
                        <li v-for="node in folderTree" :key="node.title">
                            <button class="folder-row root" @click="toggleFolder(node.title)">
                                <span>{{ isFolderOpen(node.title) ? '▾' : '▸' }}</span>
                                <strong>{{ node.title }}</strong>
                            </button>
                            <ul v-if="isFolderOpen(node.title)">
                                <li v-for="child in node.children" :key="child.title">
                                    <button class="folder-row" @click="toggleFolder(child.title)">
                                        <span>{{ isFolderOpen(child.title) ? '▾' : '▸' }}</span>
                                        <strong>{{ child.title }}</strong>
                                    </button>
                                    <ul v-if="isFolderOpen(child.title)">
                                        <li v-for="form in child.children" :key="form.title">
                                            <button class="file-row" @click="selectByCode(form.code || '')">
                                                <span class="file-dot"></span>
                                                {{ form.title }}
                                            </button>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </section>

                <section class="card result-panel">
                    <div class="panel-title">
                        <div>
                            <h3>Hasil Form Submit</h3>
                            <p>{{ filteredResults.length }} item ditemukan</p>
                        </div>
                    </div>
                    <p v-if="loading" class="hint">Memuat data...</p>
                    <div v-else class="result-list">
                        <button v-for="item in filteredResults" :key="item.id" :class="['result-card', selectedResult?.id === item.id ? 'active' : '']" @click="selectResult(item)">
                            <div>
                                <span class="form-code">{{ item.form_code }}</span>
                                <strong>{{ item.form_name }}</strong>
                                <p>{{ item.client }} · {{ item.engagement }}</p>
                            </div>
                            <span :class="['badge', item.status]">{{ statusLabel(item.status) }}</span>
                        </button>
                        <div v-if="filteredResults.length === 0" class="empty">Belum ada hasil submit.</div>
                    </div>
                </section>

                <section class="card detail-panel">
                    <template v-if="selectedResult">
                        <span :class="['badge', selectedResult.status]">{{ statusLabel(selectedResult.status) }}</span>
                        <h3>{{ selectedResult.form_code }} · {{ selectedResult.form_name }}</h3>
                        <div class="detail-grid">
                            <div><label>Client</label><strong>{{ selectedResult.client }}</strong></div>
                            <div><label>Engagement</label><strong>{{ selectedResult.engagement }}</strong></div>
                            <div><label>Update terakhir</label><strong>{{ formatDate(selectedResult.updated_at) }}</strong></div>
                        </div>
                        <button class="open-btn" @click="openResult(selectedResult)">Buka hasil form</button>
                    </template>
                    <template v-else>
                        <div class="empty-state">
                            <h3>Belum ada item dipilih</h3>
                            <p>Pilih salah satu hasil form untuk melihat ringkasannya.</p>
                        </div>
                    </template>
                </section>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.admin-folder-drive { display: flex; flex-direction: column; gap: 1.5rem; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; padding: 0.5rem 0 1rem; border-bottom: 1px solid var(--surface-border); }
.eyebrow { display: inline-block; margin-bottom: 0.4rem; color: var(--orange-600); font-size: 0.78rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
.page-header h2 { margin: 0; font-size: 1.8rem; }
.page-header p, .panel-title p { margin: 0.35rem 0 0; color: #7f8c8d; }
.toolbar { display: flex; gap: 0.75rem; }
.search-input, .status-filter { min-width: 220px; padding: 0.65rem 0.8rem; border: 1px solid var(--surface-border); border-radius: 8px; background: #fff; }
.grid { display: grid; grid-template-columns: 280px minmax(360px, 1fr) 320px; gap: 1rem; align-items: start; }
.card { background: #fff; border: 1px solid var(--surface-border); border-radius: 14px; padding: 1.25rem; box-shadow: 0 8px 24px rgba(44,62,80,0.06); }
.card h3 { margin: 0 0 1rem; }
.tree { list-style: none; padding-left: 0; margin: 0; }
.tree ul { list-style: none; padding-left: 1rem; margin: 0.45rem 0 0.7rem; border-left: 1px dashed var(--surface-border); }
.tree li { margin-bottom: 0.3rem; }
.folder-row, .file-row, .result-card { width: 100%; border: 0; background: transparent; text-align: left; cursor: pointer; font: inherit; }
.folder-row { display: flex; align-items: center; gap: 0.45rem; padding: 0.45rem 0.35rem; border-radius: 8px; color: var(--ink-900); }
.folder-row:hover, .file-row:hover { background: color-mix(in srgb, var(--orange-600) 8%, white); }
.folder-row.root { background: var(--surface); }
.file-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.5rem; border-radius: 8px; color: #56616f; }
.file-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--orange-600); flex: 0 0 auto; }
.panel-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.result-list { display: flex; flex-direction: column; gap: 0.75rem; }
.result-card { display: flex; justify-content: space-between; gap: 1rem; padding: 1rem; border: 1px solid var(--surface-border); border-radius: 12px; transition: 0.15s ease; }
.result-card:hover, .result-card.active { border-color: var(--orange-600); box-shadow: 0 6px 16px rgba(217,107,0,0.10); }
.result-card p { margin: 0.35rem 0 0; color: #7f8c8d; font-size: 0.85rem; }
.form-code { display: inline-block; margin-right: 0.5rem; color: var(--orange-600); font-weight: 800; }
.badge { height: fit-content; white-space: nowrap; padding: 0.32rem 0.65rem; border-radius: 999px; font-size: 0.78rem; font-weight: 700; text-transform: capitalize; background: color-mix(in srgb, var(--status-progress) 16%, white); color: var(--status-progress); }
.badge.draft { background: color-mix(in srgb, var(--status-neutral) 16%, white); color: var(--status-neutral); }
.badge.pending_review { background: color-mix(in srgb, var(--status-review) 16%, white); color: var(--status-review); }
.badge.reviewed { background: color-mix(in srgb, var(--status-progress) 16%, white); color: var(--status-progress); }
.badge.approved { background: color-mix(in srgb, var(--status-approved) 16%, white); color: var(--status-approved); }
.badge.revision_required { background: color-mix(in srgb, var(--status-overdue) 16%, white); color: var(--status-overdue); }
.detail-panel { position: sticky; top: 1rem; }
.detail-panel h3 { margin-top: 1rem; line-height: 1.35; }
.detail-grid { display: grid; gap: 1rem; margin: 1.25rem 0; }
.detail-grid label { display: block; margin-bottom: 0.25rem; color: #7f8c8d; font-size: 0.78rem; }
.detail-grid strong { color: var(--ink-900); }
.open-btn { width: 100%; padding: 0.75rem 1rem; border: 0; border-radius: 10px; background: var(--orange-600); color: #fff; font-weight: 700; cursor: pointer; }
.empty, .hint, .empty-state p { color: #7f8c8d; }
.empty { padding: 2rem; text-align: center; border: 1px dashed var(--surface-border); border-radius: 12px; }
@media (max-width: 1200px) { .grid { grid-template-columns: 1fr; } .detail-panel { position: static; } .page-header { align-items: stretch; flex-direction: column; } .toolbar { flex-direction: column; } }
</style>
