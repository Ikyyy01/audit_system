<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from './DashboardLayout.vue';

interface RoleItem { id: number; name: string; description: string; }
interface UserItem { id: number; name: string; email: string; role_id: number; role?: { id: number; name: string }; }
interface Assignment { id?: number; user_id: number; role_id: number; user?: UserItem; role?: RoleItem; }
interface Client { id: number; name: string; client_type: string; }
interface Engagement {
    id: number;
    client_id: number;
    engagement_code: string;
    engagement_year: number;
    status: string;
    client?: Client;
    assignments?: Assignment[];
}

const engagements = ref<Engagement[]>([]);
const clients = ref<Client[]>([]);
const roles = ref<RoleItem[]>([]);
const users = ref<UserItem[]>([]);

const showModal = ref(false);
const editMode = ref(false);
const formEngagementId = ref<number | null>(null);

const clientId = ref<number | null>(null);
const engagementCode = ref('');
const engagementYear = ref(new Date().getFullYear());
const status = ref<'draft' | 'active' | 'closed'>('active');
const formAssignments = ref<{ role_id: number; user_id: number | null }[]>([]);

const saving = ref(false);

// Detail tim drawer
const detailEngagement = ref<Engagement | null>(null);
const showDetail = ref(false);

function authHeaders(): Record<string, string> {
    const t = localStorage.getItem('token');
    return { Authorization: `Bearer ${t}`, Accept: 'application/json' };
}

async function fetchEngagements() {
    try {
        const res = await fetch('/api/v1/engagements', { headers: authHeaders() });
        if (res.ok) engagements.value = await res.json();
    } catch (e) { console.error(e); }
}

async function fetchClients() {
    try {
        const res = await fetch('/api/v1/clients', { headers: authHeaders() });
        if (res.ok) clients.value = await res.json();
    } catch (e) { console.error(e); }
}

async function fetchMetadata() {
    try {
        const res = await fetch('/api/v1/engagements-metadata', { headers: authHeaders() });
        if (res.ok) {
            const data = await res.json();
            roles.value = data.roles;
            users.value = data.users;
        }
    } catch (e) { console.error(e); }
}

function allUsersSorted(): UserItem[] {
    return [...users.value].sort((a, b) => a.name.localeCompare(b.name));
}

function resetForm() {
    clientId.value = null;
    engagementCode.value = '';
    engagementYear.value = new Date().getFullYear();
    status.value = 'active';
    formAssignments.value = roles.value.map(r => ({ role_id: r.id, user_id: null }));
}

function openAddModal() {
    editMode.value = false;
    formEngagementId.value = null;
    resetForm();
    showModal.value = true;
}

function openEditModal(eng: Engagement) {
    editMode.value = true;
    formEngagementId.value = eng.id;
    clientId.value = eng.client_id;
    engagementCode.value = eng.engagement_code;
    engagementYear.value = eng.engagement_year;
    status.value = eng.status as 'draft' | 'active' | 'closed';

    formAssignments.value = roles.value.map(r => {
        const existing = eng.assignments?.find(a => a.role_id === r.id);
        return { role_id: r.id, user_id: existing?.user_id ?? existing?.user?.id ?? null };
    });

    showModal.value = true;
}

async function saveEngagement() {
    if (!clientId.value) { alert('Pilih klien terlebih dahulu'); return; }
    if (!engagementCode.value.trim()) { alert('Kode engagement wajib diisi'); return; }
    saving.value = true;

    const assignData = formAssignments.value
        .filter(a => a.user_id)
        .map(a => ({ role_id: a.role_id, user_id: a.user_id }));

    const body = {
        client_id: clientId.value,
        engagement_code: engagementCode.value.trim(),
        engagement_year: engagementYear.value,
        status: status.value,
        assignments: assignData,
    };

    try {
        const url = editMode.value
            ? `/api/v1/engagements/${formEngagementId.value}`
            : '/api/v1/engagements';
        const method = editMode.value ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });

        if (res.ok) {
            showModal.value = false;
            fetchEngagements();
        } else {
            const err = await res.json();
            alert(`Gagal menyimpan: ${err.message || JSON.stringify(err.errors || err)}`);
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    } finally {
        saving.value = false;
    }
}

