<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import DashboardLayout from './DashboardLayout.vue';

const router = useRouter();
const selectedCompany = ref(JSON.parse(localStorage.getItem('selectedCompany') || 'null'));
const user = JSON.parse(localStorage.getItem('user') || '{}');
const userRole = typeof user.role === 'object' && user.role !== null ? user.role.name : (user.role || 'Auditor');

// Data ringkasan dashboard
const stats = ref({
    totalForms: 24,
    pendingReview: 3,
    approvedForms: 18,
    activeClient: selectedCompany.value ? selectedCompany.value.name : 'Belum Dipilih'
});

// Progress audit per modul
const moduleProgress = ref([
    { code: '1000', name: 'Risk Assessment', percent: 75, color: '#DC2626' },
    { code: '2000', name: 'Risk Response', percent: 40, color: '#F59E0B' },
    { code: '3000', name: 'Audit Evidence', percent: 25, color: '#2563EB' },
    { code: '4000', name: 'Representation & Consultation', percent: 10, color: '#9333EA' },
    { code: '5000', name: 'Reporting', percent: 0, color: '#64748B' },
]);

// Tabel Form / Aktivitas Terbaru
const recentActivities = ref([
    { code: '1100', name: 'Memo Penerimaan & Keberlanjutan Klien', module: '1000 Risk Assessment', user: 'Senior Auditor', status: 'pending_review', time: '10 Menit lalu' },
    { code: '1110', name: 'Survey Klien', module: '1000 Risk Assessment', user: 'Junior Auditor', status: 'approved', time: '1 Jam lalu' },
    { code: '1130', name: 'Evaluasi Independensi', module: '1000 Risk Assessment', user: 'Junior Auditor', status: 'draft', time: 'Yesterday 16:45' },
    { code: '2100', name: 'Strategi Audit', module: '2000 Risk Response', user: 'Manager Audit', status: 'reviewed', time: '29 Aug 2026' },
    { code: '3100', name: 'Balance Sheet - Kas & Bank', module: '3000 Audit Evidence', user: 'Senior Auditor', status: 'approved', time: '28 Aug 2026' }
]);

function openForm(code: string) {
    if (code === '1100') {
        router.push('/form/1100');
    } else {
        router.push(`/form/dynamic/${code}`);
    }
}

function statusBadgeClass(status: string) {
    switch (status) {
        case 'approved': return 'badge-approved';
        case 'pending_review': return 'badge-pending';
        case 'reviewed': return 'badge-reviewed';
        case 'revision_required': return 'badge-revision';
        default: return 'badge-draft';
    }
}

function statusLabel(status: string) {
    switch (status) {
        case 'approved': return 'Approved';
        case 'pending_review': return 'Pending Review';
        case 'reviewed': return 'Reviewed';
        case 'revision_required': return 'Revisi';
        default: return 'Draft';
    }
}

onMounted(() => {
    selectedCompany.value = JSON.parse(localStorage.getItem('selectedCompany') || 'null');
});
</script>

