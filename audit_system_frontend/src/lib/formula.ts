// Evaluator formula aritmatika sederhana & aman (BUKAN eval bebas) — dipakai
// bersama oleh WorksheetTable.vue (form 3100-style, "form worksheet") dan
// RepeaterField.vue (tabel di dalam field checklist, mis. daftar pemegang
// saham). Sebelumnya 2 implementasi terpisah yang gampang divergen kalau ada
// bug/perbaikan di salah satu tapi lupa di-apply ke yang lain — sekarang 1
// tempat, dipakai keduanya.
//
// Formula HANYA berasal dari definisi kolom yang ditulis admin/seeder di
// database (options_json / formula_expression), bukan dari input user,
// jadi aman dari injeksi — tapi tetap divalidasi lewat regex whitelist
// sebelum dieval sebagai jaga-jaga.
//
// Token khusus `__multiplier__` dipakai RepeaterField untuk formula yang
// punya pengali yang bisa diubah auditor (mis. "jumlah_lembar * __multiplier__"
// untuk menghitung Nilai Rp dari Jumlah Lembar Saham x harga per lembar).

export function evalFormula(
    expr: string,
    row: Record<string, any>,
    multiplierValue?: number
): number {
    const tokens = expr.match(/[a-zA-Z_][a-zA-Z0-9_]*|[-+*/().]|\d+(\.\d+)?/g) || [];

    const resolved = tokens.map((t) => {
        if (t === '__multiplier__') {
            return String(Number(multiplierValue) || 0);
        }
        if (/^[a-zA-Z_]/.test(t)) {
            const v = Number(row[t]);
            return Number.isFinite(v) ? String(v) : '0';
        }
        return t;
    });

    const joined = resolved.join(' ');
    // Whitelist ketat: setelah substitusi, cuma boleh tersisa angka/operator/spasi.
    if (!/^[\d\s+\-*/().]*$/.test(joined) || joined.trim() === '') return 0;

    try {
        // eslint-disable-next-line no-new-func
        const result = Function(`"use strict"; return (${joined});`)();
        return typeof result === 'number' && Number.isFinite(result) ? result : 0;
    } catch {
        return 0;
    }
}

export function parseNumber(val: any): number {
    if (typeof val === 'number') return Number.isFinite(val) ? val : 0;
    if (!val) return 0;
    let s = String(val).replace(/[^0-9,.-]/g, '').trim();
    if (!s) return 0;
    // Format Indonesia: titik sebagai pemisah ribuan (misal 330.321.157.819)
    if (s.includes('.') && !s.includes(',')) {
        const parts = s.split('.');
        if (parts.length > 2 || (parts.length === 2 && parts[1].length === 3)) {
            s = s.replace(/\./g, '');
        }
    } else if (s.includes('.') && s.includes(',')) {
        s = s.replace(/\./g, '').replace(',', '.');
    } else if (s.includes(',')) {
        s = s.replace(',', '.');
    }
    const n = parseFloat(s);
    return Number.isFinite(n) ? n : 0;
}

export function formatNumber(value: number): string {
    return value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
}
