<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from './DashboardLayout.vue';

// Auto-resize textarea Alamat, konsisten sama semua form lain (Form1100, DynamicForm)
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

interface Client {
    id: number;
    name: string;
    client_type: string;
    address: string;
}

const clients = ref<Client[]>([]);
const showModal = ref(false);
const editMode = ref(false);
const formId = ref<number | null>(null);
const name = ref('');
const clientType = ref('TBK');
const address = ref('');

async function fetchClients() {
    try {
        const currentToken = localStorage.getItem('token');
        const res = await fetch('/api/v1/clients', {
            headers: { Authorization: `Bearer ${currentToken}`, Accept: 'application/json' }
        });
        if (res.ok) {
            clients.value = await res.json();
        } else {
            console.error('Fetch error:', res.status);
        }
    } catch (e) {
        console.error(e);
    }
}

function openAddModal() {
    editMode.value = false;
    formId.value = null;
    name.value = '';
    clientType.value = 'TBK';
    address.value = '';
    showModal.value = true;
}

function openEditModal(client: Client) {
    editMode.value = true;
    formId.value = client.id;
    name.value = client.name;
    clientType.value = client.client_type;
    address.value = client.address;
    showModal.value = true;
}

async function saveClient() {
    try {
        const currentToken = localStorage.getItem('token');
        const url = editMode.value ? `/api/v1/clients/${formId.value}` : '/api/v1/clients';
        const method = editMode.value ? 'PUT' : 'POST';
        
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${currentToken}`, Accept: 'application/json' },
            body: JSON.stringify({ name: name.value, client_type: clientType.value, address: address.value })
        });

        if (res.ok) {
            showModal.value = false;
            fetchClients();
        } else {
            const err = await res.json();
            alert(`Gagal menyimpan data: ${err.message || 'Terjadi kesalahan'}`);
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan jaringan');
    }
}

async function deleteClient(id: number) {
    if (!confirm('Hapus perusahaan ini?')) return;
    try {
        const currentToken = localStorage.getItem('token');
        const res = await fetch(`/api/v1/clients/${id}`, {
            method: 'DELETE',
            headers: { Authorization: `Bearer ${currentToken}`, Accept: 'application/json' }
        });
        if (res.ok) {
            fetchClients();
        } else {
            alert('Gagal menghapus data');
        }
    } catch (e) {
        console.error(e);
    }
}

onMounted(fetchClients);
</script>

<template>
    <DashboardLayout>
        <div class="client-crud">
            <div class="crud-header">
                <h2>Manajemen Klien / Perusahaan</h2>
                <button class="btn primary" @click="openAddModal">+ Tambah Perusahaan</button>
            </div>

            <div class="card table-card">
                <table>
                    <thead>
                        <tr>
                            <th>Nama Perusahaan</th>
                            <th>Tipe</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in clients" :key="c.id">
                            <td><strong>{{ c.name }}</strong></td>
                            <td><span :class="['badge-client-type', c.client_type === 'TBK' ? 'badge-client-type--tbk' : 'badge-client-type--non-tbk']">{{ c.client_type }}</span></td>
                            <td>{{ c.address || '-' }}</td>
                            <td>
                                <button class="btn-sm" @click="openEditModal(c)">Edit</button>
                                <button class="btn-sm danger" @click="deleteClient(c.id)">Hapus</button>
                            </td>
                        </tr>
                        <tr v-if="clients.length === 0">
                            <td colspan="4" class="empty">Belum ada data perusahaan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal Form -->
            <div v-if="showModal" class="modal-overlay">
                <div class="card modal-box">
                    <h3>{{ editMode ? 'Edit Perusahaan' : 'Tambah Perusahaan' }}</h3>
                    <div class="form-group">
                        <label>Nama Perusahaan</label>
                        <input v-model="name" type="text" placeholder="Contoh: PT ABC Tbk" />
                    </div>
                    <div class="form-group">
                        <label>Tipe Klien</label>
                        <select v-model="clientType">
                            <option value="TBK">TBK</option>
                            <option value="Non-TBK">Non-TBK</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <textarea v-model="address" v-auto-resize placeholder="Alamat perusahaan..."></textarea>
                    </div>
                    <div class="modal-actions">
                        <button class="btn secondary" @click="showModal = false">Batal</button>
                        <button class="btn primary" @click="saveClient">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.client-crud { width: 100%; }
.crud-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.crud-header h2 { margin: 0; }

.table-card { padding: 0; overflow: hidden; }
table { width: 100%; border-collapse: collapse; }
th { background: var(--surface); padding: 1rem; text-align: left; font-size: 0.85rem; color: #7f8c8d; border-bottom: 2px solid var(--surface-border); }
td { padding: 1rem; border-bottom: 1px solid var(--surface-border); }
.empty { text-align: center; color: #95a5a6; padding: 2rem; }

.btn { padding: 0.6rem 1.2rem; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; }
.btn.primary { background: var(--orange-600); color: white; }
.btn.primary:hover { background: var(--orange-600-hover); }
.btn.secondary { background: var(--surface); color: var(--ink-900); }
.btn-sm { padding: 0.3rem 0.6rem; border-radius: 3px; border: 1px solid #ccc; background: #fff; cursor: pointer; margin-right: 0.3rem; }
.btn-sm.danger { color: var(--status-overdue); border-color: var(--status-overdue); }

.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 100; }
.modal-box { width: 450px; padding: 2rem; background: white; }
.modal-box h3 { margin-top: 0; margin-bottom: 1.5rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.3rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.6rem; border: 1px solid #ddd; border-radius: 4px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem; }
</style>
