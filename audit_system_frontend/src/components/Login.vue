<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const email = ref('admin@kapmgn.test');
const password = ref('password123');
const loading = ref(false);
const errorMessage = ref('');
const showPassword = ref(false);

async function login(): Promise<void> {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch('/api/v1/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ email: email.value, password: password.value }),
        });

        const text = await response.text();
        const data = text ? JSON.parse(text) : {};
        if (!response.ok) throw new Error(data.message ?? `Login gagal (${response.status})`);

        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        router.push('/dashboard');
    } catch (error) {
        errorMessage.value = error instanceof Error ? error.message : 'Login gagal';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="login-viewport">
        <!-- Panel Kiri: Form Login Minimalis Putih -->
        <section class="left-panel">
            <div class="form-container">
                <!-- Icon Brand Merah -->
                <div class="brand-badge">
                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>

                <h1 class="brand-title">KAP MGN &amp; Rekan</h1>
                <p class="brand-subtitle">Selamat datang kembali &mdash; silakan masuk</p>

                <form class="login-form" @submit.prevent="login">
                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <input
                            id="email"
                            v-model="email"
                            type="email"
                            placeholder="admin@kapmgn.test"
                            autocomplete="username"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <div class="password-wrapper">
                            <input
                                id="password"
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                required
                            />
                            <button
                                type="button"
                                class="btn-toggle-pw"
                                @click="showPassword = !showPassword"
                                :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            >
                                <svg v-if="!showPassword" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                    <line x1="2" y1="2" x2="22" y2="22"></line>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" :disabled="loading">
                        {{ loading ? 'Memproses...' : 'Masuk Sekarang' }}
                    </button>

                    <p v-if="errorMessage" class="error-msg">{{ errorMessage }}</p>
                </form>
            </div>
        </section>

        <!-- Panel Kanan: Gradient Oranye-Merah dengan Ornamen & Glass Cards -->
        <aside class="right-panel">
            <!-- Background Ornaments (Big Blur Circles) -->
            <div class="bg-orb orb-1"></div>
            <div class="bg-orb orb-2"></div>
            <div class="bg-orb orb-3"></div>

            <div class="right-content">
                <span class="eyebrow">AUDIT MANAGEMENT SYSTEM</span>
                <h2 class="hero-heading">Kelola audit klien Anda dengan presisi.</h2>
                <p class="hero-desc">
                    Dashboard terpadu untuk kertas kerja, workflow review &amp; approval berjenjang &mdash; semua dalam satu sistem terintegrasi.
                </p>

                <div class="glass-cards-list">
                    <!-- Glass Card 1 -->
                    <div class="glass-card">
                        <div class="glass-icon-box">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <div class="glass-text">
                            <strong>Kelola Form Audit</strong>
                            <span>Pantau &amp; isi seluruh form dari 1000 hingga 5000</span>
                        </div>
                    </div>

                    <!-- Glass Card 2 -->
                    <div class="glass-card">
                        <div class="glass-icon-box">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <div class="glass-text">
                            <strong>Review &amp; Approval Bertingkat</strong>
                            <span>Junior submit &rarr; Manager review &rarr; Partner approve</span>
                        </div>
                    </div>

                    <!-- Glass Card 3 -->
                    <div class="glass-card">
                        <div class="glass-icon-box">
                            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                                <line x1="8" y1="21" x2="16" y2="21"></line>
                                <line x1="12" y1="17" x2="12" y2="21"></line>
                            </svg>
                        </div>
                        <div class="glass-text">
                            <strong>Manajemen Klien &amp; Folder</strong>
                            <span>Akses mudah untuk TBK dan Non-TBK secara fleksibel</span>
                        </div>
                    </div>
                </div>

                <div class="panel-footer">
                    <span>Powered by <strong>Laravel + Vue 3</strong></span>
                    <span class="dot-separator">&bull;</span>
                    <span class="system-status">
                        <span class="pulse-dot"></span> Sistem Online
                    </span>
                </div>
            </div>
        </aside>
    </main>
</template>

<style scoped>
/* Fullscreen Reset */
.login-viewport {
    display: flex;
    width: 100vw;
    height: 100vh;
    overflow: hidden;
    background: #FFFFFF;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* ---------------- LEFT PANEL ---------------- */
.left-panel {
    flex: 0 0 45%;
    max-width: 540px;
    min-width: 380px;
    height: 100%;
    background: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 4rem;
    box-sizing: border-box;
    overflow-y: auto;
}

.form-container {
    width: 100%;
    max-width: 380px;
    display: flex;
    flex-direction: column;
}

.brand-badge {
    width: 48px;
    height: 48px;
    background: #DC2626;
    color: #FFFFFF;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.75rem;
    box-shadow: 0 8px 16px rgba(220, 38, 38, 0.25);
}

.brand-title {
    font-size: 1.65rem;
    font-weight: 800;
    color: #0F172A;
    margin: 0 0 0.4rem;
    letter-spacing: -0.02em;
}

.brand-subtitle {
    font-size: 0.92rem;
    color: #64748B;
    margin: 0 0 2rem;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.45rem;
}

.form-group label {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #475569;
}

.form-group input {
    width: 100%;
    padding: 0.85rem 1rem;
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    font-size: 0.95rem;
    color: #0F172A;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-group input:focus {
    background: #FFFFFF;
    border-color: #DC2626;
    box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.08);
}

.password-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.password-wrapper input {
    padding-right: 2.75rem;
}

.btn-toggle-pw {
    position: absolute;
    right: 0.75rem;
    background: transparent;
    border: none;
    color: #94A3B8;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
}

.btn-toggle-pw:hover {
    color: #475569;
}

.btn-submit {
    width: 100%;
    padding: 0.9rem 1.25rem;
    background: #DC2626;
    color: #FFFFFF;
    border: none;
    border-radius: 10px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    margin-top: 0.5rem;
    box-shadow: 0 8px 20px rgba(220, 38, 38, 0.28);
    transition: all 0.2s ease;
}

.btn-submit:hover {
    background: #B91C1C;
    box-shadow: 0 10px 24px rgba(220, 38, 38, 0.35);
    transform: translateY(-1px);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:disabled {
    background: #FDA4AF;
    cursor: not-allowed;
    box-shadow: none;
}

.error-msg {
    margin: 0;
    font-size: 0.85rem;
    color: #DC2626;
    font-weight: 500;
    text-align: center;
}

/* ---------------- RIGHT PANEL ---------------- */
.right-panel {
    flex: 1;
    height: 100%;
    background: linear-gradient(135deg, #E03E00 0%, #D82800 50%, #C41F00 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 4rem 5rem;
    box-sizing: border-box;
    color: #FFFFFF;
}

/* Abstract Background Glow Orbs */
.bg-orb {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}

.orb-1 {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255, 120, 70, 0.4) 0%, rgba(255, 120, 70, 0) 70%);
    top: -150px;
    right: -100px;
}

.orb-2 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(255, 60, 0, 0.45) 0%, rgba(255, 60, 0, 0) 70%);
    bottom: -100px;
    left: 50px;
}

