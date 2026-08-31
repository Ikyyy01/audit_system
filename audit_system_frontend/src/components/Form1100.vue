<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DashboardLayout from './DashboardLayout.vue';

// Custom directive for auto-resizing textarea on mount
const vMountedAutoResize = {
    mounted(el: HTMLTextAreaElement) {
        el.style.height = 'auto';
        el.style.height = el.scrollHeight + 'px';
    }
};

const selectedCompanyString = localStorage.getItem('selectedCompany');
const selectedCompanyObj = selectedCompanyString ? JSON.parse(selectedCompanyString) : null;
const user = JSON.parse(localStorage.getItem('user') || '{}');
const userRole = typeof user.role === 'object' && user.role !== null ? user.role.name : (user.role || 'Junior');

const status = ref('Draft'); // Draft, Pending Review, Pending Approval, Approved
const preparedBy = ref('');
const preparedDate = ref('');
const reviewedBy = ref('');
const reviewDate = ref('');
const approvedBy = ref('');
const approvalDate = ref('');
const conclusionDate = ref('');
const partnerNotes = ref('');
const decision = ref('');
const signatureFile = ref<File | null>(null);
const signaturePreview = ref<string | null>(null);
const responseId = ref<number | null>(null);

async function loadFormData() {
    try {
        const token = localStorage.getItem('token');
        if (!selectedCompanyObj) return;

        // Ambil info engagement klien aktif
        const engRes = await fetch('/api/v1/engagements', {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        const engagements = await engRes.json();
        const matchedEng = engagements.find((e: any) => e.client?.id === selectedCompanyObj.id);
        if (!matchedEng) return;

        // Ambil info form
        const formsRes = await fetch('/api/v1/audit-forms', {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        const forms = await formsRes.json();
        const matchedForm = forms.find((f: any) => f.code === '1100');
        if (!matchedForm) return;

        // Ambil responses yang ada
        const res = await fetch('/api/v1/audit-form-responses', {
            headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
        });
        
        if (res.ok) {
            const data = await res.json();
            let item = data.find((r: any) => r.form?.code === '1100' && r.engagement_id === matchedEng.id);
            
            // Kalau belum ada response, buat response baru di backend
            if (!item) {
                const createRes = await fetch('/api/v1/audit-form-responses', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ form_id: matchedForm.id, engagement_id: matchedEng.id, user_id: user.id }),
                });
                if (createRes.ok) {
                    item = await createRes.json();
                }
            }

            if (item) {
                responseId.value = item.id;
                status.value = item.status;
                preparedBy.value = item.user?.name || preparedBy.value;
                preparedDate.value = item.submitted_at ? new Date(item.submitted_at).toLocaleDateString() : '';
                
                if (item.reviews && item.reviews.length > 0) {
                    const lastReview = item.reviews[item.reviews.length - 1];
                    reviewedBy.value = lastReview.reviewer?.name || reviewedBy.value;
                    reviewDate.value = lastReview.reviewed_at ? new Date(lastReview.reviewed_at).toLocaleDateString() : '';
                }
                
                if (item.approvals && item.approvals.length > 0) {
                    const lastApp = item.approvals[item.approvals.length - 1];
                    approvedBy.value = lastApp.approver?.name || approvedBy.value;
                    approvalDate.value = lastApp.approval_date ? new Date(lastApp.approval_date).toLocaleDateString() : '';
                }
            }
        }
    } catch (e) {
        console.error('Error loading form 1100 backend data:', e);
    }
}

function handleSignatureUpload(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        signatureFile.value = file;
        signaturePreview.value = URL.createObjectURL(file);
    }
}

