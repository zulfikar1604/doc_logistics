# Panduan Akses & Hak Akses Pengguna (RBAC Matrix)
**PT Elektronik Global Logistics**

Dokumen ini menjelaskan pembagian hak akses (role) dan aturan keamanan yang diterapkan pada aplikasi **DocLogistics** agar Anda tidak lupa.

---

## 🛡️ Ringkasan Hak Akses (Role Matrix)

Aplikasi memiliki 3 tipe pengguna dengan tingkat wewenang yang berbeda:

| Halaman / Fitur | File PHP | Admin | Manager | Driver | Deskripsi / Proteksi Sistem |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Manajemen User** | `user_management.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa daftar/edit/resign user.<br>**Manager**: Hanya bisa melihat daftar user.<br>**Driver**: Diblokir total (Redirect). |
| **Arsip Karyawan Resign** | `arsip_karyawan_resign.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin & Manager** bisa melihat arsip mantan karyawan.<br>**Driver**: Diblokir total. |
| **Data Armada / Kendaraan** | `data_armada.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa tambah/edit/hapus armada.<br>**Manager**: Hanya bisa melihat list.<br>**Driver**: Diblokir total. |
| **Data Driver** | `data_driver.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa tambah/edit/hapus driver.<br>**Manager**: Hanya bisa melihat list.<br>**Driver**: Diblokir total. |
| **Master Checkpoint (Kota)**| `master_points.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa tambah/edit/hapus kota.<br>**Manager**: Hanya bisa melihat list & peta.<br>**Driver**: Diblokir total. |
| **Manajemen Rute** | `manajemen_rute.php`<br>`proses_rute.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa buat & edit rute.<br>**Manager**: Hanya bisa melihat list rute.<br>**Driver**: Diblokir total. |
| **Stok Barang (Master)** | `stok_barang.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa daftarkan barang baru.<br>**Manager**: Hanya bisa melihat stok & cetak barcode.<br>**Driver**: Diblokir total. |
| **Penerimaan Barang** | `penerimaan_barang.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa konfirmasi penerimaan barang baru.<br>**Manager**: Hanya melihat riwayat penerimaan.<br>**Driver**: Diblokir total. |
| **Kategori Barang** | `kategori_barang.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Kelola kategori barang.<br>**Manager**: Hanya melihat list kategori.<br>**Driver**: Diblokir total. |
| **Buat Surat Jalan** | `buat_surat_jalan.php`<br>`proses_simpan_sj.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa terbitkan surat jalan baru.<br>**Manager**: Hanya melihat form (tombol terbit terkunci).<br>**Driver**: Diblokir total. |
| **Arsip & Detail Invoice** | `manajemen_invoice.php`<br>`cetak_invoice.php` | **✓** (Write) | **✓** (Read-Only) | ✗ | **Admin**: Bisa cetak & hapus invoice.<br>**Manager**: Bisa cetak tetapi tombol hapus dikunci.<br>**Driver**: Diblokir total. |
| **Konfirmasi Pembayaran** | `proses_bayar_invoice.php`<br>`invoice_pengiriman.php`| **✓** (Write) | **✓** (Write) | ✗ | **Admin & Manager** diperbolehkan menandai invoice menjadi **PAID (Lunas)**.<br>**Driver**: Diblokir total. |
| **Arsip Surat Jalan** | `manajemen_dokumen.php` | **✓** (Read) | **✓** (Read) | **✓** (Filtered) | **Admin & Manager**: Melihat semua dokumen.<br>**Driver**: Hanya melihat dokumen miliknya sendiri. |
| **Dashboard Driver** | `dashboard_driver.php` | ✗ | ✗ | **✓** (Write) | Khusus Driver untuk melihat tugas trip aktif & update status. |
| **Dashboard Utama** | `dashboard_utama.php` | **✓** | **✓** | ✗ | Menampilkan grafik dan data keuangan untuk Admin & Manager. |

---

## 🔒 Aturan Keamanan & Logika Bisnis Penting

Berikut adalah beberapa aturan backend otomatis yang telah disematkan untuk menjaga integritas data Anda:

1. **Pencegahan Pembayaran Ganda (Double Payment Block)**
   - Sistem akan memeriksa status invoice terlebih dahulu sebelum menandai sebagai lunas. Jika status sudah `PAID`, sistem akan langsung menolak proses ulang untuk menghindari bug pencatatan keuangan ganda.

2. **Integritas Penghapusan Driver & Armada**
   - Driver atau Armada kendaraan **tidak dapat dihapus** jika mereka sedang ditugaskan dalam pengiriman aktif (`ON TRIP`, `RETURNING`, `DELIVERED`, atau `DRAFT`). Ini mencegah rusaknya data relasi riwayat pengiriman.

3. **Autofill & Validasi Stok**
   - Saat membuat Surat Jalan, jumlah barang yang diinput akan divalidasi dengan sisa stok di gudang secara otomatis (stok tidak boleh negatif).

4. **Multi-Role Notification Broadcast**
   - Fitur notifikasi sistem (`buat_notifikasi`) mendukung pengiriman pesan massal sekaligus (contoh: `'MANAGER,ADMIN'`), sehingga Manager dan Admin mendapatkan info real-time secara bersamaan.

5. **Proteksi Akses URL Langsung**
   - Jika pengguna non-Admin mencoba mengakses file proses backend secara langsung (misal: mengetik `proses_simpan_sj.php` di address bar), sistem akan memblokir dan mengarahkan kembali (redirect) ke halaman yang aman dengan pesan peringatan.
