<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from './DashboardLayout.vue';

interface Role {
    id: number;
    name: string;
    description: string;
}

interface AssignmentItem {
    id: number;
    role?: { name: string };
    engagement?: {
        engagement_code: string;
        engagement_year: number;
        client?: { name: string };
    };
}

interface UserItem {
    id: number;
    role_id: number;
    name: string;
    email: string;
    role?: Role;
    assignments_count?: number;
    responses_count?: number;
    assignments?: AssignmentItem[];
}

const users = ref<UserItem[]>([]);
const roles = ref<Role[]>([]);

const showModal = ref(false);
const editMode = ref(false);
const formUserId = ref<number | null>(null);

const name = ref('');
const email = ref('');
const roleId = ref<number | null>(null);
const password = ref('');

const saving = ref(false);

// Detail drawer
const showDetail = ref(false);
const detailUser = ref<UserItem | null>(null);

function authHeaders(): Record<string, string> {
    const t = localStorage.getItem('token');
    return { Authorization: `Bearer ${t}`, Accept: 'application/json' };
}

async function fetchUsers() {
    try {
        const res = await fetch('/api/v1/users', { headers: authHeaders() });
        if (res.ok) users.value = await res.json();
    } catch (e) { console.error(e); }
}

async function fetchRoles() {
    try {
        const res = await fetch('/api/v1/roles', { headers: authHeaders() });
        if (res.ok) roles.value = await res.json();
    } catch (e) { console.error(e); }
}

function resetForm() {
    name.value = '';
    email.value = '';
    roleId.value = roles.value.length > 0 ? roles.value[0].id : null;
    password.value = '';
}

function openAddModal() {
    editMode.value = false;
    formUserId.value = null;
    resetForm();
    showModal.value = true;
}

function openEditModal(user: UserItem) {
    editMode.value = true;
    formUserId.value = user.id;
    name.value = user.name;
    email.value = user.email;
    roleId.value = user.role_id;
    password.value = ''; // kosong jika tidak ingin ubah password
    showModal.value = true;
}

