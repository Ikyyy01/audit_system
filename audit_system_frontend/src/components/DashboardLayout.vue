<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';

const router = useRouter();
const route = useRoute();
const user = JSON.parse(localStorage.getItem('user') || '{}');
const userRole = typeof user.role === 'object' && user.role !== null ? user.role.name : (user.role || 'Auditor');

const selectedCompany = ref<{ id: number; name: string } | null>(
    JSON.parse(localStorage.getItem('selectedCompany') || 'null')
);

interface Client { id: number; name: string; client_type: string; }
const companies = ref<Client[]>([]);
const loadingClients = ref(false);

const flows = [
    {
        title: 'PENERIMAAN & INDEPENDENSI',
        forms: [
            { code: '1100', name: 'Memo Penerimaan Klien' },
            { code: '1110', name: 'Survey Klien' },
            { code: '1120', name: 'Surat Keberatan Professional' },
            { code: '1130A', name: 'Independence Checklist' },
            { code: '1130B', name: 'First Pass Data' },
            { code: '1130C', name: 'Background Check' },
            { code: '1130D', name: 'Entities Tree / UBO' },
            { code: '1200', name: 'Konfirmasi Independensi' },
            { code: '1210', name: 'Kuisioner Independensi' },
        ]
    },
    {
        title: 'PERENCANAAN & RISIKO',
        forms: [
            { code: '1400', name: 'Laporan Risiko' },
            { code: '1410', name: 'Pemahaman Peraturan' },
            { code: '1420', name: 'Prosedur Analitik Awal' },
            { code: '1430', name: 'Proses Pelaporan Keuangan' },
            { code: '1440', name: 'Fraud Risk Assessment' },
            { code: '1441', name: '↳ Interview Klien' },
            { code: '1450', name: 'Penilaian Risiko Bisnis' },
            { code: '1460', name: 'Pengendalian Internal' },
        ]
    },
    {
        title: 'MATERIALITAS & PERIKATAN',
        forms: [
            { code: '1500', name: 'Risiko Tingkat LK per Akun' },
            { code: '1600', name: 'Penentuan Materialitas' },
            { code: '1610', name: 'Materiality Sampling' },
            { code: '1700', name: 'Alokasi Jam Jasa' },
            { code: '1900', name: 'Komunikasi Tim Perikatan' },
        ]
    },
    {
        title: 'RESPONS RISIKO',
        forms: [
            { code: '2100', name: 'Strategi Audit' },
            { code: '2110', name: 'Komunikasi Tim Audit' },
            { code: '2200', name: 'Uji Pengendalian' },
            { code: '2300', name: 'Materiality Sampling MUS' },
            { code: '2400', name: 'Pemeriksaan IT' },
            { code: '2410', name: 'Pengendalian Umum Komputer' },
            { code: '2420', name: 'Siklus Bisnis IT' },
        ]
    },
    {
        title: 'BUKTI AUDIT',
        forms: [
            { code: '3100', name: 'Balance Sheet' },
        ]
    },
    {
        title: 'REPRESENTASI & PELAPORAN',
        forms: [
            { code: '4100', name: 'Konsultasi Partner' },
            { code: '4200', name: 'Surat Representasi' },
            { code: '5100', name: 'Working Balance Sheet' },
            { code: '5200', name: 'Confirmation Judgement' },
            { code: '5300', name: 'Materialitas Final' },
            { code: '5500', name: 'Checklist Audit' },
            { code: '5700', name: 'Evaluasi Bukti Audit' },
            { code: '5900', name: 'Memorandum Audit Final' },
        ]
    },
];