<template>
    <DashboardLayout>
        <div class="dashboard-main">
            <!-- Top Alert / Notice Banner -->
            <div v-if="stats.pendingReview > 0" class="alert-banner">
                <div class="alert-left">
                    <span class="alert-icon">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                        </svg>
                    </span>
                    <span>Ada <strong>{{ stats.pendingReview }} form audit</strong> menunggu review &amp; approval.</span>
                </div>
                <button class="alert-action" @click="router.push('/admin/folders')">Lihat Semua &rarr;</button>
            </div>

            <!-- Page Header Title -->
            <div class="dashboard-head-bar">
                <div>
                    <h1 class="page-title">Dashboard Audit</h1>
                    <p class="page-sub">Selamat datang kembali, <strong>{{ user.name || 'Auditor' }}</strong> ({{ userRole }})</p>
                </div>
                <button v-if="userRole === 'Admin'" class="btn-primary-action" @click="router.push('/clients')">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:0.4rem; vertical-align:text-bottom;">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Kelola Klien
                </button>
            </div>

            <!-- 4 Top Metric Cards -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon-wrapper red">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                            <polyline points="10 9 9 9 8 9"></polyline>
                        </svg>
                    </div>
                    <div class="metric-data">
                        <span class="metric-val">{{ stats.totalForms }}</span>
                        <span class="metric-lbl">Total Form Audit</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon-wrapper orange">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <div class="metric-data">
                        <span class="metric-val">{{ stats.pendingReview }}</span>
                        <span class="metric-lbl">Menunggu Review</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon-wrapper green">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                    </div>
                    <div class="metric-data">
                        <span class="metric-val">{{ stats.approvedForms }}</span>
                        <span class="metric-lbl">Form Approved</span>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon-wrapper blue">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div class="metric-data">
                        <span class="metric-val-sm">{{ stats.activeClient }}</span>
                        <span class="metric-lbl">Klien Perikatan Aktif</span>
                    </div>
                </div>
            </div>

            <!-- Middle Section: Progress + Quick Actions -->
            <div class="mid-grid">
                <!-- Progress Audit Modul -->
                <div class="card-box progress-card">
                    <div class="card-head">
                        <h3>Progress Kertas Kerja Per Fase</h3>
                        <span class="chip-info">Klien: {{ selectedCompany ? selectedCompany.name : 'PT IAS Tbk' }}</span>
                    </div>

                    <div class="progress-list">
                        <div v-for="m in moduleProgress" :key="m.code" class="prog-item">
                            <div class="prog-info">
                                <span class="prog-name"><strong>{{ m.code }}</strong> {{ m.name }}</span>
                                <span class="prog-val">{{ m.percent }}%</span>
                            </div>
                            <div class="prog-bar-bg">
                                <div class="prog-bar-fill" :style="{ width: m.percent + '%', backgroundColor: m.color }"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Dark Info Card + Quick Actions -->
                <div class="right-stack">
                    <div class="dark-brand-card">
                        <div class="dbc-header">
                            <div class="dbc-logo">
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                                    <path d="M2 17l10 5 10-5"></path>
                                    <path d="M2 12l10 5 10-5"></path>
                                </svg>
                            </div>
                            <div>
                                <h4>KAP MGN &amp; Rekan</h4>
                                <small>Jakarta &middot; Assurance</small>
                            </div>
                        </div>
                        <div class="dbc-stats">
                            <div class="dbc-stat-item">
                                <strong>{{ stats.pendingReview }}</strong>
                                <span>Pending</span>
                            </div>
                            <div class="dbc-stat-item">
                                <strong>{{ stats.approvedForms }}</strong>
                                <span>Approved</span>
                            </div>
                            <div class="dbc-stat-item">
                                <strong>Rp0</strong>
                                <span>Revenue</span>
                            </div>
                        </div>
                        <div class="dbc-status-line">
                            <span class="stack-text">Stack <strong>Laravel + Vue 3</strong></span>
                            <span class="live-badge"><span class="live-dot"></span> Online</span>
                        </div>
                    </div>

                    <div class="card-box quick-actions-card">
                        <span class="action-title-lbl">AKSI CEPAT</span>
                        <div class="action-buttons-list">
                            <button class="action-row-btn" @click="openForm('1100')">
                                <div class="arb-icon-bg red">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                        <line x1="16" y1="13" x2="8" y2="13"></line>
                                        <line x1="16" y1="17" x2="8" y2="17"></line>
                                        <polyline points="10 9 9 9 8 9"></polyline>
                                    </svg>
                                </div>
                                <div class="arb-text">
                                    <strong>Form 1100 Memo Penerimaan</strong>
                                    <small>Input evaluasi keberlanjutan klien</small>
                                </div>
                                <span class="arb-arrow">&rsaquo;</span>
                            </button>

                            <button class="action-row-btn" @click="openForm('1110')">
                                <div class="arb-icon-bg orange">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                </div>
                                <div class="arb-text">
                                    <strong>Form 1110 Survey Klien</strong>
                                    <small>Isi kuisioner survei entitas</small>
                                </div>
                                <span class="arb-arrow">&rsaquo;</span>
                            </button>

                            <button v-if="userRole === 'Admin'" class="action-row-btn" @click="router.push('/admin/folders')">
                                <div class="arb-icon-bg green">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </div>
                                <div class="arb-text">
                                    <strong>Folder Drive Admin</strong>
                                    <small>Pantau seluruh file yang disubmit</small>
                                </div>
                                <span class="arb-arrow">&rsaquo;</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Table Section -->
            <div class="card-box table-section-card">
                <div class="card-head">
                    <div>
                        <h3>Aktivitas &amp; Kertas Kerja Terbaru</h3>
                        <p class="sub-head-desc">Daftar pembaruan form audit dari tim perikatan</p>
                    </div>
                    <button class="btn-text-link" @click="router.push('/admin/folders')">Lihat Semua</button>
                </div>

                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>KODE &amp; FORM</th>
                                <th>MODUL AUDIT</th>
                                <th>DISUBMIT OLEH</th>
                                <th>STATUS</th>
                                <th>WAKTU UPDATE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in recentActivities" :key="row.code">
                                <td>
                                    <button class="form-title-btn" @click="openForm(row.code)">
                                        <span class="code-badge">{{ row.code }}</span>
                                        <strong>{{ row.name }}</strong>
                                    </button>
                                </td>
                                <td class="text-muted">{{ row.module }}</td>
                                <td>{{ row.user }}</td>
                                <td>
                                    <span :class="['status-pill', statusBadgeClass(row.status)]">
                                        {{ statusLabel(row.status) }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ row.time }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.dashboard-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Alert Banner */
