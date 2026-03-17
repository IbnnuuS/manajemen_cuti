# 📋 Sistem Manajemen Cuti

Aplikasi web untuk manajemen pengajuan cuti karyawan berbasis **Laravel 12** (backend API) + **Vue 3 + TypeScript**.

---

## 🚀 Fitur Utama

- **Karyawan**: Login, lihat saldo cuti, ajukan cuti, batalkan pengajuan, lihat riwayat
- **Admin**: Setujui/tolak pengajuan, lihat semua riwayat, kelola data karyawan
- **Otomatis**: Penambahan karyawan baru langsung mendapatkan saldo Cuti Tahunan (12 hari) dan Cuti Sakit (6 hari)
- **Validasi**: Tanggal tidak boleh masa lalu, tidak boleh overlap, kuota harus mencukupi

---

## 🛠️ Tech Stack

| Layer     | Teknologi                                  |
|-----------|--------------------------------------------|
| Backend   | PHP 8.2+, Laravel 12, Laravel Sanctum      |
| Frontend  | Vue 3, TypeScript, Inertia.js, Pinia, Axios |
| Database  | PostgreSQL                                 |
| UI        | Bootstrap 5 (NiceAdmin Template)           |
| Build     | Vite 7                                     |

---

## 📦 Install Backend (Laravel)

```bash
# 1. Clone repo
git clone <repo-url>
cd manajemen_cuti

# 2. Install PHP dependencies
composer install

# 3. Salin file environment
cp .env.example .env

# 4. Generate app key
php artisan key:generate

# 5. Konfigurasi database di .env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=manajemen_cuti
# DB_USERNAME=postgres
# DB_PASSWORD=yourpassword

# 6. Jalankan migrasi dan seeder
php artisan migrate --seed
```

---

## 🎨 Install Frontend (Vue 3 + Vite)

```bash
# 1. Install Node.js dependencies
npm install

# 2. Build untuk production
npm run build

# Atau untuk development dengan hot-reload:
npm run dev
```

---

## ▶️ Menjalankan Proyek

```bash
# Jalankan Laravel development server
php artisan serve
```

Buka browser: **http://127.0.0.1:8000**

### Akun Default (setelah seeder)

| Role  | Email             | Password |
|-------|-------------------|----------|
| Admin | admin@example.com | password |

---

## 🧪 Menjalankan Test

### Backend — PHPUnit

```bash
# Jalankan semua test
php artisan test

# Jalankan hanya feature test leave request
php artisan test --filter=LeaveRequestTest

# Dengan tampilan verbose
php artisan test --verbose
```

### Frontend — Vitest

```bash
# Jalankan semua test
npx vitest run

# Mode watch (auto-rerun saat file berubah)
npx vitest

# Dengan coverage report
npx vitest run --coverage
```

---

## 📂 Struktur Folder Penting

```
manajemen_cuti/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php          # Login / Logout
│   │   │   ├── LeaveRequestController.php  # Submit, Approve, Reject, Cancel, Delete
│   │   │   ├── LeaveInfoController.php     # Tipe & saldo cuti
│   │   │   └── AdminController.php         # Dashboard, Kelola User
│   │   └── Requests/
│   │       ├── StoreLeaveRequest.php       # Validasi submit cuti
│   │       ├── ApproveLeaveRequest.php     # Validasi approve
│   │       └── RejectLeaveRequest.php      # Validasi reject
│   ├── Models/
│   │   ├── User.php
│   │   ├── LeaveRequest.php
│   │   ├── LeaveType.php
│   │   └── LeaveBalance.php
│   └── Services/
│       ├── AuthService.php                 # Logic login/logout
│       └── LeaveRequestService.php         # Business logic cuti
├── resources/js/
│   ├── pages/
│   │   ├── Login.vue
│   │   ├── Dashboard.vue                   # Dashboard karyawan
│   │   ├── LeaveRequestPage.vue            # Form pengajuan
│   │   ├── AdminDashboard.vue              # Dashboard admin
│   │   ├── AdminLeavePage.vue              # Riwayat semua cuti
│   │   └── AdminManageUsers.vue            # Kelola karyawan
│   ├── stores/
│   │   └── auth.ts                         # Pinia auth store
│   ├── layouts/
│   │   └── AuthenticatedLayout.vue         # Layout utama
│   ├── api.ts                              # Axios instance + interceptor
│   └── tests/
│       └── LeaveRequestForm.test.ts        # Vitest frontend tests
├── routes/
│   ├── api.php                             # API routes (Sanctum protected)
│   └── web.php                             # Inertia web routes
└── tests/
    └── Feature/
        └── LeaveRequestTest.php            # PHPUnit backend tests
```

---

## 🌐 API Endpoints

### Public
| Method | Endpoint     | Deskripsi |
|--------|--------------|-----------|
| POST   | /api/login   | Login karyawan / admin |

### Protected (Bearer Token)
| Method | Endpoint                           | Role  | Deskripsi                    |
|--------|------------------------------------|-------|------------------------------|
| POST   | /api/logout                        | All   | Logout                       |
| GET    | /api/user                          | All   | Data user aktif              |
| GET    | /api/leave-types                   | All   | Daftar tipe cuti             |
| GET    | /api/my-leave-balances             | User  | Saldo cuti milik sendiri     |
| GET    | /api/my-leave-requests             | User  | Riwayat pengajuan sendiri    |
| POST   | /api/leave-requests                | User  | Ajukan cuti baru             |
| POST   | /api/leave-requests/{id}/cancel    | User  | Batalkan pengajuan (pending) |
| DELETE | /api/leave-requests/{id}           | All   | Hapus pengajuan (final)      |
| GET    | /api/leave-requests                | Admin | Semua riwayat pengajuan      |
| POST   | /api/leave-requests/{id}/approve   | Admin | Setujui pengajuan            |
| POST   | /api/leave-requests/{id}/reject    | Admin | Tolak pengajuan              |
| GET    | /api/admin/dashboard-stats         | Admin | Statistik dashboard          |
| GET    | /api/admin/users                   | Admin | Daftar karyawan (paginated)  |
| POST   | /api/admin/users                   | Admin | Tambah karyawan baru         |
| DELETE | /api/admin/users/{id}              | Admin | Hapus karyawan               |

---

## 📋 Contoh API Request / Response

### Login
```json
POST /api/login
{
  "email": "admin@example.com",
  "password": "password"
}

// Response 200
{
  "success": true,
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

### Submit Cuti
```json
POST /api/leave-requests
Authorization: Bearer {token}
{
  "leave_type_id": 1,
  "start_date": "2026-03-20",
  "end_date": "2026-03-22",
  "reason": "Liburan keluarga"
}

// Response 201
{
  "success": true,
  "message": "Leave request submitted successfully.",
  "data": {
    "id": 5,
    "status": "pending",
    "total_days": 3
  }
}

// Response 400 — kuota tidak cukup
{
  "success": false,
  "message": "Insufficient leave balance. You are requesting 5 days but only have 2 days remaining."
}
```
