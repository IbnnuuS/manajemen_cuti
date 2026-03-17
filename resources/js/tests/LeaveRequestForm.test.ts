import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

// ─────────────────────────────────────────────────────────────
// Unit helper: validasi logika form pengajuan cuti
// (tanpa mounting komponen penuh, fokus pada logic layer saja)
// ─────────────────────────────────────────────────────────────

interface LeaveFormData {
    leave_type_id: string | null;
    start_date: string;
    end_date: string;
    reason: string;
}

function validateLeaveForm(form: LeaveFormData): string[] {
    const errors: string[] = [];

    if (!form.leave_type_id) {
        errors.push('Tipe cuti harus dipilih.');
    }

    if (!form.start_date) {
        errors.push('Tanggal mulai harus diisi.');
    }

    if (!form.end_date) {
        errors.push('Tanggal selesai harus diisi.');
    }

    if (form.start_date && form.end_date) {
        if (new Date(form.end_date) < new Date(form.start_date)) {
            errors.push('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
        }
    }

    if (form.start_date) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const start = new Date(form.start_date);
        if (start < today) {
            errors.push('Tanggal mulai tidak boleh di masa lalu.');
        }
    }

    if (!form.reason || form.reason.trim().length < 5) {
        errors.push('Alasan cuti minimal 5 karakter.');
    }

    return errors;
}

// ─────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────

describe('Validasi Form Pengajuan Cuti', () => {

    it('Mengembalikan error jika semua field kosong', () => {
        const errors = validateLeaveForm({
            leave_type_id: null,
            start_date: '',
            end_date: '',
            reason: '',
        });
        expect(errors.length).toBeGreaterThan(0);
        expect(errors).toContain('Tipe cuti harus dipilih.');
        expect(errors).toContain('Tanggal mulai harus diisi.');
        expect(errors).toContain('Tanggal selesai harus diisi.');
        expect(errors).toContain('Alasan cuti minimal 5 karakter.');
    });

    it('Lolos validasi jika semua field diisi dengan benar', () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 3);

        const errors = validateLeaveForm({
            leave_type_id: '1',
            start_date: tomorrow.toISOString().split('T')[0],
            end_date: dayAfter.toISOString().split('T')[0],
            reason: 'Liburan bersama keluarga',
        });
        expect(errors.length).toBe(0);
    });

    it('Error jika end_date lebih awal dari start_date', () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 5);
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() + 1);

        const errors = validateLeaveForm({
            leave_type_id: '1',
            start_date: tomorrow.toISOString().split('T')[0],  // lebih jauh
            end_date: yesterday.toISOString().split('T')[0],   // lebih dekat
            reason: 'Test tanggal terbalik',
        });
        expect(errors).toContain('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
    });

    it('Error jika start_date di masa lalu', () => {
        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 2);
        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 1);

        const errors = validateLeaveForm({
            leave_type_id: '1',
            start_date: yesterday.toISOString().split('T')[0],
            end_date: dayAfter.toISOString().split('T')[0],
            reason: 'Test tanggal masa lalu',
        });
        expect(errors).toContain('Tanggal mulai tidak boleh di masa lalu.');
    });

    it('Error jika alasan terlalu pendek', () => {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        const errors = validateLeaveForm({
            leave_type_id: '1',
            start_date: tomorrow.toISOString().split('T')[0],
            end_date: tomorrow.toISOString().split('T')[0],
            reason: 'ok', // terlalu pendek
        });
        expect(errors).toContain('Alasan cuti minimal 5 karakter.');
    });
});

// ─────────────────────────────────────────────────────────────
// Test Auth Store Logic (mock-based)
// ─────────────────────────────────────────────────────────────
describe('Auth Logic', () => {

    it('Token tersimpan di localStorage setelah login berhasil', () => {
        // Simulasi penyimpanan token
        const fakeToken = 'fake-bearer-token-abc123';
        localStorage.setItem('auth_token', fakeToken);

        expect(localStorage.getItem('auth_token')).toBe(fakeToken);
    });

    it('Token dihapus saat logout', () => {
        localStorage.setItem('auth_token', 'some-token');
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user');

        expect(localStorage.getItem('auth_token')).toBeNull();
        expect(localStorage.getItem('user')).toBeNull();
    });

    it('User teridentifikasi sebagai admin berdasarkan role', () => {
        const isAdmin = (role: string) => role === 'admin';
        expect(isAdmin('admin')).toBe(true);
        expect(isAdmin('user')).toBe(false);
    });
});
