import { createRouter, createWebHistory } from 'vue-router';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import Form1100 from './components/Form1100.vue';
import ClientManagement from './components/ClientManagement.vue';
import EngagementManagement from './components/EngagementManagement.vue';
import UserManagement from './components/UserManagement.vue';
import AdminFolderDrive from './components/AdminFolderDrive.vue';
import DynamicForm from './components/DynamicForm.vue';

const routes = [
    { path: '/', component: Login },
    { path: '/dashboard', component: Dashboard },
    { path: '/clients', component: ClientManagement },
    { path: '/engagements', component: EngagementManagement },
    { path: '/users', component: UserManagement },
    { path: '/admin/folders', component: AdminFolderDrive },
    { path: '/form/1100', component: Form1100 },
    // Form generic — baca struktur section/field dari database (audit_forms API),
    // dipakai buat form yang belum punya halaman hardcoded sendiri.
    { path: '/form/dynamic/:code', component: DynamicForm },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

function currentRoleName(): string | null {
    try {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        if (typeof user.role === 'object' && user.role !== null) return user.role.name ?? null;
        return user.role ?? null;
    } catch {
        return null;
    }
}

router.beforeEach((to, _from, next) => {
    const token = localStorage.getItem('token');

    // Belum login: cuma boleh ke halaman Login.
    if (to.path !== '/' && !token) {
        next('/');
        return;
    }

    // Udah login: kalau navigasi langsung (ketik URL / bookmark) ke '/',
    // arahkan ke dashboard. Tapi kalau user klik tombol back dari dashboard,
    // biarkan lewat supaya nggak stuck redirect-loop.
    if (to.path === '/' && token) {
        // Cek: kalau datangnya dari '/dashboard' (back button), jangan redirect lagi.
        if (_from.path === '/dashboard') {
            next();
        } else {
            next('/dashboard');
        }
        return;
    }

    // Folder Drive khusus role Admin.
    if (to.path === '/admin/folders' && currentRoleName() !== 'Admin') {
        next('/dashboard');
        return;
    }

    // Form kertas kerja wajib udah pilih klien dulu.
    const selectedCompany = localStorage.getItem('selectedCompany');
    if (to.path.startsWith('/form') && !selectedCompany) {
        next('/dashboard');
        return;
    }

    next();
});

export default router;