async function saveUser() {
    if (!name.value.trim()) { alert('Nama pegawai wajib diisi'); return; }
    if (!email.value.trim()) { alert('Email wajib diisi'); return; }
    if (!roleId.value) { alert('Pilih role pegawai'); return; }
    if (!editMode.value && !password.value.trim()) { alert('Password wajib diisi untuk pegawai baru'); return; }

    saving.value = true;

    const body: Record<string, any> = {
        name: name.value.trim(),
        email: email.value.trim(),
        role_id: roleId.value,
    };
    if (password.value.trim()) {
        body.password = password.value.trim();
    }

    try {
        const url = editMode.value ? `/api/v1/users/${formUserId.value}` : '/api/v1/users';
        const method = editMode.value ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: { ...authHeaders(), 'Content-Type': 'application/json' },
            body: JSON.stringify(body),
        });

        if (res.ok) {
            showModal.value = false;
            fetchUsers();
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

async function deleteUser(user: UserItem) {
    if (!confirm(`Hapus akun pegawai "${user.name}" (${user.email})?`)) return;
    try {
        const res = await fetch(`/api/v1/users/${user.id}`, {
            method: 'DELETE',
            headers: authHeaders(),
        });
        if (res.ok) {
            fetchUsers();
            if (detailUser.value?.id === user.id) showDetail.value = false;
        } else {
            const err = await res.json();
            alert(err.message || 'Gagal menghapus pegawai');
        }
    } catch (e) { console.error(e); }
}

async function openDetail(user: UserItem) {
    try {
        const res = await fetch(`/api/v1/users/${user.id}`, { headers: authHeaders() });
        if (res.ok) {
            detailUser.value = await res.json();
            showDetail.value = true;
        }
    } catch (e) { console.error(e); }
}

function roleBadgeClass(roleName?: string) {
    switch (roleName) {
        case 'Admin': return 'badge-role--admin';
        case 'Partner': return 'badge-role--partner';
        case 'Manager': return 'badge-role--manager';
        case 'Senior': return 'badge-role--senior';
        default: return 'badge-role--junior';
    }
}

onMounted(async () => {
    await Promise.all([fetchUsers(), fetchRoles()]);
});
</script>

<template>
    <DashboardLayout>
        <div class="user-crud">
            <div class="crud-header">
                <div>
                    <h2>Kelola Pegawai / Auditor</h2>
                    <p class="subtitle">Kelola akun pengguna, role jabatan, dan akses sistem KAP MGN.</p>
                </div>
                <button class="btn primary" @click="openAddModal">+ Tambah Pegawai</button>
            </div>

            <!-- Tabel Users -->
            <div class="card table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Pegawai</th>
                            <th>Email</th>
                            <th>Role Jabatan</th>
                            <th>Penugasan Perikatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in users" :key="u.id">
                            <td>
                                <strong>{{ u.name }}</strong>
                            </td>
                            <td class="cell-email">{{ u.email }}</td>
                            <td>
                                <span :class="['badge-role', roleBadgeClass(u.role?.name)]">
                                    {{ u.role?.name || 'Auditor' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge-count">{{ u.assignments_count ?? 0 }} Engagement</span>
                            </td>
                            <td class="cell-actions">
                                <button class="btn-action btn-action-detail" @click="openDetail(u)">Detail</button>
                                <button class="btn-action btn-action-edit" @click="openEditModal(u)">Edit</button>
                                <button class="btn-action btn-action-danger" @click="deleteUser(u)">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="5" class="empty">Belum ada data pegawai. Klik "Tambah Pegawai" untuk memulai.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Detail Drawer -->
            <div v-if="showDetail && detailUser" class="detail-overlay" @click.self="showDetail = false">
                <div class="detail-panel card">
                    <div class="detail-header">
                        <h3>Detail Pegawai</h3>
                        <button class="btn-close" @click="showDetail = false">&times;</button>
                    </div>
                    <div class="detail-body">
                        <div class="detail-row">
                            <span class="detail-label">Nama Lengkap</span>
                            <span class="detail-value"><strong>{{ detailUser.name }}</strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Email Login</span>
                            <span class="detail-value">{{ detailUser.email }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Role Jabatan</span>
                            <span :class="['badge-role', roleBadgeClass(detailUser.role?.name)]">{{ detailUser.role?.name }}</span>
                        </div>
                        <hr class="detail-divider" />
                        <h4 class="detail-section-title">Riwayat Penugasan Perikatan</h4>
                        <div v-if="detailUser.assignments && detailUser.assignments.length > 0">
                            <div v-for="a in detailUser.assignments" :key="a.id" class="team-row">
                                <div class="team-left">
                                    <strong>{{ a.engagement?.engagement_code }}</strong>
                                    <span class="client-sub">{{ a.engagement?.client?.name }} ({{ a.engagement?.engagement_year }})</span>
                                </div>
                                <span class="role-tag">{{ a.role?.name || 'Anggota' }}</span>
                            </div>
                        </div>
                        <div v-else class="empty-detail">Belum ada penugasan perikatan untuk pegawai ini.</div>
                    </div>
                    <div class="detail-footer">
                        <button class="btn primary" @click="showDetail = false; openEditModal(detailUser)">Edit Akun Pegawai</button>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah/Edit -->
            <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                <div class="card modal-box">
                    <h3>{{ editMode ? 'Edit Data Pegawai' : 'Tambah Pegawai Baru' }}</h3>

                    <div class="form-group">
                        <label>Nama Lengkap <span class="req">*</span></label>
                        <input v-model="name" type="text" placeholder="Contoh: Budi Santoso, S.E., Ak." />
                    </div>

                    <div class="form-group">
                        <label>Email Login <span class="req">*</span></label>
                        <input v-model="email" type="email" placeholder="budi@kapmgn.test" />
                    </div>

                    <div class="form-group">
                        <label>Role Jabatan <span class="req">*</span></label>
                        <select v-model="roleId">
                            <option :value="null" disabled>— Pilih Role —</option>
                            <option v-for="r in roles" :key="r.id" :value="r.id">
                                {{ r.name }} — {{ r.description }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>
                            {{ editMode ? 'Password Baru (Kosongkan jika tidak diubah)' : 'Password Login *' }}
                        </label>
                        <input v-model="password" type="password" placeholder="Minimal 6 karakter..." />
                    </div>

                    <div class="modal-actions">
                        <button class="btn secondary" @click="showModal = false" :disabled="saving">Batal</button>
                        <button class="btn primary" @click="saveUser" :disabled="saving">
                            {{ saving ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.user-crud { width: 100%; }
.crud-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.crud-header h2 { margin: 0; }
.subtitle { margin: 0.2rem 0 0; color: #7f8c8d; font-size: 0.9rem; }

.table-card { padding: 0; overflow: auto; }
table { width: 100%; border-collapse: collapse; min-width: 800px; }
th { background: var(--surface); padding: 0.8rem 1rem; text-align: left; font-size: 0.82rem; color: #7f8c8d; border-bottom: 2px solid var(--surface-border); white-space: nowrap; }
td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--surface-border); font-size: 0.9rem; }
.empty { text-align: center; color: #95a5a6; padding: 2rem; }
.cell-email { color: #555; }
.cell-actions { white-space: nowrap; }

/* Role Badges */
.badge-role { display: inline-block; padding: 0.25rem 0.65rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
.badge-role--admin { background: #fee2e2; color: #991b1b; }
.badge-role--partner { background: #f3e8ff; color: #6b21a8; }
.badge-role--manager { background: #dbeafe; color: #1e40af; }
.badge-role--senior { background: #d1fae5; color: #065f46; }
.badge-role--junior { background: #f3f4f6; color: #374151; }

.badge-count { display: inline-block; padding: 0.2rem 0.5rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.8rem; color: #475569; }

/* Buttons */
.btn { padding: 0.6rem 1.2rem; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 0.9rem; }
.btn.primary { background: var(--orange-600); color: #ffffff !important; }
.btn.primary:hover { background: var(--orange-600-hover); }
.btn.primary:disabled { opacity: 0.6; cursor: not-allowed; }
.btn.secondary { background: #e2e8f0; color: #334155 !important; border: 1px solid #cbd5e1; }
.btn.secondary:hover { background: #cbd5e1; }

/* Action Buttons */
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
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: flex-start; padding-top: 8vh; z-index: 100; overflow-y: auto; }
.modal-box { width: 480px; padding: 2rem; background: white; border-radius: 8px; }
.modal-box h3 { margin-top: 0; margin-bottom: 1.2rem; }
.form-group { margin-bottom: 1.1rem; }
.form-group label { display: block; font-size: 0.85rem; color: #475569; margin-bottom: 0.35rem; font-weight: 500; }
.form-group input, .form-group select { width: 100%; padding: 0.65rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
.req { color: #dc2626; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.8rem; }

/* Detail Panel */
.detail-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.35); display: flex; justify-content: flex-end; z-index: 100; }
.detail-panel { width: 420px; height: 100vh; overflow-y: auto; border-radius: 0; display: flex; flex-direction: column; }
.detail-header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--surface-border); }
.detail-header h3 { margin: 0; }
.btn-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999; line-height: 1; }
.btn-close:hover { color: #333; }
.detail-body { flex: 1; padding: 1.5rem; }
.detail-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.8rem; }
.detail-label { color: #7f8c8d; font-size: 0.85rem; }
.detail-value { font-size: 0.9rem; text-align: right; }
.detail-divider { border: none; border-top: 1px solid #eee; margin: 1rem 0; }
.detail-section-title { margin: 0.5rem 0 0.8rem; font-size: 0.95rem; }
.team-row { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; border-bottom: 1px dashed #eee; }
.team-left { display: flex; flex-direction: column; }
.client-sub { font-size: 0.78rem; color: #64748b; }
.role-tag { font-size: 0.78rem; font-weight: 600; padding: 0.15rem 0.4rem; background: #f1f5f9; color: #334155; border-radius: 3px; }
.empty-detail { color: #95a5a6; font-size: 0.88rem; text-align: center; padding: 1rem 0; }
.detail-footer { padding: 1.2rem 1.5rem; border-top: 1px solid var(--surface-border); display: flex; justify-content: flex-end; }
</style>
