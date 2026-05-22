<?php
session_start();
require 'koneksi.php'; // WAJIB ada koneksi ke database

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

// 1. AMBIL DATA BARANG DARI DATABASE UNTUK DROPDOWN
$query_stok_barang = mysqli_query($conn, "SELECT * FROM stok_barang ORDER BY nama_barang ASC");
$options_barang = "";
while($s = mysqli_fetch_assoc($query_stok_barang)){
    // Kita simpan satuan dan stok di atribut data (data-satuan & data-stok) untuk diproses Javascript
    $options_barang .= "<option value='".$s['nama_barang']."' data-satuan='".$s['satuan']."' data-stok='".$s['stok']."'>".$s['nama_barang']." (Sisa Stok: ".$s['stok'].")</option>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Buat Surat Jalan - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7fa;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT --- */
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }

        /* --- DESAIN FORM --- */
        .form-container { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .form-section-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-blue); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--accent-yellow); display: inline-block; }
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e0e6ed; padding: 10px 15px; background-color: #fcfdfe; font-size: 0.9rem; }
        label { font-weight: 600; color: #4a5568; font-size: 0.85rem; margin-bottom: 8px; }
        .btn-submit-sultan { background: linear-gradient(135deg, var(--primary-blue) 0%, #2b59ff 100%); color: white; border: none; border-radius: 12px; padding: 12px 30px; font-weight: 600; transition: all 0.3s; }
        .btn-submit-sultan:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(43, 89, 255, 0.2); color: white; }
        
        /* Select2 Fix */
        .select2-container .select2-selection--single { height: 43px; border: 1.5px solid #e0e6ed; border-radius: 10px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 40px; }
        
        /* Input Readonly Custom */
        input[readonly].input-satuan { background-color: #f8fafc !important; color: #64748b; font-weight: 600; cursor: not-allowed; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 mb-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="fw-bold mb-0" style="color: #1a367e;">Input Surat Jalan Baru</h3>
                <a href="manajemen_dokumen.php" class="btn btn-sm btn-light border shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <p class="text-muted small mb-4">Pastikan seluruh data pengiriman diperiksa kembali sebelum diterbitkan.</p>

            <div class="form-container">
                <form action="proses_simpan_sj.php" method="POST">
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="form-section-title"><i class="fas fa-info-circle me-2"></i> Informasi Utama</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Nomor Surat Jalan</label>
                            <input type="text" name="no_sj" class="form-control form-control-sultan fw-bold text-primary bg-light" value="#SJ-<?php echo date('Ymd'); ?>-<?php echo rand(100,999); ?>" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Tanggal Pengiriman</label>
                            <input type="date" class="form-control form-control-sultan" name="tgl_kirim" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Prioritas</label>
                            <select class="form-select form-control-sultan" name="prioritas">
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent (Segera)</option>
                                <option value="High">High Priority</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-section-title"><i class="fas fa-truck me-2"></i> Armada & Driver</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Pilih Driver (Tersedia)</label>
                            <select class="form-control select2" name="driver_id" required>
                                <option value="">-- Cari Nama Driver --</option>
                                <?php
                                // Ambil driver yang statusnya AVAILABLE dari database
                                $q_driver = mysqli_query($conn, "SELECT driver.id, login.nama_lengkap, driver.lisensi_sim FROM driver JOIN login ON driver.id_login = login.id WHERE driver.status_kerja = 'AVAILABLE'");
                                while($d = mysqli_fetch_assoc($q_driver)) {
                                    echo "<option value='".$d['id']."'>".$d['nama_lengkap']." (".$d['lisensi_sim'].")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Nomor Polisi / Kendaraan</label>
                            <select class="form-control select2" name="kendaraan_id" required>
                                <option value="">-- Pilih Kendaraan --</option>
                                <?php
                                // Ambil data armada dari database
                                $q_mobil = mysqli_query($conn, "SELECT * FROM armada");
                                while($m = mysqli_fetch_assoc($q_mobil)) {
                                    $kategori = isset($m['kategori_kendaraan']) && !empty($m['kategori_kendaraan']) ? $m['kategori_kendaraan'] : 'Kategori -';
                                    echo "<option value='".$m['id']."'>".$m['nopol']." - ".$m['tipe']." (".$kategori.")</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i> Detail Tujuan</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Nama Penerima / Perusahaan</label>
                            <input type="text" class="form-control form-control-sultan" placeholder="Contoh: PT. Maju Jaya Bersama" name="penerima" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Kota Tujuan</label>
                            <input type="text" class="form-control form-control-sultan" placeholder="Contoh: Surabaya" name="kota_tujuan" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Rute Pengiriman</label>
                            <select class="form-select form-control-sultan" name="id_route" required>
                                <option value="" disabled selected>-- Pilih Rute --</option>
                                <?php
                                $q_rute = mysqli_query($conn, "SELECT * FROM routes WHERE tipe_rute='PERGI' AND is_active=1");
                                while($r = mysqli_fetch_assoc($q_rute)) {
                                    echo "<option value='".$r['id']."'>".$r['nama_rute']." (".number_format($r['total_jarak_km'],1)." km - Rp ".number_format($r['total_biaya_rute'],0,',','.').")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label>Alamat Lengkap Bongkar</label>
                            <textarea class="form-control form-control-sultan" rows="3" placeholder="Masukkan alamat detail pergudangan..." name="alamat_tujuan" required></textarea>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="form-section-title"><i class="fas fa-box-open me-2"></i> Kargo / Muatan</div>
                            <div class="alert alert-info small py-2 mb-3"><i class="fas fa-info-circle me-1"></i> Pilih barang dari gudang. Satuan akan terisi otomatis.</div>
                        </div>
                        <div class="col-md-12">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle" style="border-radius: 10px; overflow: hidden; border: 1.5px solid #e0e6ed;">
                                    <thead class="bg-light">
                                        <tr class="small text-uppercase text-muted">
                                            <th>Pilih Barang (Dari Gudang)</th>
                                            <th width="120">Jumlah</th>
                                            <th width="150">Satuan</th>
                                            <th>Keterangan</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabel-kargo">
                                        <tr>
                                            <td>
                                                <select class="form-select border-0 bg-transparent shadow-none select-barang" name="nama_barang[]" required>
                                                    <option value="">-- Pilih Barang --</option>
                                                    <?php echo $options_barang; ?>
                                                </select>
                                            </td>
                                            <td><input type="number" class="form-control border-0 bg-transparent shadow-none" placeholder="0" name="jumlah[]" required min="1"></td>
                                            <td>
                                                <input type="text" class="form-control border-0 bg-transparent shadow-none input-satuan text-center" name="satuan[]" readonly placeholder="-">
                                            </td>
                                            <td><input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="Catatan tambahan..." name="keterangan[]"></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" id="btn-tambah-baris" class="btn btn-sm btn-light border text-primary mb-3 fw-medium">
                                    <i class="fas fa-plus me-1"></i> Tambah Baris Barang
                                </button>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4 opacity-25">

                    <div class="d-flex justify-content-end align-items-center">
                        <button type="reset" class="btn btn-link text-muted me-3 text-decoration-none">Reset Form</button>
                        <button type="submit" class="btn btn-submit-sultan">
                            <i class="fas fa-paper-plane me-2"></i> TERBITKAN SURAT JALAN
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inisialisasi Select2 untuk form Armada & Driver
            $('.select2').select2({ width: '100%' });

            // Simpan data option barang ke dalam variabel Javascript
            var optBarang = `<?php echo $options_barang; ?>`;

            // FUNGSI TAMBAH BARIS BARANG
            $('#btn-tambah-baris').click(function() {
                var barisBaru = `
                <tr>
                    <td>
                        <select class="form-select border-0 bg-transparent shadow-none select-barang" name="nama_barang[]" required>
                            <option value="">-- Pilih Barang --</option>
                            ${optBarang}
                        </select>
                    </td>
                    <td><input type="number" class="form-control border-0 bg-transparent shadow-none" placeholder="0" name="jumlah[]" required min="1"></td>
                    <td>
                        <input type="text" class="form-control border-0 bg-transparent shadow-none input-satuan text-center" name="satuan[]" readonly placeholder="-">
                    </td>
                    <td><input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="Catatan tambahan..." name="keterangan[]"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm text-danger btn-hapus-baris"><i class="fas fa-times"></i></button>
                    </td>
                </tr>`;
                $('#tabel-kargo').append(barisBaru);
            });

            // FUNGSI OTOMATIS ISI SATUAN SAAT BARANG DIPILIH
            $(document).on('change', '.select-barang', function() {
                // Ambil data satuan dari option yang di-select
                var satuan = $(this).find(':selected').data('satuan');
                
                // Jika ada satuan, masukkan ke input readonly di baris yang sama. Jika tidak, kosongkan.
                if(satuan !== undefined) {
                    $(this).closest('tr').find('.input-satuan').val(satuan);
                } else {
                    $(this).closest('tr').find('.input-satuan').val('');
                }
            });

            // FUNGSI HAPUS BARIS
            $(document).on('click', '.btn-hapus-baris', function() {
                $(this).closest('tr').remove();
            });
        });
    </script>
</body>
</html>