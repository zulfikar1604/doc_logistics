<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;

$query = "SELECT sj.*, 
          l.nama_lengkap as nama_driver,
          a.nopol, a.tipe as tipe_armada,
          r.nama_rute
          FROM surat_jalan sj
          LEFT JOIN driver d ON sj.id_driver = d.id
          LEFT JOIN login l ON d.id_login = l.id
          LEFT JOIN armada a ON sj.id_armada = a.id
          LEFT JOIN routes r ON sj.id_route = r.id
          WHERE sj.id = '$id'";

$q_sj = mysqli_query($conn, $query);
$sj = mysqli_fetch_assoc($q_sj);

if (!$sj) {
    echo "Surat Jalan tidak ditemukan.";
    exit();
}

// Hitung Absolute URL untuk QR Code
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST']; 
$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
$base_dir = rtrim(dirname($uri_parts[0]), '\\/');

$tracking_url = $protocol . $host . $base_dir . '/tracking.php?resi=' . urlencode($sj['no_sj']);
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=0&data=" . urlencode($tracking_url);

// Total quantity untuk label
$q_det = mysqli_query($conn, "SELECT SUM(jumlah) as qty, COUNT(id) as total_item FROM surat_jalan_detail WHERE id_sj='$id'");
$d_det = mysqli_fetch_assoc($q_det);
$total_qty = $d_det['qty'] ?? 0;
$total_item = $d_det['total_item'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Label Pengiriman - <?= htmlspecialchars($sj['no_sj']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Desain Khusus untuk Thermal Printer / Label A6 (Lebar: ~10cm, Tinggi: ~15cm) */
        @page {
            size: 100mm 150mm;
            margin: 0;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #000; 
            margin: 0; 
            padding: 0; 
            background: #e2e8f0;
            display: flex;
            justify-content: center;
        }
        .label-container { 
            width: 100mm; 
            min-height: 148mm;
            background: #fff; 
            padding: 5mm; 
            box-sizing: border-box;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #000; padding-bottom: 5mm; margin-bottom: 5mm; }
        .logo { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: -0.5px; }
        .logo span { background: #000; color: #fff; padding: 2px 5px; border-radius: 4px; margin-left: 2px; }
        
        .title { font-size: 16px; font-weight: 800; text-align: right; }
        
        .qr-section { display: flex; gap: 5mm; align-items: center; border-bottom: 2px solid #000; padding-bottom: 5mm; margin-bottom: 5mm; }
        .qr-image { width: 35mm; height: 35mm; border: 1px solid #000; padding: 2mm; border-radius: 5px; flex-shrink:0; }
        
        .resi-box { flex: 1; text-align: center; }
        .resi-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 2px; }
        .resi-number { font-size: 18px; font-weight: 800; background: #000; color: #fff; padding: 5px; border-radius: 6px; letter-spacing: 1px;}
        .resi-date { font-size: 10px; font-weight: 600; margin-top: 5px; }

        .section-title { font-size: 10px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #000; padding-bottom: 2px; margin-bottom: 3px; display: inline-block; }
        
        .address-box { border: 2px solid #000; border-radius: 8px; padding: 4mm; margin-bottom: 5mm; }
        .receiver-name { font-size: 14px; font-weight: 800; margin-bottom: 3px; }
        .receiver-address { font-size: 11px; font-weight: 500; line-height: 1.4; }
        .receiver-city { font-size: 12px; font-weight: 800; margin-top: 4px; text-transform: uppercase; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3mm; border-bottom: 2px dashed #000; padding-bottom: 5mm; margin-bottom: 5mm; }
        .info-item { background: #f8fafc; padding: 3mm; border-radius: 5px; border: 1px solid #cbd5e1; }
        .info-label { font-size: 9px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 11px; font-weight: 800; }

        .package-summary { text-align: center; border: 2px solid #000; border-radius: 8px; padding: 3mm; background: #000; color: #fff; margin-bottom: 5mm; }
        .package-value { font-size: 18px; font-weight: 800; }
        .package-label { font-size: 10px; font-weight: 600; text-transform: uppercase; opacity: 0.9; }

        .footer-note { text-align: center; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }

        .no-print { text-align: center; margin: 20px 0; }
        .btn-print { padding: 12px 25px; background: #1a367e; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-family: inherit; box-shadow: 0 4px 15px rgba(26,54,126,0.3); }

        @media print {
            body { background: #fff; padding: 0; align-items: flex-start; justify-content: flex-start;}
            .label-container { box-shadow: none; border: none; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

<div>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print"><i class="fas fa-print"></i> CETAK LABEL (A6)</button>
    </div>

    <div class="label-container">
        <!-- HEADER -->
        <div class="header">
            <div class="logo">GLOBAL<span>LOGISTICS</span></div>
            <div class="title">ORDER LABEL</div>
        </div>

        <!-- QR CODE & RESI -->
        <div class="qr-section">
            <img src="<?= $qr_api_url ?>" alt="QR Code Tracking" class="qr-image">
            <div class="resi-box">
                <div class="resi-label">Nomor Resi / Order</div>
                <div class="resi-number"><?= htmlspecialchars($sj['no_sj']) ?></div>
                <div class="resi-date">Tanggal: <?= date('d M Y', strtotime($sj['tgl_kirim'])) ?></div>
                <div style="font-size: 8px; font-weight: 700; margin-top: 3px; color: #475569;">SCAN UNTUK TRACKING</div>
            </div>
        </div>

        <!-- PENERIMA -->
        <div class="address-box">
            <div class="section-title">PENERIMA (CONSIGNEE)</div>
            <div class="receiver-name"><?= htmlspecialchars($sj['penerima']) ?></div>
            <div class="receiver-address"><?= nl2br(htmlspecialchars($sj['alamat_tujuan'])) ?></div>
            <div class="receiver-city"><i class="fas fa-map-marker-alt" style="margin-right:3px;"></i> <?= htmlspecialchars($sj['kota_tujuan']) ?></div>
        </div>

        <!-- SUMMARY BARANG -->
        <div class="package-summary">
            <div class="package-value"><?= $total_qty ?> PCS / <?= $total_item ?> ITEM</div>
            <div class="package-label">TOTAL MUATAN BARANG</div>
        </div>

        <div class="footer-note">
            PT GLOBAL LOGISTICS - DO NOT BEND
        </div>
    </div>
</div>

</body>
</html>
