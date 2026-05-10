<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Buat Invoice Pengiriman - Global Logistics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-blue: #1a367e;
            --accent-yellow: #ffcc00;
            --bg-light: #f4f7f6;
        }

        body { font-family: 'Poppins', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }

        /* --- CSS WAJIB LAYOUT ANTI TABRAKAN --- */
        #sidebar {
            width: var(--sidebar-width); position: fixed; top: 0; left: 0; height: 100vh;
            background: linear-gradient(180deg, var(--primary-blue) 0%, #0d1b3e 100%);
            z-index: 999; display: flex; flex-direction: column;
        }
        #main-content { margin-left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); min-height: 100vh; }
        .sidebar-header { padding: 25px 20px; background: rgba(0,0,0,0.15); border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-menu { padding: 20px 0; flex-grow: 1; overflow-y: auto; }
        .menu-label { color: rgba(255,255,255,0.4); font-size: 0.7rem; font-weight: 700; letter-spacing: 1px; padding: 0 25px; margin-bottom: 10px; display: block; }
        #sidebar ul li a { padding: 12px 25px; color: rgba(255,255,255,0.7); text-decoration: none; display: block; font-size: 0.85rem; transition: 0.3s; }
        #sidebar ul li a:hover, #sidebar ul li.active > a { color: #fff; background: rgba(255,255,255,0.1); border-left: 4px solid var(--accent-yellow); }
        #sidebar ul li a i { width: 25px; text-align: center; }
        .submenu a { padding-left: 50px !important; font-size: 0.8rem !important; }
        .sidebar-footer { padding: 20px 25px; background: rgba(0,0,0,0.2); }
        .top-navbar { background: white; padding: 15px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 998; }

        /* --- DESAIN INVOICE SULTAN --- */
        .invoice-card { background: #ffffff; border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.03); overflow: hidden; border: 1px solid rgba(0,0,0,0.05); }
        .invoice-header { background: linear-gradient(45deg, var(--primary-blue) 0%, #162d66 100%); padding: 40px; color: white; }
        .section-title { font-weight: 700; color: var(--primary-blue); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1.5px; margin-bottom: 15px; display: block; }
        
        .form-control-sultan { border-radius: 10px; border: 1.5px solid #e2e8f0; padding: 12px 15px; background-color: #f8fafc; transition: all 0.2s; font-size: 0.9rem;}
        .form-control-sultan:focus { border-color: var(--primary-blue); background-color: #ffffff; box-shadow: 0 0 0 4px rgba(26, 54, 126, 0.05); }
        
        /* Table Styling */
        .table-invoice thead th { background-color: #f8fafc; border: none; color: #475569; font-weight: 600; padding: 15px; font-size: 0.85rem; text-transform: uppercase;}
        .table-invoice tbody td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        /* Totals Area */
        .totals-box { background-color: #f8fafc; border-radius: 15px; padding: 25px; border: 1.5px solid #e2e8f0;}
        .grand-total { font-size: 1.5rem; font-weight: 800; color: var(--primary-blue); }
        
        /* Button Action */
        .btn-print { background: #10b981; color: white; border-radius: 10px; padding: 12px 30px; font-weight: 600; border: none; transition: all 0.3s; }
        .btn-print:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); color: white; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div id="main-content">
        <?php include 'navbar.php'; ?>

        <div class="container-fluid p-4 mb-5">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h3 class="fw-bold mb-0" style="color: #1a367e;">Invoice Pengiriman</h3>
                <a href="manajemen_invoice.php" class="btn btn-sm btn-light border shadow-sm" style="border-radius: 8px;">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <p class="text-muted small mb-4">Terbitkan tagihan biaya logistik secara profesional.</p>

            <div class="invoice-card">
                <div class="invoice-header d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2 class="mb-0 fw-bold">INVOICE</h2>
                        <p class="mb-0 opacity-75 small">Global Logistics Solution</p>
                    </div>
                    <div class="text-end">
                        <h5 class="mb-0 fw-bold">#INV-<?php echo date('Ymd'); ?>-<?php echo rand(100, 999); ?></h5>
                        <p class="mb-0 opacity-75 small">Tanggal: <?php echo date('d F Y'); ?></p>
                    </div>
                </div>

                <form action="proses_invoice.php" method="POST">
                    <div class="p-4 p-md-5">
                        <div class="row mb-5">
                            <div class="col-md-6 mb-4">
                                <span class="section-title"><i class="fas fa-building me-2"></i> Ditujukan Kepada:</span>
                                <div class="form-group">
                                    <input type="text" name="nama_klien" class="form-control form-control-sultan mb-3" placeholder="Nama Perusahaan / Client" required>
                                    <textarea name="alamat_klien" class="form-control form-control-sultan" rows="3" placeholder="Alamat Lengkap Client" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <span class="section-title"><i class="fas fa-credit-card me-2"></i> Metode Pembayaran:</span>
                                <select name="metode_bayar" class="form-select form-control-sultan mb-3" required>
                                    <option value="BCA">Transfer Bank - BCA</option>
                                    <option value="Mandiri">Transfer Bank - Mandiri</option>
                                    <option value="COD">Cash On Delivery (COD)</option>
                                </select>
                                <label class="small fw-bold text-muted mb-2">Jatuh Tempo:</label>
                                <input type="date" name="jatuh_tempo" class="form-control form-control-sultan" required>
                            </div>
                        </div>

                        <span class="section-title"><i class="fas fa-list me-2"></i> Rincian Biaya Logistik</span>
                        <div class="table-responsive mb-4">
                            <table class="table table-invoice align-middle">
                                <thead>
                                    <tr>
                                        <th>Deskripsi Layanan (Surat Jalan / Rute)</th>
                                        <th width="200">Tarif / Kg / Unit</th>
                                        <th width="150">Qty</th>
                                        <th width="200" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="tabel-invoice-body">
                                    <tr>
                                        <td><input type="text" name="deskripsi[]" class="form-control border-0 bg-transparent shadow-none" placeholder="Pengiriman JKT-SBY (#SJ-001)" required></td>
                                        <td><input type="number" name="tarif[]" class="form-control border-0 bg-transparent shadow-none tarif-input" placeholder="0" required></td>
                                        <td><input type="number" name="qty[]" class="form-control border-0 bg-transparent shadow-none qty-input" value="1" required></td>
                                        <td class="fw-bold text-primary text-end item-subtotal">Rp 0</td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" id="btn-tambah-item" class="btn btn-sm btn-light border text-primary fw-medium">
                                <i class="fas fa-plus me-1"></i> Tambah Item Biaya
                            </button>
                        </div>

                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="totals-box shadow-sm">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small fw-bold">Subtotal</span>
                                        <span class="fw-bold" id="label-subtotal">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small fw-bold">Pajak (PPN 11%)</span>
                                        <span class="fw-bold" id="label-pajak">Rp 0</span>
                                    </div>
                                    <hr class="opacity-25">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h6 mb-0 fw-bold">TOTAL TAGIHAN</span>
                                        <span class="grand-total" id="label-grandtotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-5 opacity-25">

                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <p class="small text-muted mb-3 mb-md-0"><i class="fas fa-shield-alt me-1"></i> Invoice ini sah diterbitkan secara digital oleh sistem.</p>
                            <div>
                                <button type="reset" class="btn btn-link text-muted me-3 text-decoration-none">Batal</button>
                                <button type="submit" class="btn btn-print shadow-sm">
                                    <i class="fas fa-save me-2"></i> SIMPAN & CETAK INVOICE
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // 1. Fungsi Tambah Baris Biaya
        $('#btn-tambah-item').on('click', function() {
            var barisBaru = `
            <tr>
                <td><input type="text" name="deskripsi[]" class="form-control border-0 bg-transparent shadow-none" placeholder="Biaya Tambahan..." required></td>
                <td><input type="number" name="tarif[]" class="form-control border-0 bg-transparent shadow-none tarif-input" placeholder="0" required></td>
                <td><input type="number" name="qty[]" class="form-control border-0 bg-transparent shadow-none qty-input" value="1" required></td>
                <td class="fw-bold text-primary text-end item-subtotal">Rp 0</td>
            </tr>`;
            $('#tabel-invoice-body').append(barisBaru);
        });

        // 2. Fungsi Hitung Otomatis (Event Listener)
        $(document).on('input', '.tarif-input, .qty-input', function() {
            hitungInvoice();
        });

        function hitungInvoice() {
            var totalKotor = 0;

            $('#tabel-invoice-body tr').each(function() {
                var tarif = parseFloat($(this).find('.tarif-input').val()) || 0;
                var qty = parseFloat($(this).find('.qty-input').val()) || 0;
                var subtotalItem = tarif * qty;
                
                $(this).find('.item-subtotal').text('Rp ' + subtotalItem.toLocaleString('id-ID'));
                totalKotor += subtotalItem;
            });

            var pajak = totalKotor * 0.11;
            var grandTotal = totalKotor + pajak;

            // Update Label di Box Total
            $('#label-subtotal').text('Rp ' + totalKotor.toLocaleString('id-ID'));
            $('#label-pajak').text('Rp ' + pajak.toLocaleString('id-ID'));
            $('#label-grandtotal').text('Rp ' + grandTotal.toLocaleString('id-ID'));
        }
    });
    </script>
</body>
</html>