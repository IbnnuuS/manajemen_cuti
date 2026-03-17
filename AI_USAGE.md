# 🤖 AI Usage Documentation

Dokumen ini mencatat penggunaan AI dalam pengembangan proyek **Sistem Manajemen Cuti**.

---

## 🛠️ Tools AI yang Digunakan

| Tool          | Provider      | Kegunaan                                      |
|---------------|---------------|-----------------------------------------------|
| **Chat GPT** | OpenAI | Coding assistant — implementasi kode, debugging, dokumentasi |
| **GitHub Copilot** | GitHub / OpenAI | Autocomplete & snippet saat menulis kode manual |

---

## 💬 Daftar Prompt yang Digunakan

### 🧱 STEP 1–2: Setup Proyek & Database
```
Buat project Laravel 12 + Vue 3 + TypeScript dengan Inertia.js.
Setup autentikasi menggunakan Laravel Sanctum.
Buat migration untuk tabel: users, leave_types, leave_balances, leave_requests.
```

### 🔐 STEP 3: Business Logic
```
Implementasikan logika:
- Validasi: start_date ≤ end_date, tidak boleh tanggal masa lalu, quota cukup, tidak boleh overlap
- APPROVE: ubah status → approved, kurangi quota (leave_balances.used += total_days)
- REJECT: status → rejected, tidak mengubah quota
- CANCEL: status → cancelled, hanya bisa jika pending
- DELETE: hanya untuk status final, isi deleted_at dan deleted_by
```

### 🏗️ STEP 4: Service Layer
```
Pisahkan logic ke:
- LeaveRequestService (submit, approve, reject, cancel, delete)
- AuthService (login, logout)
Tujuan: controller tetap clean, logic reusable
```

### 🧾 STEP 5: Validation Request Class
```
Gunakan Form Request class:
- StoreLeaveRequest
- ApproveLeaveRequest
- RejectLeaveRequest
```

### 🌐 STEP 6: Frontend Vue 3 + Template
```
Buat halaman:
- LoginPage
- Dashboard (quota card + riwayat tabel)
- LeaveRequestPage (form pengajuan)
- AdminLeavePage (riwayat semua pengajuan)
- AdminUserPage (riwayat request dalam konteks karyawan)

Implementasikan template NiceAdmin Bootstrap yang ada di folder /template.
Terjemahkan semua teks UI ke Bahasa Indonesia.
Gunakan Axios instance dengan Authorization Bearer Token.
Gunakan Pinia store untuk auth state.
```

### 📊 STEP 6b: Admin Dashboard & User Management
```
Tambahkan Admin Dashboard dengan:
- Card statistik: pending, disetujui, ditolak
- Tabel "Perlu Tindakan" dengan tombol Setujui/Tolak untuk pengajuan pending

Ubah AdminUserPage menjadi halaman riwayat semua pengajuan cuti (tanpa aksi).

Tambahkan halaman "Kelola User":
- Card total karyawan
- Tabel karyawan dengan pagination
- Tombol "Tambah Karyawan Baru" yang membuka modal
- Modal berisi input nama, email, password
- Otomatis assign: Cuti Tahunan 12 hari + Cuti Sakit 6 hari
```

### 🧪 STEP 7: Testing
```
Buat PHPUnit Feature Test untuk:
- Quota tidak cukup → gagal submit
- Approve → mengurangi saldo
- Reject → tidak mengurangi saldo
- Cancel hanya bisa jika status pending
- Delete hanya untuk status final

Buat Vitest unit test untuk:
- Validasi form (field kosong, tanggal terbalik, tanggal lalu, alasan terlalu pendek)
- Auth: token tersimpan setelah login, terhapus setelah logout
```

### 📄 STEP 8: Dokumentasi
```
Buat README.md berisi:
- Cara install backend (composer install, migrate, seed)
- Cara install frontend (npm install, npm run build)
- Cara menjalankan project
- Cara menjalankan test
- Struktur folder
- Daftar API endpoint dan contoh request/response

Buat AI_USAGE.md berisi list prompt dan tools AI yang digunakan.
```

---

## ✅ Final Checklist

| Fitur                            | Status |
|----------------------------------|--------|
| Login berhasil                   | ✅     |
| Token tersimpan di localStorage  | ✅     |
| Submit cuti berhasil             | ✅     |
| Approve mengurangi quota         | ✅     |
| Reject tidak mengurangi quota    | ✅     |
| Cancel hanya saat pending        | ✅     |
| Delete sesuai aturan status      | ✅     |
| Error message jelas              | ✅     |
| Admin dashboard statistik        | ✅     |
| Tambah karyawan + assign kuota   | ✅     |
| Hapus karyawan + data terkait    | ✅     |
| Pagination user list             | ✅     |
| UI full Bahasa Indonesia         | ✅     |
| Template NiceAdmin terintegrasi  | ✅     |

---

## 📌 Catatan

- Seluruh pengembangan dilakukan dengan bantuan **ChatGPT** sebagai pair programming AI assistant.
- AI digunakan untuk: scaffolding awal, implementasi business logic, debugging, refactoring, dan penulisan dokumentasi.
- Review dan keputusan arsitektur tetap dilakukan oleh developer manusia.
