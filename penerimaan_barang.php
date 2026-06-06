<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}
$role = isset($_SESSION['role']) ? strtoupper($_SESSION['role']) : 'DRIVER';
if ($role == 'DRIVER') {
    header('location:dashboard_driver.php');
    exit();
}

$nama_user = $_SESSION['nama_lengkap'] ?? 'Sistem';

// --- PROSES SIMPAN PENERIMAAN ---
if (isset($_POST['simpan_penerimaan'])) {
    if ($role != 'ADMIN') {
        echo "<script>alert('Akses Ditolak! Hanya Admin yang dapat memproses penerimaan barang.'); window.location.href='penerimaan_barang.php';</script>";
        exit();
    }
    $id_barang_arr = $_POST['id_barang'] ?? [];
    $jumlah_arr    = $_POST['jumlah'] ?? [];
    $keterangan_arr = $_POST['keterangan'] ?? [];

    $sukses = 0;
    for ($i = 0; $i < count($id_barang_arr); $i++) {
        $id_brg = (int)$id_barang_arr[$i];
        $jml    = (int)$jumlah_arr[$i];
        $ket    = mysqli_real_escape_string($conn, $keterangan_arr[$i] ?? '');
        $user   = mysqli_real_escape_string($conn, $nama_user);

        if ($id_brg <= 0 || $jml <= 0) continue;

        // Ambil info barang
        $q = mysqli_query($conn, "SELECT sku, nama_barang, satuan FROM stok_barang WHERE id='$id_brg'");
        $brg = mysqli_fetch_assoc($q);
        if (!$brg) continue;

        $sku  = mysqli_real_escape_string($conn, $brg['sku']);
        $nama = mysqli_real_escape_string($conn, $brg['nama_barang']);
        $sat  = mysqli_real_escape_string($conn, $brg['satuan']);

        // Insert riwayat penerimaan
        mysqli_query($conn, "INSERT INTO penerimaan_barang (id_barang, sku, nama_barang, jumlah, satuan, keterangan, diterima_oleh) VALUES ('$id_brg', '$sku', '$nama', '$jml', '$sat', '$ket', '$user')");

        // Update stok barang (+)
        mysqli_query($conn, "UPDATE stok_barang SET stok = stok + $jml WHERE id='$id_brg'");

        $sukses++;
    }

    if ($sukses > 0) {
        echo "<script>alert('$sukses item berhasil diterima dan stok diperbarui!'); window.location.href='penerimaan_barang.php';</script>";
    } else {
        echo "<script>alert('Tidak ada data yang valid untuk disimpan.');</script>";
    }
}

// Ambil data barang untuk scanner lookup
$q_barang = mysqli_query($conn, "SELECT * FROM stok_barang ORDER BY nama_barang ASC");
$json_barang = [];
$options_barang = "";
while ($s = mysqli_fetch_assoc($q_barang)) {
    $options_barang .= "<option value='" . $s['id'] . "' data-satuan='" . $s['satuan'] . "' data-nama='" . htmlspecialchars($s['nama_barang']) . "'>" . htmlspecialchars($s['nama_barang']) . " (Stok: " . $s['stok'] . ")</option>";
    if (!empty($s['sku'])) {
        $json_barang[$s['sku']] = [
            'id' => $s['id'],
            'nama_barang' => $s['nama_barang'],
            'satuan' => $s['satuan'],
            'stok' => $s['stok']
        ];
    }
}
$json_str = json_encode($json_barang);