.alert-banner {
    background: #FFFBEB;
    border: 1px solid #FCD34D;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #92400E;
    font-size: 0.88rem;
}

.alert-left {
    display: flex;
    align-items: center;
    gap: 0.6rem;
}

.alert-action {
    background: transparent;
    border: none;
    color: #D97706;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.85rem;
}

.alert-action:hover {
    text-decoration: underline;
}

/* Head Bar */
.dashboard-head-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.2rem;
    letter-spacing: -0.02em;
}

.page-sub {
    margin: 0;
    font-size: 0.88rem;
    color: #64748B;
}

.btn-primary-action {
    background: #DC2626;
    color: #FFFFFF;
    border: none;
    padding: 0.6rem 1.1rem;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.88rem;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
    transition: all 0.15s ease;
}

.btn-primary-action:hover {
    background: #B91C1C;
    box-shadow: 0 6px 16px rgba(220, 38, 38, 0.25);
    transform: translateY(-1px);
}

/* Metrics 4 Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.25rem;
}

.metric-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.metric-icon-wrapper {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.metric-icon-wrapper.red { background: #FEF2F2; color: #DC2626; }
.metric-icon-wrapper.orange { background: #FFF7ED; color: #EA580C; }
.metric-icon-wrapper.green { background: #F0FDF4; color: #16A34A; }
.metric-icon-wrapper.blue { background: #EFF6FF; color: #2563EB; }

.metric-data {
    display: flex;
    flex-direction: column;
}

.metric-val {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0F172A;
    line-height: 1.2;
}

.metric-val-sm {
    font-size: 0.95rem;
    font-weight: 700;
    color: #0F172A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
}

.metric-lbl {
    font-size: 0.75rem;
    color: #64748B;
    margin-top: 0.1rem;
}

/* Mid Grid */
.mid-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 1.25rem;
}

.card-box {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 1.5rem;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.card-head h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
}

.chip-info {
    font-size: 0.72rem;
    background: #F1F5F9;
    color: #475569;
    padding: 0.25rem 0.6rem;
    border-radius: 20px;
    font-weight: 600;
}

.progress-list {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.prog-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    margin-bottom: 0.35rem;
    color: #334155;
}

.prog-val {
    font-weight: 700;
    color: #0F172A;
}