async function deleteEngagement(id: number) {
    if (!confirm('Hapus engagement ini beserta semua data assignment-nya?')) return;
    try {
        const res = await fetch(`/api/v1/engagements/${id}`, {
            method: 'DELETE',
            headers: authHeaders(),
        });
        if (res.ok) {
            fetchEngagements();
            if (detailEngagement.value?.id === id) showDetail.value = false;
        } else {
            alert('Gagal menghapus engagement');
        }
    } catch (e) { console.error(e); }
}

function openDetail(eng: Engagement) {
    detailEngagement.value = eng;
    showDetail.value = true;
}

function statusLabel(s: string) {
    switch (s) {
        case 'active': return 'Aktif';
        case 'closed': return 'Selesai';
        default: return 'Draft';
    }
}
function statusClass(s: string) {
    switch (s) {
        case 'active': return 'badge-status--active';
        case 'closed': return 'badge-status--closed';
        default: return 'badge-status--draft';
    }
}

function assignedRoleName(eng: Engagement, roleName: string): string {
    const a = eng.assignments?.find(a => a.role?.name === roleName);
    return a?.user?.name ?? '-';
}

onMounted(async () => {
    await Promise.all([fetchEngagements(), fetchClients(), fetchMetadata()]);
});
</script>

<template>
    <DashboardLayout>
        <div class="engagement-crud">
            <div class="crud-header">
                <div>
                    <h2>Kelola Engagement</h2>
                    <p class="subtitle">Kelola perikatan audit per klien, tahun buku, dan penugasan tim.</p>
                </div>
                <button class="btn primary" @click="openAddModal">+ Tambah Engagement</button>
            </div>

            <!-- Tabel Engagement -->
            <div class="card table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Klien</th>
                            <th>Tahun</th>
                            <th>Status</th>
                            <th>Partner</th>
                            <th>Manager</th>
                            <th>Senior</th>
                            <th>Junior</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="eng in engagements" :key="eng.id">
                            <td><strong>{{ eng.engagement_code }}</strong></td>
                            <td>{{ eng.client?.name || '-' }}</td>
                            <td>{{ eng.engagement_year }}</td>
                            <td><span :class="['badge-status', statusClass(eng.status)]">{{ statusLabel(eng.status) }}</span></td>
                            <td class="cell-user">{{ assignedRoleName(eng, 'Partner') }}</td>
                            <td class="cell-user">{{ assignedRoleName(eng, 'Manager') }}</td>
                            <td class="cell-user">{{ assignedRoleName(eng, 'Senior') }}</td>
                            <td class="cell-user">{{ assignedRoleName(eng, 'Junior') }}</td>
                            <td class="cell-actions">
                                <button class="btn-action btn-action-detail" @click="openDetail(eng)">Detail</button>
                                <button class="btn-action btn-action-edit" @click="openEditModal(eng)">Edit</button>
                                <button class="btn-action btn-action-danger" @click="deleteEngagement(eng.id)">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="engagements.length === 0">
                            <td colspan="9" class="empty">Belum ada engagement. Klik "Tambah Engagement" untuk memulai.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detail Drawer -->
            <div v-if="showDetail && detailEngagement" class="detail-overlay" @click.self="showDetail = false">
                <div class="detail-panel card">
                    <div class="detail-header">
                        <h3>Detail Engagement</h3>
                        <button class="btn-close" @click="showDetail = false">&times;</button>
                    </div>
                    <div class="detail-body">
                        <div class="detail-row">
                            <span class="detail-label">Kode Engagement</span>
                            <span class="detail-value"><strong>{{ detailEngagement.engagement_code }}</strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Klien</span>
                            <span class="detail-value">{{ detailEngagement.client?.name || '-' }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tahun Buku</span>
                            <span class="detail-value">{{ detailEngagement.engagement_year }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span :class="['badge-status', statusClass(detailEngagement.status)]">{{ statusLabel(detailEngagement.status) }}</span>
                        </div>
                        <hr class="detail-divider" />
                        <h4 class="detail-section-title">Tim Audit</h4>
                        <div v-if="detailEngagement.assignments && detailEngagement.assignments.length > 0">
                            <div v-for="a in detailEngagement.assignments" :key="a.role_id" class="team-row">
                                <span class="team-role">{{ a.role?.name || 'Role' }}</span>
                                <span class="team-user">{{ a.user?.name || '-' }} <em v-if="a.user?.email">({{ a.user.email }})</em></span>
                            </div>
                        </div>
                        <div v-else class="empty-detail">Belum ada anggota tim yang ditugaskan.</div>
                    </div>
                    <div class="detail-footer">
                        <button class="btn primary" @click="showDetail = false; openEditModal(detailEngagement)">Edit Engagement</button>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah/Edit -->
            <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                <div class="card modal-box">
                    <h3>{{ editMode ? 'Edit Engagement' : 'Tambah Engagement Baru' }}</h3>

                    <div class="form-group">
                        <label>Klien / Perusahaan <span class="req">*</span></label>
                        <select v-model="clientId">
                            <option :value="null" disabled>— Pilih Klien —</option>
                            <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }} ({{ c.client_type }})</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-2">
                            <label>Kode Engagement <span class="req">*</span></label>
                            <input v-model="engagementCode" type="text" placeholder="Contoh: IAS-2024" />
                        </div>
                        <div class="form-group flex-1">
                            <label>Tahun Buku <span class="req">*</span></label>
                            <input v-model.number="engagementYear" type="number" min="2000" max="2100" />
                        </div>
                        <div class="form-group flex-1">
                            <label>Status</label>
                            <select v-model="status">
                                <option value="draft">Draft</option>
                                <option value="active">Aktif</option>
                                <option value="closed">Selesai</option>
                            </select>
                        </div>
                    </div>

                    <hr class="modal-divider" />
                    <h4 class="assignment-title">Penugasan Tim Audit</h4>
                    <p class="assignment-hint">Pilih anggota tim per role. Kosongkan jika belum ditentukan.</p>

                    <div v-for="assign in formAssignments" :key="assign.role_id" class="form-group assignment-row">
                        <label class="role-label">{{ roles.find(r => r.id === assign.role_id)?.name || 'Role' }}</label>
                        <select v-model="assign.user_id">
                            <option :value="null">— Belum Ditentukan —</option>
                            <option v-for="u in allUsersSorted()" :key="u.id" :value="u.id">{{ u.name }} ({{ u.role?.name || '-' }})</option>
                        </select>
                    </div>

                    <div class="modal-actions">
                        <button class="btn secondary" @click="showModal = false" :disabled="saving">Batal</button>
                        <button class="btn primary" @click="saveEngagement" :disabled="saving">
                            {{ saving ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.engagement-crud { width: 100%; }
.crud-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.crud-header h2 { margin: 0; }
.subtitle { margin: 0.2rem 0 0; color: #7f8c8d; font-size: 0.9rem; }

.table-card { padding: 0; overflow: auto; }
table { width: 100%; border-collapse: collapse; min-width: 850px; }
th { background: var(--surface); padding: 0.8rem 1rem; text-align: left; font-size: 0.82rem; color: #7f8c8d; border-bottom: 2px solid var(--surface-border); white-space: nowrap; }
td { padding: 0.8rem 1rem; border-bottom: 1px solid var(--surface-border); font-size: 0.9rem; }
.empty { text-align: center; color: #95a5a6; padding: 2rem; }
.cell-user { font-size: 0.85rem; color: #555; max-width: 110px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cell-actions { white-space: nowrap; }

.badge-status { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 3px; font-size: 0.78rem; font-weight: 600; }
.badge-status--active { background: #d1fae5; color: #065f46; }
.badge-status--closed { background: #e5e7eb; color: #374151; }
.badge-status--draft { background: #fef3c7; color: #92400e; }

.btn { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 0.9rem; }
.btn.primary { background: var(--orange-600); color: #ffffff !important; }
.btn.primary:hover { background: var(--orange-600-hover); }
.btn.primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn.secondary { background: #e2e8f0; color: #334155 !important; border: 1px solid #cbd5e1; }
.btn.secondary:hover { background: #cbd5e1; }

/* Tombol Aksi di Baris Tabel */
.btn-action {
    display: inline-block;
    padding: 0.35rem 0.65rem;
    border-radius: 4px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    margin-right: 0.35rem;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.btn-action-detail {
    background: #f1f5f9;
    color: #0f172a !important;
    border: 1px solid #cbd5e1;
}
.btn-action-detail:hover {
    background: #e2e8f0;
    color: #0284c7 !important;
    border-color: #0284c7;
}

.btn-action-edit {
    background: #eff6ff;
    color: #1d4ed8 !important;
    border: 1px solid #bfdbfe;
}
.btn-action-edit:hover {
    background: #dbeafe;
    color: #1e40af !important;
    border-color: #93c5fd;
}

.btn-action-danger {
    background: #fef2f2;
    color: #dc2626 !important;
    border: 1px solid #fecaca;
}
.btn-action-danger:hover {
    background: #fee2e2;
    color: #991b1b !important;
    border-color: #f87171;
}

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: flex-start; padding-top: 5vh; z-index: 100; overflow-y: auto; }
.modal-box { width: 580px; padding: 2rem; background: white; max-height: 90vh; overflow-y: auto; }
.modal-box h3 { margin-top: 0; margin-bottom: 1.2rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.3rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; }
.form-group select { cursor: pointer; }
.req { color: #e74c3c; }
.form-row { display: flex; gap: 0.8rem; }
.flex-1 { flex: 1; }
.flex-2 { flex: 2; }
.modal-divider { border: none; border-top: 1px solid #eee; margin: 1.2rem 0; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; }

.assignment-title { margin: 0 0 0.2rem; font-size: 1rem; }
.assignment-hint { margin: 0 0 1rem; color: #95a5a6; font-size: 0.82rem; }
.assignment-row { display: flex; align-items: center; gap: 0.8rem; }
.assignment-row .role-label { min-width: 80px; font-weight: 600; color: var(--ink-900); font-size: 0.88rem; }
.assignment-row select { flex: 1; }

/* Detail Panel */
.detail-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.35); display: flex; justify-content: flex-end; z-index: 100; }
.detail-panel { width: 420px; height: 100vh; overflow-y: auto; border-radius: 0; display: flex; flex-direction: column; }
.detail-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--surface-border); }
.detail-header h3 { margin: 0; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; line-height: 1; }
.btn-close:hover { color: #333; }
.detail-body { flex: 1; padding: 1.5rem; }
.detail-row { display: flex; justify-content: space-between; margin-bottom: 0.8rem; }
.detail-label { color: #7f8c8d; font-size: 0.85rem; }
.detail-value { font-size: 0.9rem; text-align: right; }
.detail-divider { border: none; border-top: 1px solid #eee; margin: 1rem 0; }
.detail-section-title { margin: 0.5rem 0 0.8rem; font-size: 0.95rem; }
.team-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #eee; }
.team-role { font-weight: 600; font-size: 0.88rem; color: var(--ink-900); }
.team-user { font-size: 0.88rem; color: #555; text-align: right; }
.team-user em { color: #95a5a6; font-style: normal; font-size: 0.8rem; }
.empty-detail { color: #95a5a6; font-size: 0.88rem; text-align: center; padding: 1rem 0; }
.detail-footer { padding: 1.2rem 1.5rem; border-top: 1px solid var(--surface-border); display: flex; justify-content: flex-end; }
</style>
