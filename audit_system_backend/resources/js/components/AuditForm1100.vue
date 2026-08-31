<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

type FormState = {
    clientName: string;
    engagementYear: string;
    auditorName: string;
    continuanceDecision: string;
    riskSummary: string;
    notes: string;
};

const form = reactive<FormState>({
    clientName: 'PT Indo American Seafoods Tbk',
    engagementYear: '2024',
    auditorName: 'Senior Auditor',
    continuanceDecision: 'Continue',
    riskSummary: '',
    notes: '',
});

const isSaved = ref(false);
const payload = computed(() => JSON.stringify(form, null, 2));

function saveDraft(): void {
    isSaved.value = true;
    window.setTimeout(() => {
        isSaved.value = false;
    }, 2000);
}
</script>

<template>
    <main class="min-h-screen bg-slate-100 p-6 text-slate-900">
        <div class="mx-auto max-w-5xl space-y-6">
            <header class="rounded-2xl bg-white p-6 shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-600">
                    Form 1100
                </p>
                <h1 class="mt-2 text-3xl font-bold">Memo Penerimaan &amp; Keberlanjutan Klien</h1>
                <p class="mt-2 text-sm text-slate-600">
                    Prototype Vue + TypeScript untuk input form audit 1100.
                </p>
            </header>

            <section class="grid gap-6 lg:grid-cols-3">
                <form class="space-y-6 rounded-2xl bg-white p-6 shadow-sm lg:col-span-2" @submit.prevent="saveDraft">
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2">
                            <span class="text-sm font-medium">Nama Klien</span>
                            <input v-model="form.clientName" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500" type="text">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium">Tahun Engagement</span>
                            <input v-model="form.engagementYear" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500" type="text">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium">Nama Auditor</span>
                            <input v-model="form.auditorName" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500" type="text">
                        </label>
                        <label class="space-y-2">
                            <span class="text-sm font-medium">Keputusan Kelanjutan</span>
                            <select v-model="form.continuanceDecision" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500">
                                <option>Continue</option>
                                <option>Reject</option>
                                <option>Need Review</option>
                            </select>
                        </label>
                    </div>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Ringkasan Risiko</span>
                        <textarea v-model="form.riskSummary" rows="5" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500" />
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium">Catatan</span>
                        <textarea v-model="form.notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-indigo-500" />
                    </label>

                    <div class="flex items-center gap-3">
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white" type="submit">
                            Save Draft
                        </button>
                        <span v-if="isSaved" class="text-sm text-emerald-600">Tersimpan</span>
                    </div>
                </form>

                <aside class="space-y-6 rounded-2xl bg-white p-6 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold">Preview Data</h2>
                        <pre class="mt-3 overflow-auto rounded-xl bg-slate-950 p-4 text-xs text-slate-100">{{ payload }}</pre>
                    </div>
                    <div class="rounded-xl bg-indigo-50 p-4 text-sm text-indigo-900">
                        Form ini masih prototype. Berikutnya bisa disambungkan ke API `audit_forms` dan `audit_form_responses`.
                    </div>
                </aside>
            </section>
        </div>
    </main>
</template>
