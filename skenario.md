# Jawaban skenario tambahan yang perlu diperhatikan:

## 1. Bagaimana jika produk tidak ditemukan?
* Sistem memeriksa keberadaan data menggunakan `find($id)` pada `ProductController`.
* Jika data tidak ditemukan (atau sudah terhapus melalui *soft delete*), API mengembalikan response JSON dengan HTTP Status **`404 Not Found`**:
  ```json
  {
      "success": false,
      "message": "Produk tidak ditemukan"
  }
  ```
## 2. Bagaimana jika satu item memiliki variant yang tidak valid?
* Sudah ada validasi **`'items.*.variant' => 'required|in:Bordir,Tanpa Bordir'`**.
* Kalau ada satu item yang kirim nilai selain **`Bordir`** atau **`Tanpa Bordir`**, semua request direject sebelum masuk ke DB, dan ada return response status **`422 Unprocessable Entity`** dengan error message validasinya.
* Di level DB udh ada lock juga untuk kolom **`variant`** di tabel **`sales_order_items`** yang dilock pakai tipe **`enum('variant', ['Bordir', 'Tanpa Bordir'])`**.

## 3. Bagaimana jika proses penyimpanan detail gagal?
* Kalau gagal saat save atau menyimpan salah satu item detail, database bisa otomatis. **`ROLLBACK`** (cancel penyimpanan data induk sales_orders), jadi tidak ada data transaksi yang setengah-setengah yang tersimpan. Tersedia juga **`try-catch`** untuk return status **`500 Internal Server Error`**.

## 4. Bagaimana jika dua request membuat order secara bersamaan?
* Sudah ada function **`lockForUpdate()`** di dalam **`DB::transaction()`** yang fungsinya untuk mengunci proses pembacaan nomor urut terakhir yang masuk.
```PHP
      $lastOrder = SalesOrder::withTrashed()
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->latest('id')
            ->first();
```
* Request kedua akan menunggu sampai request pertama selesai menyimpan data lalu melepas lock row database sehingga nomor SO nya **`(SO-YYYYMMDD-XXXX)`** tetap berurutan dan unique.

## 5. Bagaimana jika produk dihapus tetapi masih digunakan oleh Sales Order?
* Produk sudah memiliki fitur **`SoftDeletes`** sehingga saat produk dihapus melalui API **`DELETE /api/products/{id}`**, data produk tidak hilang secara permanen dan hanya mengisi kolom **`deleted_at`**.
* Data SO tetap aman karena relasi **`product()`** pada **`SalesOrderItem`** menggunakan **`->withTrashed()`**, yang juga harga satuan produk disimpan pada kolom **`sales_order_items.price`**, oleh sebab itu data SO lama tetap menampilkan nama dan harga produk secara lengkap walaupun produknya sudah dihapus
* Di level DB, **`Foreign Key atau FK product_id`** pada **`sales_order_items`** memakai **`onDelete('restrict')`** agar tidak bisa terjadi force delete atau delete permanen di level DB selama produk masih terhubung dengan transaksi.