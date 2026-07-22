# InviteMe System Design

## Arsitektur Aplikasi

InviteMe memakai monolith Laravel modular: Blade untuk UI, Alpine.js untuk interaksi ringan, Tailwind CSS untuk styling, Eloquent untuk data access, service class untuk alur bisnis, dan Neon PostgreSQL sebagai database utama. Modul utama dipisah menjadi katalog, cart, checkout, payment, order progress, preview/revision, review, dan admin.

Lapisan aplikasi:

- Route web: halaman customer dan admin.
- Controller: menerima request, validasi, dan mengarahkan ke service.
- Service: `CatalogPricingService`, `CartService`, `CheckoutService`, `MidtransService`, dan `ProgressService`.
- Model Eloquent: relasi database dan konstanta status.
- Blade components/layout: store layout dan admin layout.

## Referensi Visual

Gambar referensi memakai header tipis, grid-paper background, headline serif besar, badge pastel, card dengan radius kecil, border hitam tipis, panel pastel pink/biru/hijau, dan spacing lega. Implementasi memakai pola itu tanpa menyalin brand, logo, teks, atau aset: warna utama `ink`, `paper`, `sky`, `rose`, `sage`, layout editorial e-commerce, ticker strip, card produk reusable, modal detail, serta admin layout terpisah dengan sidebar padat.

## Halaman Dan Route

- `/`: homepage company profile dengan kategori dan katalog unggulan dari database.
- `/catalog`: grid katalog dengan search, filter kategori, rentang harga, sorting, pagination, modal detail.
- `/catalog/{slug}`: fallback detail katalog.
- `/cart`: keranjang guest/session atau customer.
- `/checkout`: ringkasan checkout dan pembuatan order.
- `/orders`: daftar pesanan customer.
- `/orders/{order}`: detail pesanan, progress, preview, revisi, pelunasan.
- `/orders/{order}/invoice`: invoice cetak.
- `/admin`: dashboard KPI.
- `/admin/categories`: CRUD kategori.
- `/admin/catalogs`: CRUD katalog dan data terkait.
- `/admin/orders`: manajemen order.
- `/payments/midtrans/webhook`: webhook Midtrans yang dikecualikan dari CSRF dan diverifikasi signature.

## Rancangan Database

Database berjalan di Neon PostgreSQL melalui koneksi Laravel `pgsql`. Gunakan `DB_URL` pooled connection string dengan `sslmode=require` untuk aplikasi web, dan branch Neon terpisah untuk development/testing agar `migrate:fresh --seed` tidak menyentuh production.

`users` memiliki role `customer` atau `admin`. `categories` memiliki banyak `catalogs`. `catalogs` memiliki `catalog_images`, `catalog_specifications`, `catalog_input_fields`, dan opsional `discount`. `carts` berisi `cart_items`. `orders` menyimpan snapshot transaksi melalui `order_items`, jawaban personalisasi di `order_input_values`, banyak `payments`, `order_progress_steps`, `order_revisions`, `reviews`, dan `order_activities`. `discount_usages` mencatat pemakaian diskon per order.

Status pembayaran: `unpaid`, `pending`, `partially_paid`, `paid`, `expired`, `failed`, `refunded`, `partially_refunded`.

Status pengerjaan: `received`, `in_progress`, `preview`, `revision`, `completed`, `cancelled`.

Jenis pembayaran: `deposit`, `final_payment`, `full_payment`.

## Alur Pemesanan

Customer memilih katalog, menambahkan ke cart, login atau registrasi saat checkout, mengisi data personalisasi per field produk, memilih bayar penuh atau DP, menyetujui syarat layanan, lalu order dibuat dalam database transaction. Harga selalu dihitung ulang dari backend dan snapshot katalog disimpan di `order_items`.

## Alur Pembayaran

Checkout membuat `payments` dengan tipe `deposit` atau `full_payment`, lalu `MidtransService` membuat Snap token. Frontend callback hanya menampilkan informasi. Status final datang dari webhook atau sinkronisasi backend. Webhook harus memverifikasi signature, mencocokkan nominal, mencatat payload, dan idempotent terhadap callback berulang.

## Alur Preview Dan Revisi

Admin mengisi URL preview dan token aman. Saat status `preview`, customer melihat preview `noindex` dengan watermark, lalu menyetujui atau mengajukan revisi. Revisi tersimpan di `order_revisions` dan mengubah status menjadi `revision`. Hasil final hanya dapat digunakan setelah preview disetujui dan order lunas.

## Tahapan Implementasi

1. Fondasi: auth sederhana, role, migration, model, factory, seeder, layout, dan dokumen desain.
2. Customer catalog: homepage, katalog, filter, detail modal, detail URL, cart.
3. Checkout: order, snapshot item, input personalisasi, diskon, invoice.
4. Midtrans: Snap QRIS, webhook, mapper status, idempotency.
5. Order portal: daftar/detail order, timeline, progress, preview, revisi, pelunasan.
6. Admin: dashboard, kategori, katalog, diskon, order, progress, review.
7. Hardening: automated test, responsive review, deployment docs, security review.
