<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
$auto_print = isset($_GET['download']) && $_GET['download'] == '1';

$query = "SELECT sj.*, 
          l.nama_lengkap as nama_driver,
          a.nopol, a.tipe as tipe_armada,
          r.nama_rute
          FROM surat_jalan sj
          JOIN driver d ON sj.id_driver = d.id
          JOIN login l ON d.id_login = l.id
          JOIN armada a ON sj.id_armada = a.id
          LEFT JOIN routes r ON sj.id_route = r.id
          WHERE sj.id = '$id'";

$q_sj = mysqli_query($conn, $query);
$sj = mysqli_fetch_assoc($q_sj);

if (!$sj) {
    echo "Surat Jalan tidak ditemukan.";
    exit();
}

$q_det = mysqli_query($conn, "SELECT * FROM surat_jalan_detail WHERE id_sj='$id'");
$details = [];
while ($d = mysqli_fetch_assoc($q_det)) {
    $details[] = $d;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Jalan - <?= htmlspecialchars($sj['no_sj']) ?></title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; line-height: 1.6; margin: 0; padding: 40px; background: #f8fafc; }
        .document-box { max-width: 850px; margin: auto; padding: 50px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-radius: 12px; position: relative; }
        .stamp-wrapper {
            position: absolute;
            top: 20px;
            left: 15px;
            width: 170px;
            padding: 8px;
            border: 3px solid currentColor;
            border-radius: 10px;
            text-align: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            transform: rotate(-12deg);
            mix-blend-mode: multiply;
            opacity: 0.85;
            pointer-events: none;
            user-select: none;
            z-index: 99;
        }
        .stamp-inner {
            border: 1px dashed currentColor;
            border-radius: 6px;
            padding: 6px;
        }
        .stamp-brand {
            font-size: 8px;
            letter-spacing: 2px;
            margin-bottom: 2px;
        }
        .stamp-title {
            font-size: 14px;
            margin: 4px 0;
            line-height: 1.2;
        }
        .stamp-date {
            font-size: 9px;
            letter-spacing: 1px;
        }
        .stamp-shipper {
            color: #1a367e; /* Corporate Blue */
            background: rgba(26, 54, 126, 0.05);
        }
        .stamp-consignee {
            color: #10b981; /* Green */
            background: rgba(16, 185, 129, 0.05);
        }
        .stamp-draft {
            color: #64748b; /* Slate Gray */
            background: rgba(100, 116, 139, 0.05);
        }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 4px solid #1a367e; padding-bottom: 20px; }
        .logo { font-size: 26px; font-weight: 800; color: #1a367e; letter-spacing: -0.5px; }
        .logo span { color: #ffcc00; }
        .doc-title { font-size: 28px; font-weight: 800; color: #1a367e; margin: 0; letter-spacing: 1px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; }
        .info-box { background: #f8fafc; padding: 20px; border-radius: 10px; border: 1px solid #f1f5f9; }
        .info-title { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
        
        .row-data { display: flex; margin-bottom: 8px; font-size: 14px; }
        .row-data .label { width: 120px; font-weight: 600; color: #475569; }
        .row-data .value { font-weight: 700; flex: 1; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; border: 1px solid #e2e8f0; }
        table th { background: #1a367e; color: #fff; font-size: 13px; font-weight: 700; text-transform: uppercase; padding: 12px 15px; text-align: left; }
        table th.center { text-align: center; }
        table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; font-size: 14px; }
        table td.center { text-align: center; font-weight: 600; }
        
        .signature-section { display: flex; justify-content: space-between; margin-top: 60px; text-align: center; }
        .sign-box { width: 200px; position: relative; }
        .sign-title { font-size: 13px; font-weight: 700; margin-bottom: 80px; }
        .sign-line { border-top: 1px solid #1e293b; font-weight: 700; font-size: 14px; padding-top: 5px; }
        
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-weight: 800; font-size: 11px; margin-top: 5px; }
        .bg-ontrip { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .bg-delivered { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .bg-draft { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .document-box { box-shadow: none; border: none; padding: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body onload="<?php if($auto_print) echo 'window.print();'; ?>">

<div class="document-box">
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1a367e; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: inherit;">
            <svg style="width:16px; height:16px; vertical-align:-3px; margin-right:5px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Cetak Dokumen
        </button>
    </div>

    <div class="header">
        <div>
            <div class="logo">GLOBAL<span>LOGISTICS</span></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                Jl. Logistic Center No.1, Jakarta Raya<br>
                Telp: (021) 1234-5678
            </div>
        </div>
        <div style="text-align: right;">
            <h1 class="doc-title">SURAT JALAN</h1>
            <div style="font-size: 15px; font-weight: 800; color: #e11d48; margin-top: 5px;"><?= htmlspecialchars($sj['no_sj']) ?></div>
            
            <?php 
                $st = $sj['status'];
                $bg = 'bg-draft';
                if ($st == 'ON TRIP' || $st == 'RETURNING') $bg = 'bg-ontrip';
                if ($st == 'DELIVERED' || $st == 'FINISHED') $bg = 'bg-delivered';
            ?>
            <span class="status-badge <?= $bg ?>"><?= $st ?></span>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <div class="info-title">Pihak Penerima (Consignee)</div>
            <div style="font-size: 16px; font-weight: 800; margin-bottom: 5px; color:#1a367e;"><?= htmlspecialchars($sj['penerima']) ?></div>
            <div style="font-size: 14px; color: #475569; font-weight: 500; line-height: 1.5;">
                <?= nl2br(htmlspecialchars($sj['alamat_tujuan'])) ?><br>
                <strong><?= htmlspecialchars($sj['kota_tujuan']) ?></strong>
            </div>
        </div>
        
        <div>
            <div class="info-title" style="margin-top: 10px;">Detail Pengiriman</div>
            <div class="row-data">
                <div class="label">Tanggal</div>
                <div class="value">: <?= date('d F Y', strtotime($sj['tgl_kirim'])) ?></div>
            </div>
            <div class="row-data">
                <div class="label">Armada</div>
                <div class="value">: <?= htmlspecialchars($sj['nopol']) ?> (<?= htmlspecialchars($sj['tipe_armada']) ?>)</div>
            </div>
            <div class="row-data">
                <div class="label">Driver</div>
                <div class="value">: <?= htmlspecialchars($sj['nama_driver']) ?></div>
            </div>
            <div class="row-data">
                <div class="label">Rute</div>
                <div class="value">: <?= htmlspecialchars($sj['nama_rute'] ?? 'Manual') ?></div>
            </div>
            <div class="row-data">
                <div class="label">Prioritas</div>
                <div class="value">: <span style="color: <?= $sj['prioritas']=='Tinggi'?'#e11d48':'#1e293b' ?>"><?= htmlspecialchars($sj['prioritas']) ?></span></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 50px;">No</th>
                <th>Nama Barang / Deskripsi</th>
                <th class="center" style="width: 120px;">Qty</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_qty = 0;
            foreach($details as $d): 
                $total_qty += $d['jumlah'];
            ?>
            <tr>
                <td class="center"><?= $no++ ?></td>
                <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($d['nama_barang']) ?></td>
                <td class="center"><?= $d['jumlah'] ?> <?= htmlspecialchars($d['satuan']) ?></td>
                <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($d['keterangan']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #f8fafc;">
                <td colspan="2" style="text-align: right; font-weight: 700;">TOTAL QUANTITY:</td>
                <td class="center" style="font-size: 16px; color: #1a367e;"><?= $total_qty ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="font-size: 12px; color: #64748b; font-style: italic; margin-bottom: 20px;">
        * Barang diterima dalam keadaan baik dan cukup.<br>
        * Segala komplain terkait pengiriman maksimal 1x24 jam setelah barang diterima.
    </div>

    <div class="signature-section">
        <div class="sign-box">
            <div class="sign-title">Pengirim (Shipper)</div>
            <div class="sign-line">PT Global Logistics</div>
            
            <?php if ($st != 'DRAFT'): ?>
            <!-- CSS Shipper Stamp -->
            <div class="stamp-wrapper stamp-shipper">
                <div class="stamp-inner">
                    <div class="stamp-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-title">RELEASED</div>
                    <div class="stamp-date"><?= date('d.m.Y', strtotime($sj['tgl_kirim'])) ?></div>
                </div>
            </div>
            <?php else: ?>
            <!-- CSS Draft Stamp -->
            <div class="stamp-wrapper stamp-draft">
                <div class="stamp-inner">
                    <div class="stamp-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-title">DRAFT ONLY</div>
                    <div class="stamp-date"><?= date('d.m.Y', strtotime($sj['tgl_kirim'])) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="sign-box">
            <div class="sign-title">Supir (Driver)</div>
            <div class="sign-line"><?= htmlspecialchars($sj['nama_driver']) ?></div>
            
            <?php if ($st != 'DRAFT'): ?>
            <!-- CSS Driver Stamp -->
            <div class="stamp-wrapper <?= ($st == 'DELIVERED' || $st == 'FINISHED') ? 'stamp-consignee' : 'stamp-shipper' ?>">
                <div class="stamp-inner">
                    <div class="stamp-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-title"><?= ($st == 'DELIVERED' || $st == 'FINISHED') ? 'DELIVERED' : 'ON TRIP' ?></div>
                    <div class="stamp-date"><?= date('d.m.Y', strtotime($sj['tgl_kirim'])) ?></div>
                </div>
            </div>
            <?php else: ?>
            <!-- CSS Draft Stamp -->
            <div class="stamp-wrapper stamp-draft">
                <div class="stamp-inner">
                    <div class="stamp-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-title">DRAFT ONLY</div>
                    <div class="stamp-date"><?= date('d.m.Y', strtotime($sj['tgl_kirim'])) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="sign-box">
            <div class="sign-title">Penerima (Consignee)</div>
            <div class="sign-line">Tanda Tangan & Stempel</div>
        </div>
    </div>
</div>

</body>
</html>
