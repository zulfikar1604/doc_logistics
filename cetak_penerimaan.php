<?php
session_start();
require 'koneksi.php';

// Cek Keamanan
if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$filter_query = "";
$tgl_text = "Semua Waktu";
if (isset($_GET['tgl_mulai']) && isset($_GET['tgl_selesai']) && !empty($_GET['tgl_mulai'])) {
    $mulai = mysqli_real_escape_string($conn, $_GET['tgl_mulai']);
    $selesai = mysqli_real_escape_string($conn, $_GET['tgl_selesai']);
    
    $filter_query .= " AND created_at BETWEEN '$mulai 00:00:00' AND '$selesai 23:59:59' ";
    $tgl_text = date('d F Y', strtotime($mulai)) . " s/d " . date('d F Y', strtotime($selesai));
}

$sql = "SELECT * FROM penerimaan_barang WHERE 1=1 $filter_query ORDER BY created_at ASC";
$query = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penerimaan Barang - PT Elektronik Global</title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.5;
        }

        /* Container & A4 Layout */
        .print-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header (Kop Surat) */
        .header {
            text-align: center;
            border-bottom: 3px solid #111827;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            font-size: 10pt;
            color: #4b5563;
        }

        /* Judul Laporan */
        .report-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-title h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }
        .report-title .periode {
            font-size: 10pt;
            color: #4b5563;
            margin-top: 5px;
        }

        /* Tabel Data */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #111827;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9pt;
        }
        td {
            font-size: 10pt;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Tanda Tangan */
        .signature-area {
            width: 100%;
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-box {
            width: 250px;
            text-align: center;
        }
        .signature-box .date {
            margin-bottom: 10px;
        }
        .signature-box .name {
            margin-top: 70px;
            font-weight: 700;
            text-decoration: underline;
        }

        /* Tombol Print (Sembunyi saat dicetak) */
        .btn-print {
            display: block;
            width: 200px;
            margin: 30px auto;
            padding: 12px;
            background: #1a367e;
            color: white;
            text-align: center;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        
        @media print {
            .btn-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { margin: 1cm; size: A4 portrait; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">🖨️ Cetak Dokumen</button>

    <div class="print-container">
        <!-- Kop Surat -->
        <div class="header">
            <h1>PT. Elektronik Global</h1>
            <p>Kawasan Industri Terpadu, Jl. Logistik No. 88, Jakarta Raya<br>
               Telp: (021) 555-1234 | Email: warehouse@elektronikglobal.com</p>
        </div>

        <!-- Judul Laporan -->
        <div class="report-title">
            <h2>Laporan Histori Penerimaan Barang (Inbound)</h2>
            <div class="periode">Periode: <?= $tgl_text ?></div>
        </div>

        <!-- Tabel Data -->
        <table>
            <thead>
                <tr>
                    <th class="text-center" width="5%">No</th>
                    <th width="15%">Waktu Masuk</th>
                    <th width="15%">SKU</th>
                    <th width="35%">Nama Barang</th>
                    <th class="text-right" width="10%">Qty Masuk</th>
                    <th width="20%">Diterima Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_qty = 0;
                if(mysqli_num_rows($query) == 0) {
                    echo "<tr><td colspan='6' class='text-center'>Tidak ada data penerimaan pada periode ini.</td></tr>";
                } else {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $total_qty += $row['jumlah'];
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['sku']) ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                    <td class="text-right"><strong><?= $row['jumlah'] ?></strong> <?= htmlspecialchars($row['satuan']) ?></td>
                    <td><?= htmlspecialchars($row['diterima_oleh']) ?></td>
                </tr>
                <?php 
                    } 
                }
                ?>
            </tbody>
            <?php if(mysqli_num_rows($query) > 0): ?>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right">TOTAL BARANG MASUK</th>
                    <th class="text-right"><?= $total_qty ?></th>
                    <th></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <!-- Tanda Tangan -->
        <div class="signature-area">
            <div class="signature-box">
                <div class="date">Jakarta, <?= date('d F Y') ?></div>
                <div>Mengetahui,</div>
                <div class="name">Kepala Gudang</div>
            </div>
        </div>
    </div>

    <!-- Script auto print -->
    <script>
        window.onload = function() {
            // Otomatis membuka dialog print
            window.print();
        };
    </script>
</body>
</html>