const sections = ref([
    {
        title: 'Integritas dan Karakter Calon Klien',
        questions: [
            {
                no: '1.',
                text: 'Apakah anda, klien anda, atau rekan kerja anda mengenal calon klien tersebut?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '2.',
                text: 'Apakah anda yakin bahwa tidak ada hal-hal atau kondisi yang mengakibatkan diragukannya pemilik, dewan pimpinan, atau manajemen calon klien? Khususnya, apakah anda mempunyai keyakinan memadai bahwa hal-hal di bawah ini tidak terjadi pada calon klien?',
                subItems: [
                    'a. Putusan sanksi hukum',
                    'b. Dugaan adanya tindakan illegal atau kecurangan',
                    'c. Investigasi yang sedang berjalan',
                    'd. Keanggotaan manajemen dalam organisasi profesional yang mempunyai reputasi kurang baik',
                    'e. Publikasi negatif, dan kedekatan dengan pihak yang etikanya dipertanyakan'
                ],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Auditor / Akuntan Terdahulu',
        questions: [
            {
                no: '3.',
                text: 'Apakah anda telah menghubungi auditor/akuntan terdahulu (Jika relevan dalam yurisdiksi anda) dan menanyakan tentang:',
                subItems: [
                    'a. Akses terhadap kertas kerja calon klien',
                    'b. Adanya jasa profesional yang belum diselesaikan',
                    'c. Adanya perbedaan pendapat atau ketidaksepakatan',
                    'd. Integritas manajemen dan pimpinan',
                    'e. Alasan pergantian; dan',
                    'f. Adanya permintaan yang tidak masuk akal atau sikap tidak kooperatif'
                ],
                answer: '',
                comment: ''
            },
            {
                no: '4.',
                text: 'Apakah anda telah mendapat izin dari KAP terdahulu untuk menelaah kertas kerja tahun lalu (Jika diizinkan)? Jika ya, apakah anda telah melakukan penelaahan terhadap dokumentasi perencanaan periode lalu yang dilakukan oleh KAP terdahulu, dan menilai apakah KAP terdahulu:',
                subItems: [
                    'a. Dinyatakan independen terhadap klien',
                    'b. Dalam pelaksanaan audit, apakah KAP telah menerapkan standar sesuai SPM',
                    'c. Telah memiliki sumber daya dan keahlian yang memadai; dan',
                    'd. Telah memiliki pemahaman atas entitas dan lingkungannya'
                ],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Laporan Keuangan Sebelumnya',
        questions: [
            {
                no: '5.',
                text: 'Apakah anda telah menerima dan menelaah salinan:',
                subItems: [
                    'a. Laporan keuangan untuk periode sekurang-kurangnya dua tahun terakhir',
                    'b. Berkas surat pemberitahuan dan ketetapan pajak yang terkait dalam dua tahun terakhir; dan',
                    'c. Surat rekomendasi kepada manajemen (management letter) dalam dua atau tiga tahun terakhir'
                ],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Penerimaan Klien & Saldo Awal',
        questions: [
            {
                no: '6.',
                text: 'Seandainya anda mendapatkan akses, apakah anda telah menelaah kertas kerja periode sebelumnya yang dibuat oleh auditor atau akuntan terdahulu, yang bertujuan untuk:',
                subItems: [
                    'a. Menilai kewajaran saldo akhir periode sebelumnya dengan menitikberatkan akun-akun yang signifikan, untuk menentukan perlu tidaknya dilakukan penyajian kembali',
                    'b. Menentukan apakah auditor/akuntan terdahulu mengetahui adanya salah saji yang material',
                    'c. Menentukan dampak salah saji yang tidak material yang tidak disesuaikan pada laporan keuangan tahun sebelumnya; dan',
                    'd. Menilai kelayakan sistem akuntansi manajemen dengan menelaah jurnal penyesuaian dan surat rekomendasi kepada manajemen (management letter) yang dikeluarkan oleh auditor/akuntan terdahulu'
                ],
                answer: '',
                comment: ''
            },
            {
                no: '7.',
                text: 'Apakah anda telah menentukan kebijakan akuntansi yang signifikan dan metode yang digunakan dalam laporan keuangan periode tahun sebelumnya dan mempertimbangkan apakah telah diterapkan secara tepat dan konsisten? Misalnya:',
                subItems: [
                    'a. Penilaian signifikan seperti penyisihan piutang tak tertagih, persediaan, dan investasi',
                    'b. Kebijakan dan tarif amortisasi',
                    'c. Estimasi signifikan; dan',
                    'd. Lainnya (silakan mengidentifikasikan)'
                ],
                answer: '',
                comment: ''
            },
            {
                no: '8.',
                text: 'Apakah anda telah menentukan bahwa opini tidak menyatakan pendapat atas laporan keuangan tersebut akan dikeluarkan, sebagai akibat tidak diperolehnya keyakinan memadai pada saldo awal?',
                subItems: [],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Keahlian dan Sumber Daya Tim',
        questions: [
            {
                no: '9.',
                text: 'Apakah anda telah memperoleh pemahaman menyeluruh tentang bisnis dan operasi klien? (Lengkapi pemahaman pada memorandum klien atau gunakan checklist standar sebagai sumber Informasi).',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '10.',
                text: 'Apakah rekan dan staf memiliki pemahaman yang memadai mengenai praktik akuntansi atas industri calon klien untuk melaksanakan suatu perikatan? Jika tidak, apakah informasi yang dibutuhkan untuk memahami praktik akuntansi industry terkait telah diperoleh? Identifikasi sumber-sumber tersebut.',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '11.',
                text: 'Apakah terdapat hal yang telah diidentifikasi yang memerlukan sebuah pengetahuan khusus? Jika ya, apakah pengetahuan yang dibutuhkan tersebut telah tersedia? Identifikasi sumber-sumber tersebut.',
                subItems: [],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Penilaian Independensi',
        questions: [
            {
                no: '12.',
                text: 'Identifikasi dan dokumentasikan larangan-larangan yang ada (ancaman terhadap independensi dimana tidak terdapat pencegahan yang memadai) seperti:',
                subItems: [
                    'a. Penerimaan hadiah yang signifikan atau keramahtamahan dari klien',
                    'b. Hubungan bisnis yang dekat dengan klien',
                    'c. Hubungan keluarga dan kedekatan pribadi dengan klien',
                    'd. Fee yang jauh di bawah harga pasar',
                    'e. Kepentingan keuangan pada klien',
                    'f. Adanya hubungan ketenagakerjaan pada periode jasa assurance dengan klien',
                    'g. Pinjaman kepada/dari klien',
                    'h. Membuat jurnal atau klasifikasi akuntansi tanpa persetujuan manajemen',
                    'i. Melaksanakan fungsi manajemen untuk klien',
                    'j. Melaksanakan jasa non-assurance (konsultasi keuangan, bantuan hukum, jasa penilaian hal material)'
                ],
                answer: '',
                comment: ''
            },
            {
                no: '13.',
                text: 'Mengacu pada Bagian B Kode Etik sebagai panduan dalam mengidentifikasi ancaman dan tindak pengamanan terhadap independensi. Identifikasi dan dokumentasikan ancaman:',
                subItems: [
                    'a. Ancaman kepentingan pribadi',
                    'b. Ancaman telaah-pribadi',
                    'c. Ancaman kedekatan',
                    'd. Ancaman intimidasi'
                ],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Penelaahan Review Perikatan',
        questions: [
            {
                no: '14.',
                text: 'Sudahkah anda menentukan bahwa resiko terkait dengan industri dan calon klien masih dapat diterima oleh KAP? Jelaskan review yang sudah diketahui dan diduga akan terjadi dan dampaknya terhadap perikatan, termasuk:',
                subItems: [
                    'a. Pemilik yang dominan',
                    'b. Pelanggaran peraturan perundangan denda/penalti material',
                    'c. Permasalahan pembiayaan atau ketidakmampuan menyelesaikan',
                    'd. Perhatian media yang tinggi terhadap entitas/manajemen',
                    'e. Trend dan kinerja industri',
                    'f. Manajemen yang terlalu konservatif atau terlalu optimis',
                    'g. Partisipasi dalam bisnis berisiko tinggi',
                    'h. Sistem akuntansi dan pencatatan yang buruk',
                    'i. Transaksi tidak biasa / hubungan istimewa signifikan',
                    'j. Struktur operasi rumit / tidak biasa',
                    'k. Pengendalian dan manajemen yang lemah',
                    'l. Lemahnya kebijakan pengakuan pendapatan',
                    'm. Pengaruh signifikan perubahan teknologi',
                    'n. Potensi manfaat manajemen tergantung kinerja keuangan',
                    'o. Isu kompetensi/kredibilitas manajemen',
                    'p. Perubahan terkini manajemen kunci/akuntan/pengacara',
                    'q. Kewajiban pelaporan entitas kepada publik'
                ],
                answer: '',
                comment: ''
            },
            {
                no: '15.',
                text: 'Siapa yang umumnya menggunakan laporan keuangan? (Perbankan, DJP, Regulator, Manajemen, Kreditur, Investor, Pemegang Saham). Apakah ada perselisihan di antara pemegang saham?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '16.',
                text: 'Adakah bagian-bagian tertentu dari laporan keuangan atau akun-akun tertentu yang perlu mendapat perhatian lebih? Jika ya, dokumentasikan rinciannya.',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '17.',
                text: 'Apakah auditor/akuntan terdahulu mengajukan banyak jurnal penyesuaian dan atau mengidentifikasikan banyak koreksi yang tidak material dan tidak perlu disesuaikan?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '18.',
                text: 'Apakah anda yakin bahwa tidak ada keraguan yang signifikan terhadap kelangsungan usaha calon klien dalam waktu mendatang (sekurang-kurangnya satu tahun mendatang)?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '19.',
                text: 'Apakah anda yakin bahwa calon klien mau dan mampu membayar imbalan jasa profesional yang wajar?',
                subItems: [],
                answer: '',
                comment: ''
            }
        ]
    },
    {
        title: 'Pembatasan Ruang Lingkup & Lain-lain',
        questions: [
            {
                no: '20.',
                text: 'Apakah anda yakin bahwa tidak ada pembatasan ruang lingkup oleh manajemen klien yang mempengaruhi pekerjaan anda?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '21.',
                text: 'Apakah terdapat kriteria yang sesuai untuk digunakan dalam mengevaluasi informasi hal pokok perikatan?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '22.',
                text: 'Apakah jangka waktu untuk menyelesaikan pekerjaan masuk akal?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '23.',
                text: 'Apakah terdapat hal-hal lain berkaitan dengan penerimaan klien yang perlu dipertimbangkan, seperti penelaahan secara lebih detail yang terkait dengan independensi dan faktor-faktor lainnya yang beresiko?',
                subItems: [],
                answer: '',
                comment: ''
            },
            {
                no: '24.',
                text: 'Catatan lainnya.',
                subItems: [],
                answer: '',
                comment: ''
            }
        ]
    }
]);

async function submitToManager() {
    // Validasi kelengkapan isian
    for (const section of sections.value) {
        for (const q of section.questions) {
            if (!q.answer) {
                alert(`Pertanyaan nomor ${q.no} belum dijawab! Form wajib diisi lengkap.`);
                return;
            }
            if (q.answer !== 'NA' && (!q.comment || q.comment.trim() === '')) {
                alert(`Komentar/penjelasan pada pertanyaan nomor ${q.no} wajib diisi!`);
                return;
            }
        }
    }

    if (!responseId.value) {
        alert('Gagal submit, ID response tidak ditemukan.');
        return;
    }

    try {
        const response = await fetch(`/api/v1/audit-form-responses/${responseId.value}/submit`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });
        if (response.ok) {
            status.value = 'Pending Review';
            alert('Form 1100 berhasil di-submit ke Manager untuk proses review.');
        } else {
            alert('Gagal submit form.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan koneksi.');
    }
}

// Tambahkan state untuk komentar review
const managerComment = ref('');
const reviewLogs = ref<{ by: string, date: string, comment: string, action: string }[]>([]);

async function managerReview(action: 'approve' | 'revise') {
    if (!responseId.value) return alert('ID form response tidak ditemukan.');
    try {
        const response = await fetch(`/api/v1/audit-form-responses/${responseId.value}/review`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ 
                action: action === 'approve' ? 'approve' : 'request_revision',
                comments: managerComment.value // Kirim komentar
            })
        });
        if (response.ok) {
            if (action === 'approve') {
                status.value = 'Pending Approval';
                alert('Form diteruskan ke Partner.');
            } else {
                status.value = 'Revision Required';
                reviewLogs.value.push({
                    by: user.name,
                    date: new Date().toLocaleDateString(),
                    comment: managerComment.value,
                    action: 'Revisi Diminta'
                });
                alert('Form dikembalikan untuk revisi.');
            }
        }
    } catch (e) {
        alert('Terjadi kesalahan.');
    }
}

onMounted(loadFormData);

async function partnerApprove(action: 'approve' | 'revise') {
    if (!responseId.value) return alert('ID form response tidak ditemukan.');
    try {
        const response = await fetch(`/api/v1/audit-form-responses/${responseId.value}/approve`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            },
            body: JSON.stringify({ action: action === 'approve' ? 'approve' : 'reject' })
        });
        if (response.ok) {
            if (action === 'approve') {
                status.value = 'Approved';
                approvedBy.value = user.name || 'MGN';
                approvalDate.value = new Date().toISOString().slice(0, 10);
                alert('Form 1100 disetujui (Approved) secara final.');
            } else {
                status.value = 'Draft';
                alert('Form ditolak/diminta revisi.');
            }
        } else {
            alert('Gagal memproses approval.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan koneksi.');
    }
}

async function generateDocument() {
    // 1. Validasi kelengkapan seluruh pertanyaan & komentar
    for (const section of sections.value) {
        for (const q of section.questions) {
            if (!q.answer) {
                alert(`Tidak dapat men-generate dokumen! Pertanyaan nomor ${q.no} belum dijawab.`);
                return;
            }
            if (q.answer !== 'NA' && (!q.comment || q.comment.trim() === '')) {
                alert(`Tidak dapat men-generate dokumen! Komentar pada nomor ${q.no} wajib diisi.`);
                return;
            }
        }
    }

    // 2. Validasi kelengkapan catatan rekan & keputusan
    if (!partnerNotes.value || partnerNotes.value.trim() === '') {
        alert('Tidak dapat men-generate dokumen! Catatan Rekan belum diisi.');
        return;
    }

    if (!decision.value) {
        alert('Tidak dapat men-generate dokumen! Keputusan (Diterima/Ditolak) belum dipilih.');
        return;
    }

    if (!responseId.value) return alert('ID form response tidak ditemukan.');
    
    try {
        const token = localStorage.getItem('token');
        const url = `/api/v1/audit-form-responses/${responseId.value}/export`;
        
        const res = await fetch(url, {
            headers: { Authorization: `Bearer ${token}` }
        });
        
        if (res.ok) {
            const blob = await res.blob();
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = `Audit_1100_${selectedCompanyObj?.name || 'Client'}.docx`;
            document.body.appendChild(a);
            a.click();
            a.remove();
        } else {
            alert('Gagal generate dokumen.');
        }
    } catch (e) {
        console.error(e);
        alert('Terjadi kesalahan koneksi.');
    }
}
</script>

<template>
    <DashboardLayout>
        <div class="form-container">
            <header class="memo-header card">
                <div class="title-section">
                    <h2>KAP MGN & REKAN</h2>
                    <h3>MEMO PENERIMAAN & KEBERLANJUTAN KLIEN (FORM 1100)</h3>
                    <p class="subtitle">Hal-hal yang perlu dipertimbangkan selama proses evaluasi untuk menerima perikatan dengan klien</p>
                </div>
                <div class="status-box">
                    <span :class="['badge-status', status.toLowerCase().replace(' ', '-')]">{{ status }}</span>
                </div>
            </header>

            <section class="client-info-grid card">
                <div class="info-item"><label>Nama Klien:</label> <span>{{ selectedCompanyObj?.name || 'PT Indo American Seafoods Tbk dan Entitas Anak' }}</span></div>
                <div class="info-item"><label>Periode:</label> <span>31 Desember 2024</span></div>
                <div class="info-item"><label>Dibuat Oleh:</label> <span>{{ preparedBy }} ({{ preparedDate }})</span></div>
                <div class="info-item"><label>Direview Oleh:</label> <span>{{ reviewedBy }} ({{ reviewDate }})</span></div>
                <div class="info-item"><label>Disetujui Oleh:</label> <span>{{ approvedBy }} ({{ approvalDate }})</span></div>
            </section>

            <!-- Question Sections (1 to 24) -->
            <div v-for="section in sections" :key="section.title" class="section-card card">
                <h4>{{ section.title }}</h4>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">No</th>
                            <th>Prosedur / Pertanyaan Evaluasi</th>
                            <th style="width: 140px;">Pilihan</th>
                            <th style="width: 35%;">Komentar / Penjelasan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="q in section.questions" :key="q.no">
                            <td style="text-align: center; font-weight: bold; white-space: nowrap; vertical-align: top; padding-top: 1rem;">{{ q.no }}</td>
                            <td class="question-cell">
                                <div>{{ q.text }}</div>
                                <ul v-if="q.subItems && q.subItems.length" class="subitem-list">
                                    <li v-for="sub in q.subItems" :key="sub">{{ sub }}</li>
                                </ul>
                            </td>
                            <td class="answer-cell">
                                <template v-if="status === 'Draft'">
                                    <select v-model="q.answer">
                                        <option value="">-- Pilih --</option>
                                        <option value="Y">Ya</option>
                                        <option value="T">Tidak</option>
                                        <option value="NA">N/A</option>
                                    </select>
                                </template>
                                <span v-else class="answer-display">{{ q.answer === 'Y' ? 'Ya' : q.answer === 'T' ? 'Tidak' : 'N/A' }}</span>
                            </td>
                            <td class="comment-cell">
                                <template v-if="status === 'Draft'">
                                    <textarea 
                                        v-model="q.comment" 
                                        placeholder="Wajib diisi..."
                                        @input="(e) => {
                                            const target = e.target as HTMLTextAreaElement;
                                            target.style.height = 'auto';
                                            target.style.height = target.scrollHeight + 'px';
                                        }"
                                        v-mounted-auto-resize
                                    ></textarea>
                                </template>
                                <p v-else class="comment-display">{{ q.comment }}</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Catatan Rekan / Kesimpulan Partner -->
            <section class="partner-conclusion card">
                <h4>Catatan Rekan & Kesimpulan</h4>
                <div class="form-group">
                    <label><strong>Catatan Rekan:</strong></label>
                    <textarea v-model="partnerNotes" :disabled="status !== 'Draft' && status !== 'Pending Approval'" rows="4"></textarea>
                </div>
                <div class="conclusion-box">
                    <p>Berdasarkan pengetahuan awal mengenai calon klien ini serta faktor-faktor di atas, penilaian terhadap calon Klien ini:</p>
                    <div class="decision-radio">
                        <label><input type="radio" v-model="decision" value="Diterima" :disabled="status !== 'Draft' && status !== 'Pending Approval'"> <strong>Diterima</strong></label>
                        <label><input type="radio" v-model="decision" value="Ditolak" :disabled="status !== 'Draft' && status !== 'Pending Approval'"> <strong>Ditolak</strong></label>
                    </div>
                    
                    <div class="signature-section" v-if="userRole === 'Partner'">
                        <label><strong>Upload Tanda Tangan Partner:</strong></label>
                        <div class="signature-upload">
                            <input type="file" @change="handleSignatureUpload" accept="image/*,application/pdf" :disabled="status === 'Approved'">
                            <div v-if="signaturePreview" class="preview">
                                <img :src="signaturePreview" alt="Signature" width="150" />
                            </div>
                        </div>
                    </div>

                    <div class="conclusion-meta">
                        <span>Tanggal Kesimpulan: <strong>{{ conclusionDate }}</strong></span>
                    </div>
                </div>
            </section>

            <!-- Workflow Action Buttons -->
            <footer class="action-footer card">
                <div class="action-left">
                    <p class="role-hint">Role akun login: <strong>{{ userRole }}</strong></p>
                </div>
                <div class="action-right">
                    <button class="btn success" @click="generateDocument">Generate Word</button>

                    <button v-if="userRole === 'Junior' || userRole === 'Senior'" 
                            v-show="status === 'Draft' || status === 'Revision Required' || status === 'draft' || status === 'revision_required'" 
                            class="btn primary" 
                            @click="submitToManager">
                        Submit ke Manager
                    </button>
                    
                    <template v-if="(status === 'Pending Review' || status === 'pending_review') && userRole === 'Manager'">
                        <div class="review-panel">
                            <textarea v-model="managerComment" placeholder="Masukkan catatan review / revisi di sini..." class="comment-box"></textarea>
                            <div class="action-right">
                                <button class="btn danger" @click="managerReview('revise')">Minta Revisi ke Auditor</button>
                                <button class="btn primary" @click="managerReview('approve')">Review Selesai &amp; Teruskan ke Partner</button>
                            </div>
                        </div>
                    </template>

                    <template v-if="(status === 'Pending Approval' || status === 'reviewed' || status === 'pending_approval') && userRole === 'Partner'">
                        <div class="review-panel">
                            <textarea v-model="managerComment" placeholder="Masukkan catatan approval / penolakan..." class="comment-box"></textarea>
                            <div class="action-right">
                                <button class="btn danger" @click="partnerApprove('revise')">Tolak / Minta Revisi</button>
                                <button class="btn success" @click="partnerApprove('approve')">Approve Final (Partner)</button>
                            </div>
                        </div>
                    </template>
                </div>
            </footer>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.form-container { max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem; }
.card { background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }

.memo-header { display: flex; justify-content: space-between; align-items: center; border-left: 5px solid var(--orange-600); }
.title-section h2 { margin: 0; font-size: 1.1rem; color: #7f8c8d; letter-spacing: 1px; font-family: var(--font-body); }
.title-section h3 { margin: 0.25rem 0; font-size: 1.3rem; }
.subtitle { margin: 0; font-size: 0.85rem; color: #95a5a6; }

/* Status form — token sama dengan status-badge global di style.css,
   dipertahankan sebagai class lokal karena logic status masih string custom */
.badge-status { padding: 0.4rem 1rem; border-radius: 20px; font-weight: bold; font-size: 0.85rem; white-space: nowrap; }
.badge-status.draft { background: color-mix(in srgb, var(--status-neutral) 18%, white); color: var(--status-neutral); }
.badge-status.pending-review { background: color-mix(in srgb, var(--status-review) 18%, white); color: var(--status-review); }
.badge-status.pending-approval { background: color-mix(in srgb, var(--status-progress) 18%, white); color: var(--status-progress); }
.badge-status.approved { background: color-mix(in srgb, var(--status-approved) 18%, white); color: var(--status-approved); }

.client-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; background: var(--surface); }
.info-item label { display: block; font-size: 0.75rem; color: #7f8c8d; margin-bottom: 0.25rem; font-weight: bold; }
.info-item span { font-size: 0.95rem; font-weight: 600; word-break: break-word; }

.section-card h4, .partner-conclusion h4 { margin: 0 0 1rem; font-size: 1rem; border-bottom: 1px solid var(--surface-border); padding-bottom: 0.5rem; font-family: var(--font-body); color: var(--ink-900); }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
th { background: var(--surface); padding: 0.75rem 0.75rem; text-align: left; font-size: 0.8rem; color: #57606f; border-bottom: 2px solid var(--surface-border); word-wrap: break-word; }
td { padding: 0.75rem 0.75rem; border-bottom: 1px solid var(--surface-border); vertical-align: top; font-size: 0.85rem; color: #2f3542; word-wrap: break-word; overflow-wrap: break-word; }
.text-center { text-align: center; }

.question-cell { line-height: 1.5; }
.answer-cell { min-width: 100px; }
.answer-display { font-weight: 600; color: var(--ink-900); font-size: 0.85rem; }
.comment-cell { min-width: 150px; }
.comment-display { margin: 0; font-size: 0.85rem; color: #2f3542; line-height: 1.5; white-space: pre-line; }

.subitem-list { margin: 0.5rem 0 0 0.5rem; padding-left: 0.5rem; color: #57606f; font-size: 0.8rem; list-style-type: none; }
.subitem-list li { margin-bottom: 0.2rem; }

select { width: 100%; padding: 0.4rem; border: 1px solid #ced6e0; border-radius: 4px; box-sizing: border-box; font-family: inherit; font-size: 0.85rem; }
textarea { width: 100%; padding: 0.4rem; border: 1px solid #ced6e0; border-radius: 4px; box-sizing: border-box; font-family: inherit; font-size: 0.85rem; resize: none; overflow: hidden; min-height: 40px; }
select:focus, textarea:focus { outline: none; border-color: var(--orange-600); }

.partner-conclusion .form-group { margin-bottom: 1rem; }
.partner-conclusion .form-group textarea { resize: vertical; }
.decision-radio { display: flex; gap: 2rem; margin: 1rem 0; }
.signature-section { margin-top: 1rem; padding: 1rem; background: var(--surface); border: 1px dashed var(--orange-600); border-radius: 6px; }
.signature-upload { display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem; }
.conclusion-meta { margin-top: 1rem; font-size: 0.9rem; color: #7f8c8d; }

.action-footer { display: flex; justify-content: space-between; align-items: center; position: sticky; bottom: 1rem; z-index: 5; }
.role-hint { margin: 0; font-size: 0.9rem; color: #7f8c8d; }
.action-right { display: flex; gap: 0.75rem; }

.btn { padding: 0.6rem 1.2rem; border-radius: 4px; font-weight: bold; cursor: pointer; border: none; font-size: 0.9rem; }
.btn.primary { background: var(--orange-600); color: #fff; }
.btn.primary:hover { background: var(--orange-600-hover); }
.btn.success { background: var(--green-700); color: #fff; }
.btn.success:hover { background: var(--green-700-hover); }
.btn.danger { background: var(--status-overdue); color: #fff; }
.btn.danger:hover { background: #953f32; }
</style>


