<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$role = $_SESSION['role'];

// Filter Logika (Sama dengan manajemen_dokumen.php)
$filter_query = "";
$subtitle = "Semua Dokumen";
if (isset($_GET['tgl_mulai']) && isset($_GET['tgl_selesai']) && !empty($_GET['tgl_mulai'])) {
    $mulai = $_GET['tgl_mulai'];
    $selesai = $_GET['tgl_selesai'];
    $filter_query .= " AND tgl_kirim BETWEEN '$mulai' AND '$selesai' ";
    $subtitle = "Periode: " . date('d M Y', strtotime($mulai)) . " s/d " . date('d M Y', strtotime($selesai));
}

// Security: Jika Driver mencoba mengakses, batasi hanya melihat datanya sendiri
if ($role == 'DRIVER') {
    $email_driver = mysqli_real_escape_string($conn, $_SESSION['email']);
    $q_driver = mysqli_query($conn, "SELECT d.id FROM driver d JOIN login l ON d.id_login = l.id WHERE l.email = '$email_driver'");
    if ($drv = mysqli_fetch_assoc($q_driver)) {
        $filter_query .= " AND sj.id_driver = '" . $drv['id'] . "' ";
    } else {
        $filter_query .= " AND sj.id_driver = '0' "; 
    }
}

// Ambil Data
$sql = "SELECT sj.*, l.nama_lengkap as nama_driver 
        FROM surat_jalan sj 
        JOIN driver d ON sj.id_driver = d.id 
        JOIN login l ON d.id_login = l.id 
        WHERE 1=1 $filter_query 
        ORDER BY sj.id DESC";
$query = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Laporan - PT. Global Logistics</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            font-size: 11pt;
            color: #111827;
            background-color: #fff;
            margin: 0;
            padding: 20px;
        }
        
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }

        /* Kop Surat */
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #1a367e;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .kop-surat h1 {
            margin: 0;
            font-size: 20pt;
            color: #1a367e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .kop-surat p {
            margin: 5px 0 0 0;
            font-size: 10pt;
            color: #4b5563;
        }

        /* Judul Laporan */
        .laporan-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .laporan-title h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .laporan-title p {
            margin: 5px 0 0 0;
            font-size: 11pt;
        }

        /* Tabel Laporan */
        table.laporan-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10pt;
        }
        table.laporan-table th, table.laporan-table td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            text-align: left;
        }
        table.laporan-table th {
            background-color: #f3f4f6;
            color: #1a367e;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        /* Prioritas Label */
        .prioritas {
            display: inline-block;
            font-size: 8pt;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 4px;
            border: 1px solid #999;
        }

        /* Signature */
        .signature-box {
            width: 100%;
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-col {
            width: 33%;
            float: left;
            text-align: center;
            font-size: 10pt;
            position: relative; /* For stamp positioning */
        }
        .stamp-report {
            position: absolute;
            top: 20px;
            right: 40px;
            width: 160px;
            padding: 6px;
            border: 3px solid #1a367e;
            border-radius: 8px;
            color: #1a367e;
            text-align: center;
            font-family: 'Inter', sans-serif;
            text-transform: uppercase;
            font-weight: 800;
            transform: rotate(-8deg);
            mix-blend-mode: multiply;
            opacity: 0.8;
            pointer-events: none;
            user-select: none;
            z-index: 10;
        }
        .stamp-report-inner {
            border: 1px dashed #1a367e;
            border-radius: 4px;
            padding: 4px;
        }
        .stamp-rep-brand {
            font-size: 7px;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }
        .stamp-rep-title {
            font-size: 14px;
            margin: 3px 0;
            line-height: 1.2;
        }
        .stamp-rep-dept {
            font-size: 8px;
            letter-spacing: 1px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* Print Specifics */
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 15px; background: #1a367e; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Sekarang</button>
        <button onclick="window.close()" style="padding: 8px 15px; background: #9ca3af; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Tutup</button>
    </div>

    <!-- Kop Surat -->
    <div class="kop-surat">
        <h1>PT. GLOBAL LOGISTICS</h1>
        <p>Jl. Jendral Sudirman No. 123, Jakarta Selatan, 12190</p>
        <p>Telp: (021) 555-0198 | Email: cs@globallogistics.co.id</p>
    </div>

    <!-- Judul -->
    <div class="laporan-title">
        <h2>Laporan Surat Jalan & Pengiriman</h2>
        <p><?= htmlspecialchars($subtitle) ?></p>
    </div>

    <!-- Tabel -->
    <table class="laporan-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="14%">Tanggal</th>
                <th width="18%">No. Dokumen</th>
                <th width="18%">Driver</th>
                <th width="30%">Tujuan & Penerima</th>
                <th width="16%">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if(mysqli_num_rows($query) > 0) {
                while($row = mysqli_fetch_assoc($query)) {
                    $prioritas = isset($row['prioritas']) ? $row['prioritas'] : 'Normal';
            ?>
            <tr>
                <td style="text-align: center;"><?= $no++ ?></td>
                <td><?= date('d M Y', strtotime($row['tgl_kirim'])) ?></td>
                <td>
                    <b><?= htmlspecialchars($row['no_sj']) ?></b><br>
                    <span class="prioritas"><?= strtoupper($prioritas) ?></span>
                </td>
                <td><?= htmlspecialchars($row['nama_driver']) ?></td>
                <td>
                    <b><?= htmlspecialchars($row['penerima'] ?? '') ?></b><br>
                    <?= htmlspecialchars($row['kota_tujuan']) ?>
                </td>
                <td>
                    <b><?= htmlspecialchars($row['status']) ?></b>
                </td>
            </tr>
            <?php 
                }
            } else {
            ?>
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">Tidak ada data pada periode ini.</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="signature-box clearfix">
        <div class="signature-col" style="width: 100%; text-align: right; padding-right: 50px; position: relative; float: right;">
            Mengetahui,<br><br><br><br><br>
            <span style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($_SESSION['nama_lengkap']) ?></span><br>
            Manager Operasional
            
            <!-- CSS Stamp -->
            <div class="stamp-report">
                <div class="stamp-report-inner">
                    <div class="stamp-rep-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-rep-title">VERIFIED</div>
                    <div class="stamp-rep-dept">OPERATIONAL</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
