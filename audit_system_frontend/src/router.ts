import { createRouter, createWebHistory } from 'vue-router';
import Login from './components/Login.vue';
import Dashboard from './components/Dashboard.vue';
import Form1100 from './components/Form1100.vue';
import ClientManagement from './components/ClientManagement.vue';
import AdminFolderDrive from './components/AdminFolderDrive.vue';
import DynamicForm from './components/DynamicForm.vue';

const routes = [
    { path: '/', component: Login },
    { path: '/dashboard', component: Dashboard },
    { path: '/clients', component: ClientManagement },
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

router.beforeEach((to, _from, next) => {
    const selectedCompany = localStorage.getItem('selectedCompany');
    if (to.path.startsWith('/form') && !selectedCompany) {
        next('/dashboard');
    } else {
        next();
    }
});

export default router;