// Ambil riwayat penerimaan (50 terakhir)
$q_history = mysqli_query($conn, "SELECT * FROM penerimaan_barang ORDER BY created_at DESC LIMIT 50");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Penerimaan Barang - PT Elektronik Global</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

    <style>
        :root {
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        .form-container { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
        .form-section-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-blue); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--accent-yellow); display: inline-block; }
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e0e6ed; padding: 10px 15px; background-color: #fcfdfe; font-size: 0.9rem; }
        .scanner-box { background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border: 2px dashed #93c5fd; border-radius: 16px; padding: 28px; }
        .history-card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 mb-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                    <h3 class="fw-bold mb-1" style="color: #1a367e;"><i class="fas fa-dolly me-2"></i>Penerimaan Barang (Inbound)</h3>
                    <p class="text-muted small mb-0">Terima barang dari supplier dan tambahkan stok ke gudang. Scan barcode atau pilih secara manual.</p>
                </div>
            </div>
            <?php if ($role != 'ADMIN'): ?>
            <div class="alert alert-warning border-0 shadow-sm mb-4" style="border-radius:12px;">
                <i class="fas fa-exclamation-triangle me-2"></i><strong>Mode Lihat Saja:</strong> Akun Anda (Manager) hanya dapat melihat riwayat penerimaan barang dan tidak diizinkan untuk menginput penerimaan barang baru.
            </div>
            <?php endif; ?>

            <!-- SCANNER SECTION -->
            <div class="form-container mb-4">
                <form method="POST" id="form-penerimaan">
                    <input type="hidden" name="simpan_penerimaan" value="1">

                    <div class="scanner-box mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-7 mb-3 mb-md-0">
                                <label class="fw-bold text-dark small mb-2"><i class="fas fa-barcode text-primary me-1"></i> Scan Barcode / Ketik SKU</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="barcode-input" class="form-control form-control-sultan border-start-0 ps-0" placeholder="Arahkan scanner atau ketik SKU lalu tekan Enter..." style="height: 50px; font-size: 1.1rem; border-radius: 0 10px 10px 0;">
                                </div>
                                <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i> Barang akan otomatis masuk ke tabel penerimaan di bawah.</small>
                            </div>
                            <div class="col-md-5 text-md-end text-center">
                                <p class="small fw-bold text-muted mb-2">Kamera HP/Laptop (Online)</p>
                                <button type="button" id="btn-kamera" class="btn btn-outline-primary fw-bold bg-white" style="border-radius: 10px; padding: 10px 20px;">
                                    <i class="fas fa-camera me-2"></i> Buka Kamera Scanner
                                </button>
                            </div>
                            <div class="col-12 mt-3 text-center" id="reader-container" style="display: none;">
                                <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto; border-radius: 12px; overflow: hidden; border: 2px solid #3b82f6;"></div>
                                <button type="button" id="btn-tutup-kamera" class="btn btn-sm btn-danger mt-3 fw-bold rounded-pill"><i class="fas fa-times me-1"></i> Tutup Kamera</button>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL PENERIMAAN -->
                    <div class="form-section-title"><i class="fas fa-clipboard-list me-2"></i> Daftar Barang Diterima</div>
                    <div class="table-responsive mb-3">
                        <table class="table align-middle" style="border-radius: 10px; overflow: hidden; border: 1.5px solid #e0e6ed;">
                            <thead class="bg-light">
                                <tr class="small text-uppercase text-muted">
                                    <th>Barang</th>
                                    <th width="120">Jumlah</th>
                                    <th width="100">Satuan</th>
                                    <th>Keterangan</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody id="tabel-terima">
                                <tr>
                                    <td>
                                        <select class="form-select border-0 bg-transparent shadow-none select-barang" name="id_barang[]" required>
                                            <option value="">-- Pilih Barang --</option>
                                            <?php echo $options_barang; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" class="form-control border-0 bg-transparent shadow-none" placeholder="0" name="jumlah[]" required min="1"></td>
                                    <td>
                                        <input type="text" class="form-control border-0 bg-transparent shadow-none text-center input-satuan" name="satuan_display[]" readonly placeholder="-">
                                    </td>
                                    <td><input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="No. PO / Supplier..." name="keterangan[]"></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" id="btn-tambah-baris" class="btn btn-sm btn-light border text-primary fw-medium">
                            <i class="fas fa-plus me-1"></i> Tambah Baris Manual
                        </button>
                    </div>

                    <hr class="my-4 opacity-25">

                    <div class="d-flex justify-content-end align-items-center">
                        <button type="reset" class="btn btn-link text-muted me-3 text-decoration-none">Reset Form</button>
                        <?php if ($role == 'ADMIN'): ?>
                        <button type="submit" class="btn fw-bold text-white" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; padding: 12px 30px;">
                            <i class="fas fa-check-circle me-2"></i> KONFIRMASI PENERIMAAN
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary fw-bold text-white" disabled style="border-radius: 12px; padding: 12px 30px;">
                            <i class="fas fa-lock me-2"></i> KONFIRMASI PENERIMAAN (DITOLAK)
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- RIWAYAT PENERIMAAN -->
            <div class="history-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-section-title mb-0"><i class="fas fa-history me-2"></i> Riwayat Penerimaan</div>
                    <span class="badge bg-primary bg-opacity-10 text-primary" style="border-radius:8px; padding:6px 12px; font-weight:700; font-size:0.75rem;">
                        50 Transaksi Terakhir
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tabelRiwayat">
                        <thead>
                            <tr class="text-muted small">
                                <th>Tanggal</th>
                                <th>SKU</th>
                                <th>Nama Barang</th>
                                <th>Jumlah</th>
                                <th>Satuan</th>
                                <th>Keterangan</th>
                                <th>Diterima Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($h = mysqli_fetch_assoc($q_history)): ?>
                            <tr>
                                <td><small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])); ?></small></td>
                                <td><span class="badge bg-light text-secondary border" style="font-family: monospace;"><?= htmlspecialchars($h['sku']); ?></span></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($h['nama_barang']); ?></td>
                                <td><span class="fw-bold text-success fs-6">+<?= $h['jumlah']; ?></span></td>
                                <td><?= htmlspecialchars($h['satuan']); ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($h['keterangan'] ?: '-'); ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= htmlspecialchars($h['diterima_oleh']); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
    $(document).ready(function() {
        $('#tabelRiwayat').DataTable({
            "language": { "search": "Cari:", "lengthMenu": "Tampil _MENU_" },
            "order": [[0, "desc"]]
        });

        var optBarang = `<?php echo $options_barang; ?>`;
        var dbBarang = <?php echo $json_str; ?>;

        // FUNGSI PROSES BARCODE
        function prosesBarcode(sku) {
            sku = sku.trim().toUpperCase();
            if (sku === "") return;

            if (dbBarang[sku]) {
                var data = dbBarang[sku];

                // Cek apakah sudah ada di tabel
                var found = false;
                $('#tabel-terima tr').each(function() {
                    var select = $(this).find('.select-barang');
                    if (select.val() == data.id) {
                        var inputQty = $(this).find('input[name="jumlah[]"]');
                        var currQty = parseInt(inputQty.val()) || 0;
                        inputQty.val(currQty + 1);
                        found = true;
                        $(this).css({'transition': 'background-color 0.3s', 'background-color': '#d1fae5'});
                        setTimeout(() => { $(this).css('background-color', 'transparent'); }, 500);
                        return false;
                    }
                });

                if (!found) {
                    var firstRow = $('#tabel-terima tr:first');
                    var firstSelect = firstRow.find('.select-barang').val();
                    if ($('#tabel-terima tr').length === 1 && (firstSelect === "" || firstSelect === null)) {
                        firstRow.find('.select-barang').val(data.id).trigger('change');
                        firstRow.find('input[name="jumlah[]"]').val(1);
                    } else {
                        var barisBaru = `
                        <tr>
                            <td>
                                <select class="form-select border-0 bg-transparent shadow-none select-barang" name="id_barang[]" required>
                                    <option value="">-- Pilih Barang --</option>
                                    ${optBarang}
                                </select>
                            </td>
                            <td><input type="number" class="form-control border-0 bg-transparent shadow-none" placeholder="0" name="jumlah[]" required min="1" value="1"></td>
                            <td>
                                <input type="text" class="form-control border-0 bg-transparent shadow-none text-center input-satuan" name="satuan_display[]" readonly placeholder="-" value="${data.satuan}">
                            </td>
                            <td><input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="No. PO / Supplier..." name="keterangan[]"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm text-danger btn-hapus-baris"><i class="fas fa-times"></i></button>
                            </td>
                        </tr>`;
                        var newRow = $(barisBaru);
                        $('#tabel-terima').append(newRow);
                        newRow.find('.select-barang').val(data.id).trigger('change');
                    }
                }

                // SFX Beep
                try {
                    var audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
                    audio.volume = 0.5;
                    audio.play();
                } catch(e) {}

            } else {
                alert('Barang dengan SKU [' + sku + '] tidak ditemukan di Master Barang!\nPastikan barang sudah didaftarkan.');
            }
        }

        // SCANNER USB INPUT
        $('#barcode-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                prosesBarcode($(this).val());
                $(this).val('');
            }
        });

        // KAMERA SCANNER (ONLINE)
        var html5QrcodeScanner;
        $('#btn-kamera').click(function() {
            $('#reader-container').slideDown();
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 150} }, false);
                html5QrcodeScanner.render(function(decodedText) {
                    prosesBarcode(decodedText);
                    html5QrcodeScanner.pause(true);
                    setTimeout(() => { html5QrcodeScanner.resume(); }, 1500);
                }, function(err) { /* ignore */ });
            }
        });
        $('#btn-tutup-kamera').click(function() {
            $('#reader-container').slideUp();
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
            }
        });

        // TAMBAH BARIS MANUAL
        $('#btn-tambah-baris').click(function() {
            var barisBaru = `
            <tr>
                <td>
                    <select class="form-select border-0 bg-transparent shadow-none select-barang" name="id_barang[]" required>
                        <option value="">-- Pilih Barang --</option>
                        ${optBarang}
                    </select>
                </td>
                <td><input type="number" class="form-control border-0 bg-transparent shadow-none" placeholder="0" name="jumlah[]" required min="1"></td>
                <td>
                    <input type="text" class="form-control border-0 bg-transparent shadow-none text-center input-satuan" name="satuan_display[]" readonly placeholder="-">
                </td>
                <td><input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="No. PO / Supplier..." name="keterangan[]"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm text-danger btn-hapus-baris"><i class="fas fa-times"></i></button>
                </td>
            </tr>`;
            $('#tabel-terima').append(barisBaru);
        });

        // AUTO-FILL SATUAN
        $(document).on('change', '.select-barang', function() {
            var satuan = $(this).find(':selected').data('satuan');
            $(this).closest('tr').find('.input-satuan').val(satuan || '');
        });

        // HAPUS BARIS
        $(document).on('click', '.btn-hapus-baris', function() {
            $(this).closest('tr').remove();
        });
    });
    </script>
</body>
</html>
