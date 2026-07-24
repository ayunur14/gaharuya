# Deskripsi Teknis Aplikasi Gaharu

## Bahasa Pemrograman Sisi Klien (Frontend)

Tampilan yang dilihat dan digunakan oleh pengguna dibangun menggunakan tiga teknologi utama:

- **HTML** — Menyusun struktur setiap halaman, mulai dari daftar produk, keranjang belanja, hingga halaman pesanan.
- **CSS via Tailwind CSS** — Mengatur tampilan visual seperti warna, jarak, ukuran teks, dan tata letak agar halaman terlihat rapi di berbagai ukuran layar (responsif).
- **JavaScript** — Menambahkan interaktivitas pada halaman tanpa perlu memuat ulang. Secara spesifik menggunakan **Alpine.js** untuk fitur seperti modal beli, slider gambar produk, dan tombol tampilkan/sembunyikan detail pesanan.

---

## Bahasa Pemrograman Sisi Server (Backend)

Logika bisnis dan pemrosesan data di balik layar ditangani oleh:

- **PHP** versi 8.2 — Bahasa utama yang memproses setiap permintaan dari pengguna, mulai dari menampilkan produk, menyimpan pesanan, hingga berkomunikasi dengan layanan pembayaran.
- **Laravel** versi 12 — Framework PHP yang digunakan untuk mengatur routing (jalur URL), autentikasi pengguna, validasi data, dan interaksi dengan database secara lebih terstruktur dan efisien.

---

## Sistem Manajemen Basis Data

Data aplikasi disimpan dan dikelola menggunakan:

- **MySQL** — Database relasional yang menyimpan semua data penting seperti data pengguna, produk, dan riwayat pesanan.
- Dijalankan melalui **XAMPP** di lingkungan lokal (pengembangan), dan diakses oleh Laravel menggunakan Eloquent ORM sehingga pengelolaan data menjadi lebih mudah tanpa harus menulis query SQL secara langsung.

---

## Fitur Manajemen Produk

Fitur ini memungkinkan pengelolaan katalog produk yang dijual di toko. Secara lengkap, fitur ini mencakup:

- **Menambah produk baru** — Admin dapat menginput nama produk, deskripsi, harga, dan mengunggah hingga 5 foto produk sekaligus.
- **Mengedit produk** — Data produk yang sudah ada dapat diperbarui kapan saja, termasuk mengganti foto-foto yang sebelumnya diunggah.
- **Menghapus produk** — Produk yang tidak dijual lagi dapat dihapus, dan foto-fotonya otomatis ikut terhapus dari server.
- **Tampilan slider foto** — Di halaman utama, setiap produk menampilkan galeri foto yang bisa digeser (swipe/drag) untuk melihat semua gambar.
- **Akses terbatas** — Hanya pengguna dengan peran **Super Admin** yang bisa menambah, mengedit, dan menghapus produk. Pengunjung biasa hanya bisa melihat katalog.

---

## Fitur Keranjang Belanja

Fitur ini memudahkan pengguna mengumpulkan produk yang ingin dibeli sebelum melakukan pembayaran:

- **Tambah ke keranjang** — Pengguna bisa memilih produk dan menentukan jumlah yang ingin dibeli melalui tampilan modal (popup) yang muncul saat klik tombol "Beli".
- **Penyimpanan sesi** — Isi keranjang tersimpan di sesi browser, sehingga tidak hilang selama pengguna masih mengakses aplikasi.
- **Sinkronisasi ke database** — Bagi pengguna yang sudah login, isi keranjang juga disimpan ke database agar tidak hilang meski browser ditutup atau pengguna logout kemudian login kembali.
- **Manajemen item** — Pengguna bisa melihat semua produk di keranjang, mengubah jumlah, atau menghapus item sebelum melanjutkan ke checkout.
- **Wajib login untuk beli** — Pengguna yang belum login akan diarahkan ke halaman login terlebih dahulu saat mencoba menambahkan produk ke keranjang.

---

## Fitur Checkout & Integrasi Pembayaran

Fitur ini menangani proses pemesanan dan pembayaran secara menyeluruh:

- **Proses checkout** — Setelah puas dengan isi keranjang, pengguna bisa menambahkan catatan pesanan (opsional) lalu mengonfirmasi pesanan. Sistem otomatis menghitung total harga dan menyimpan rincian pesanan.
- **Integrasi Midtrans** — Pembayaran diproses menggunakan layanan **Midtrans Snap**, sebuah gateway pembayaran terpercaya yang menampilkan popup pilihan metode pembayaran langsung di halaman tanpa perlu berpindah ke halaman lain.
- **Berbagai metode pembayaran** — Pengguna dapat memilih dari banyak opsi pembayaran yang tersedia, antara lain:
  - Transfer Virtual Account (BCA, Mandiri, BNI, BRI, dan lainnya)
  - Dompet digital seperti GoPay dan ShopeePay
  - Kartu kredit/debit (Visa, Mastercard, JCB)
  - QRIS
- **Status pesanan otomatis** — Setelah pembayaran berhasil, status pesanan diperbarui secara otomatis melalui notifikasi webhook dari Midtrans, tanpa perlu konfirmasi manual.
- **Riwayat pesanan** — Pengguna dapat melihat seluruh riwayat pesanan beserta statusnya (Menunggu Pembayaran, Menunggu Konfirmasi, Lunas, atau Dibatalkan).
- **Kelola pesanan (Admin)** — Super Admin dapat melihat semua pesanan dari seluruh pengguna, menandai pesanan sebagai lunas secara manual, atau menghapus pesanan jika diperlukan.
