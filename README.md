# Sales Order Management API

RESTful API untuk mengelola data master **Products** dan transaksi **Sales Orders** beserta detail item dan variannya (`Bordir` / `Tanpa Bordir`). Dibangun menggunakan **Laravel 12**, **PostgreSQL**, dan **Laravel Sanctum** untuk autentikasi berbasis *Bearer Token*.

---

## Tech Stack
* **Framework:** Laravel >= 12
* **Language:** PHP >= 8.2
* **Database:** PostgreSQL
* **Authentication:** Laravel Sanctum (*Bearer Token*)

---

## Panduan Instalasi & Deployment

Ikuti langkah-langkah berikut untuk menjalankan aplikasi ini di komputer lokal atau server lain:

### 1. Ekstrak / Clone Project & Install Dependencies
```bash
cd sales-order-api
composer install
```
### 2. Konfigurasi Environment (.env)
* Salin file **`.env.example`** menjadi **`.env`**:
```bash
cp .env.example .env
```
* Buka file .env dan sesuaikan pengaturan database PostgreSQL Anda:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sales_order_db
DB_USERNAME=postgres
DB_PASSWORD=password_anda
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Migrasi Database & Seeding Data Dummy
* Run untuk create semua tabel dan foreign key nya dan untuk mengisi DB dengan data dummy (user account dan produk awal):
```bash
php artisan migrate --seed
```

### 5. Jalankan Server Lokal
```bash
php artisan serve
```
* Base URL API: **`http://127.0.0.1:8000/api`**


## Akun Dummy (Seeder)
* Email : **`tester@kolabora.com`**
* Password : **`password123`**

## Dokumentasi API
### Semua endpoint kecuali (/api/login) wajib menggunakan header:
* **`Authorization: Bearer <your_token>`**
* **`Accept: application/json`**

### A. Authentication

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `POST` | `/api/login` | Login user untuk mendapatkan *Bearer Token* |

**Contoh Request Body (`POST /api/login`):**

```json
{
  "email": "tester@kolabora.com",
  "password": "password123"
}
```

### B. Products

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/products` | Menampilkan seluruh daftar produk aktif |
| `POST` | `/api/products` | Menambahkan produk baru |
| `GET` | `/api/products/{id}` | Menampilkan detail produk berdasarkan ID |
| `PUT` | `/api/products/{id}` | Memperbarui data produk |
| `DELETE` | `/api/products/{id}` | Menghapus produk (*Soft Delete*) |

**Contoh Request Body (`POST /api/products` & `PUT /api/products/{id}`):**

```json
{
  "name": "Jaket Bomber Premium",
  "price": 350000
}
```

### C. Sales Orders

| Method | Endpoint | Deskripsi |
| :--- | :--- | :--- |
| `GET` | `/api/sales-orders` | Menampilkan daftar Sales Order beserta detail item |
| `POST` | `/api/sales-orders` | Membuat Sales Order baru (nomor order, subtotal, & total dihitung otomatis) |
| `GET` | `/api/sales-orders/{id}` | Menampilkan detail satu Sales Order |
| `PUT` | `/api/sales-orders/{id}` | Memperbarui status dan/atau daftar item Sales Order |
| `DELETE` | `/api/sales-orders/{id}` | Menghapus Sales Order (*Soft Delete*) |

**Contoh Request Body (`POST /api/sales-orders` & `PUT /api/sales-orders/{id}`):**

```json
{
  "items": [
    {
      "product_id": 1,
      "variant": "Bordir",
      "quantity": 2
    },
    {
      "product_id": 2,
      "variant": "Tanpa Bordir",
      "quantity": 1
    }
  ]
}
```

*(Pilihan nilai `variant` yang valid: `"Bordir"` atau `"Tanpa Bordir"`).*