.orb-3 {
    width: 350px;
    height: 350px;
    border: 70px solid rgba(255, 255, 255, 0.04);
    border-radius: 50%;
    top: 40%;
    left: -100px;
}

.right-content {
    position: relative;
    z-index: 2;
    max-width: 560px;
    display: flex;
    flex-direction: column;
}

.eyebrow {
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.15em;
    color: rgba(255, 255, 255, 0.75);
    margin-bottom: 1.25rem;
    text-transform: uppercase;
}

.hero-heading {
    font-size: 2.8rem;
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 1.25rem;
    letter-spacing: -0.03em;
    color: #FFFFFF;
}

.hero-desc {
    font-size: 1.05rem;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.85);
    margin: 0 0 2.5rem;
}

/* Glassmorphism Cards */
.glass-cards-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-bottom: 3rem;
}

.glass-card {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 1.1rem 1.4rem;
    transition: all 0.25s ease;
}

.glass-card:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.glass-icon-box {
    width: 44px;
    height: 44px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    flex-shrink: 0;
}

.glass-text {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.glass-text strong {
    font-size: 0.98rem;
    font-weight: 700;
    color: #FFFFFF;
}

.glass-text span {
    font-size: 0.82rem;
    color: rgba(255, 255, 255, 0.8);
}

/* Footer Info */
.panel-footer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.75);
}

.panel-footer strong {
    color: #FFFFFF;
}

.dot-separator {
    opacity: 0.5;
}

.system-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: #A7F3D0;
    font-weight: 600;
}

.pulse-dot {
    width: 8px;
    height: 8px;
    background: #10B981;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.35);
}

/* Responsive */
@media (max-width: 1024px) {
    .right-panel {
        display: none;
    }
    .left-panel {
        flex: 1;
        max-width: 100%;
    }
}
</style>