.prog-bar-bg {
    height: 8px;
    background: #F1F5F9;
    border-radius: 10px;
    overflow: hidden;
}

.prog-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.4s ease;
}

/* Right Stack */
.right-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.dark-brand-card {
    background: #0F172A;
    color: #FFFFFF;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
}

.dbc-header {
    display: flex;
    align-items: center;
    gap: 0.85rem;
}

.dbc-logo {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dbc-header h4 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    color: white;
}

.dbc-header small {
    color: #94A3B8;
    font-size: 0.72rem;
}

.dbc-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
}

.dbc-stat-item strong {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    color: white;
}

.dbc-stat-item span {
    font-size: 0.65rem;
    color: #94A3B8;
}

.dbc-status-line {
    font-size: 0.75rem;
    color: #94A3B8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stack-text strong {
    color: #FFFFFF;
}

.live-badge {
    background: rgba(16, 185, 129, 0.15);
    color: #34D399;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    display: flex;
    align-items: center;
    gap: 0.35rem;
    font-weight: 600;
}

.live-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #34D399;
}

.action-title-lbl {
    font-size: 0.68rem;
    font-weight: 700;
    color: #64748B;
    letter-spacing: 0.05em;
    margin-bottom: 0.85rem;
    display: block;
    text-transform: uppercase;
}

.action-buttons-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

.action-row-btn {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 0.75rem;
    border: 1px solid #FFFFFF;
    border-radius: 8px;
    background: #FFFFFF;
    text-align: left;
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-row-btn:hover {
    background: #F8FAFC;
    border-color: #E2E8F0;
}

.arb-icon-bg {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.arb-icon-bg.red { background: #FEF2F2; color: #DC2626; }
.arb-icon-bg.orange { background: #FFF7ED; color: #EA580C; }
.arb-icon-bg.green { background: #F0FDF4; color: #16A34A; }

.arb-text {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.arb-text strong {
    font-size: 0.82rem;
    color: #0F172A;
}

.arb-text small {
    font-size: 0.72rem;
    color: #64748B;
}

.arb-arrow {
    color: #CBD5E1;
    font-size: 1.2rem;
}

.action-row-btn:hover .arb-arrow {
    color: #0F172A;
}

/* Table Section */
.sub-head-desc {
    margin: 0.2rem 0 0;
    font-size: 0.78rem;
    color: #64748B;
}

.btn-text-link {
    background: transparent;
    border: none;
    color: #DC2626;
    font-weight: 600;
    font-size: 0.82rem;
    cursor: pointer;
}

.table-responsive {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.modern-table th {
    text-align: left;
    padding: 0.75rem 1rem;
    color: #64748B;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #E2E8F0;
    background: #F8FAFC;
}

.modern-table td {
    padding: 0.9rem 1rem;
    border-bottom: 1px solid #F1F5F9;
    color: #1E293B;
}

.form-title-btn {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    text-align: left;
}

.form-title-btn strong {
    color: #0F172A;
    font-weight: 600;
}

.form-title-btn:hover strong {
    color: #DC2626;
    text-decoration: underline;
}

.code-badge {
    background: #F1F5F9;
    color: #475569;
    font-weight: 700;
    font-size: 0.72rem;
    padding: 0.2rem 0.45rem;
    border-radius: 4px;
}

.form-title-btn:hover .code-badge {
    background: #FEF2F2;
    color: #DC2626;
}

.text-muted {
    color: #64748B;
}

.status-pill {
    display: inline-block;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}

.badge-approved { background: #DCFCE7; color: #15803D; }
.badge-pending { background: #FEF3C7; color: #B45309; }
.badge-reviewed { background: #DBEAFE; color: #1D4ED8; }
.badge-revision { background: #FEE2E2; color: #B91C1C; }
.badge-draft { background: #F1F5F9; color: #475569; }

@media (max-width: 1100px) {
    .metrics-grid { grid-template-columns: repeat(2, 1fr); }
    .mid-grid { grid-template-columns: 1fr; }
}
</style>