async function fetchClients() {
    loadingClients.value = true;
    try {
        const token = localStorage.getItem('token');
        const res = await fetch('/api/v1/clients', {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        if (res.status === 401) {
            // Token expired / invalid — bersihkan sesi dan paksa login ulang
            localStorage.clear();
            router.push('/');
            return;
        }
        if (res.ok) {
            companies.value = await res.json();
        }
    } catch (e) {
        console.error(e);
    } finally {
        loadingClients.value = false;
    }
}

function selectCompany(company: Client) {
    selectedCompany.value = company;
    localStorage.setItem('selectedCompany', JSON.stringify(company));
    router.push('/form/1100');
}

function changeClient() {
    selectedCompany.value = null;
    localStorage.removeItem('selectedCompany');
    fetchClients();
}

function logout() {
    localStorage.clear();
    router.push('/');
}

onMounted(() => {
    if (!selectedCompany.value) {
        fetchClients();
    }
});
</script>

<template>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="brand-header">
                <div class="brand-logo-bg">
                    <img src="/logo-mgn-utama.png" alt="KAP MGN" class="brand-img" />
                </div>
                <div class="brand-titles">
                    <span class="brand-name">KAP MGN &amp; Rekan</span>
                    <span class="brand-sub">Audit Management System</span>
                </div>
            </div>

            <div class="sidebar-scroll">
                <!-- Navigation Items -->
                <div class="nav-section">
                    <span class="nav-section-title">UTAMA</span>
                    <router-link to="/dashboard" class="nav-item" :class="{ active: route.path === '/dashboard' }">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="9" rx="1"></rect>
                                <rect x="14" y="3" width="7" height="5" rx="1"></rect>
                                <rect x="14" y="12" width="7" height="9" rx="1"></rect>
                                <rect x="3" y="16" width="7" height="5" rx="1"></rect>
                            </svg>
                        </span>
                        <span>Dashboard</span>
                    </router-link>
                    <router-link v-if="userRole === 'Admin'" to="/admin/folders" class="nav-item" :class="{ active: route.path === '/admin/folders' }">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </span>
                        <span>Folder Drive Admin</span>
                    </router-link>
                    <router-link v-if="userRole === 'Admin'" to="/clients" class="nav-item" :class="{ active: route.path === '/clients' }">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </span>
                        <span>Kelola Klien</span>
                    </router-link>
                    <router-link v-if="userRole === 'Admin'" to="/engagements" class="nav-item" :class="{ active: route.path === '/engagements' }">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                <path d="M9 14l2 2 4-4"></path>
                            </svg>
                        </span>
                        <span>Kelola Engagement</span>
                    </router-link>
                    <router-link v-if="userRole === 'Admin'" to="/users" class="nav-item" :class="{ active: route.path === '/users' }">
                        <span class="nav-icon">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </span>
                        <span>Kelola Pegawai</span>
                    </router-link>
                </div>

                <div v-if="selectedCompany" class="client-active-box">
                    <div class="client-info">
                        <span class="client-label">KLIEN AKTIF</span>
                        <strong class="client-name-text">{{ selectedCompany.name }}</strong>
                    </div>
                    <button class="btn-change" @click="changeClient">
                        Ganti Client
                    </button>
                </div>

                <div v-if="selectedCompany" class="nav-flows">
                    <div v-for="flow in flows" :key="flow.title" class="nav-section">
                        <span class="nav-section-title">{{ flow.title }}</span>
                        <router-link 
                            v-for="f in flow.forms" 
                            :key="f.code" 
                            :to="f.code === '1100' ? '/form/1100' : `/form/dynamic/${f.code}`"
                            class="nav-item"
                            :class="{ active: route.path.includes(f.code) }"
                        >
                            <span class="nav-code">{{ f.code }}</span>
                            <span class="nav-text">{{ f.name }}</span>
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Profile / System Bottom Box -->
            <div class="sidebar-footer">
                <div class="user-profile-card">
                    <div class="user-avatar">{{ (user.name || 'A')[0].toUpperCase() }}</div>
                    <div class="user-meta">
                        <span class="user-display-name">{{ user.name || 'User' }}</span>
                        <span class="user-role-badge">{{ userRole }}</span>
                    </div>
                </div>
                <button class="logout-action-btn" @click="logout">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </span>
                    <span>Logout</span>
                </button>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <div class="main-wrapper">
            <!-- Top Navigation Bar -->
            <header class="top-header">
                <div class="breadcrumb-area">
                    <span class="bc-item">KAP MGN</span>
                    <span class="bc-separator">/</span>
                    <span class="bc-item current">{{ selectedCompany ? selectedCompany.name : 'Dashboard' }}</span>
                </div>

                <div class="header-right-tools">
                    <div class="date-chip">
                        <span class="date-icon">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </span>
                        <span>{{ new Date().toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
                    </div>
                    <button v-if="userRole === 'Admin'" class="quick-header-btn" @click="router.push('/clients')">
                        <span>+ Kelola Client</span>
                    </button>
                </div>
            </header>

            <!-- Page Content -->
            <main class="page-content">
                <!-- Company Selection Overlay if no client selected -->
                <div v-if="!selectedCompany && route.path !== '/clients' && route.path !== '/admin/folders'" class="selection-overlay">
                    <div class="card selection-box">
                        <div class="selection-icon">
                            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="color: var(--orange-600);">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
                                <line x1="9" y1="22" x2="9" y2="16"></line>
                                <line x1="9" y1="16" x2="15" y2="16"></line>
                                <line x1="15" y1="16" x2="15" y2="22"></line>
                                <line x1="9" y1="12" x2="15" y2="12"></line>
                                <line x1="9" y1="8" x2="15" y2="8"></line>
                            </svg>
                        </div>
                        <h2>Pilih Klien Perikatan</h2>
                        <p class="selection-desc">Silakan pilih perusahaan untuk membuka seluruh form &amp; kertas kerja audit.</p>
                        
                        <p v-if="loadingClients" class="hint">Memuat daftar klien...</p>
                        <p v-else-if="companies.length === 0" class="hint">
                            Belum ada perusahaan. 
                            <span v-if="userRole === 'Admin'"><router-link to="/clients">Tambah di sini</router-link></span>
                            <span v-else>Hubungi Admin untuk mendaftarkan klien.</span>
                        </p>
                        <div v-else class="company-grid">
                            <div 
                                v-for="company in companies" 
                                :key="company.id" 
                                class="company-card-select"
                                @click="selectCompany(company)"
                            >
                                <div class="comp-icon-box">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <div class="comp-details">
                                    <strong>{{ company.name }}</strong>
                                    <span :class="['badge-client-type', company.client_type === 'TBK' ? 'badge-client-type--tbk' : 'badge-client-type--non-tbk']">
                                        {{ company.client_type }}
                                    </span>
                                </div>
                                <span class="arrow-select">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <slot v-else />
            </main>
        </div>
    </div>
</template>

<style scoped>
.app-layout {
    display: flex;
    height: 100%;
    width: 100%;
    overflow: hidden;
    background-color: var(--paper);
    font-family: var(--font-body);
}

/* Sidebar Styles */
.sidebar {
    width: 260px;
    background: linear-gradient(160deg, var(--ink-900) 0%, #1b2836 100%); /* konsisten sama panel gelap di Login & Dashboard */
    border-right: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 0; /* jaga-jaga: cegah kebentur rule global generik .sidebar dari style.css */
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.brand-header {
    padding: 1.1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.brand-logo-bg {
    width: 38px;
    height: 38px;
    background: #FFFFFF;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3px;
    flex-shrink: 0;
}

.brand-img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.brand-titles {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    min-width: 0;
}

.brand-name {
    font-weight: 700;
    font-size: 0.86rem;
    color: #FFFFFF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.25;
}

.brand-sub {
    font-size: 0.64rem;
    color: rgba(255, 255, 255, 0.55);
    line-height: 1.35;
    /* Sengaja dibiarkan wrap ke 2 baris (bukan ellipsis) supaya teksnya keliatan penuh */
    white-space: normal;
    word-break: break-word;
}

.sidebar-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 1.1rem 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    scroll-padding-top: 0.75rem;
    /* Scrollbar tipis nge-blend sama sidebar gelap, ganti default abu-abu gemuk bawaan browser */
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.18) transparent;
}

.sidebar-scroll::-webkit-scrollbar {
    width: 6px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.18);
    border-radius: 999px;
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.32);
}

.nav-section {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.nav-section-title {
    font-size: 0.68rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.4);
    letter-spacing: 0.08em;
    margin-bottom: 0.35rem;
    padding-left: 0.5rem;
}

.nav-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.65rem 0.75rem;
    border-radius: 8px;
    color: rgba(255, 255, 255, 0.65);
    text-decoration: none;
    font-size: 0.88rem;
    font-weight: 500;
    transition: all 0.15s ease;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #FFFFFF;
}

.nav-item.active {
    background: var(--orange-600); /* aksen brand — dipakai buat state aktif, bukan warna latar biasa (lihat design.md bag. 5) */
    color: #FFFFFF;
    font-weight: 600;
}

.nav-item.active .nav-code {
    background: rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
}

.nav-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-code {
    flex-shrink: 0;
    margin-top: 0.1rem;
    font-size: 0.75rem;
    font-weight: 700;
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.55);
    padding: 0.15rem 0.4rem;
    border-radius: 4px;
}

.nav-text {
    white-space: normal;
    line-height: 1.3;
    word-break: break-word;
}

/* Active Client Box in Sidebar */
.client-active-box {
    background: color-mix(in srgb, var(--orange-600) 16%, transparent);
    border: 1px solid color-mix(in srgb, var(--orange-600) 35%, transparent);
    border-radius: 10px;
    padding: 0.85rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.client-info {
    display: flex;
    flex-direction: column;
}

.client-label {
    font-size: 0.65rem;
    font-weight: 800;
    color: color-mix(in srgb, var(--orange-600) 55%, white);
    letter-spacing: 0.05em;
}

.client-name-text {
    font-size: 0.85rem;
    color: #FFFFFF;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.btn-change {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.35rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.btn-change:hover {
    background: rgba(255, 255, 255, 0.1);
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-profile-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 8px;
}

.user-avatar {
    width: 34px;
    height: 34px;
    background: var(--orange-600);
    color: #FFFFFF;
    font-weight: 700;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.user-meta {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.user-display-name {
    font-size: 0.85rem;
    font-weight: 600;
    color: #FFFFFF;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role-badge {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.6);
}

.logout-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.65);
    font-size: 0.85rem;
    font-weight: 500;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s ease;
}

.logout-action-btn:hover {
    background: color-mix(in srgb, var(--orange-600) 16%, transparent);
    color: color-mix(in srgb, var(--orange-600) 55%, white);
}

/* Main Wrapper */
.main-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.top-header {
    height: 60px;
    background: #FFFFFF;
    border-bottom: 1px solid var(--surface-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
    flex-shrink: 0;
}

.breadcrumb-area {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.88rem;
}

.bc-item {
    color: #64748B;
}

.bc-item.current {
    color: var(--ink-900);
    font-weight: 600;
}

.bc-separator {
    color: #CBD5E1;
}

.header-right-tools {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.date-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--surface);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    color: #475569;
}

.quick-header-btn {
    background: var(--orange-600);
    color: white;
    border: none;
    padding: 0.45rem 0.9rem;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
}

.quick-header-btn:hover {
    background: var(--orange-600-hover);
}

.page-content {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.page-content::-webkit-scrollbar {
    width: 8px;
}

.page-content::-webkit-scrollbar-track {
    background: transparent;
}

.page-content::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.page-content::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Company Selection Overlay */
.selection-overlay {
    position: fixed;
    inset: 0;
    background: color-mix(in srgb, var(--ink-900) 65%, transparent);
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 2rem;
}

.selection-box {
    width: min(560px, 100%);
    background: rgba(255, 255, 255, 0.94);
    border-radius: 18px;
    padding: 2.5rem;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.7);
}

.selection-icon {
    margin-bottom: 1.25rem;
    display: flex;
    justify-content: center;
}

.selection-box h2 {
    font-size: 1.35rem;
    color: var(--ink-900);
    margin: 0 0 0.5rem;
}

.selection-desc {
    font-size: 0.88rem;
    color: #64748B;
    margin-bottom: 1.5rem;
}

.company-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
}

.company-card-select {
    min-height: 92px;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--surface-border);
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
    pointer-events: auto;
    background: var(--surface);
}

.company-card-select:hover {
    border-color: var(--orange-600);
    background: #FFFFFF;
    box-shadow: 0 8px 20px rgba(217, 107, 0, 0.08);
    transform: translateY(-1px);
}

.comp-icon-box {
    width: 40px;
    height: 40px;
    background: var(--surface);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748B;
    font-size: 1.2rem;
}

.comp-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
    min-width: 0;
}

.comp-details strong {
    font-size: 0.92rem;
    color: var(--ink-900);
    line-height: 1.25;
}

.arrow-select {
    color: #CBD5E1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.company-card-select:hover .arrow-select {
    color: var(--orange-600);
}
</style>
