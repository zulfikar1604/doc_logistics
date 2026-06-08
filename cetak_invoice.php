<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['log'])) {
    header('location:index.php');
    exit();
}

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : 0;
$q_inv = mysqli_query($conn, "SELECT * FROM invoice WHERE id='$id'");
$inv = mysqli_fetch_assoc($q_inv);

if (!$inv) {
    echo "Invoice tidak ditemukan.";
    exit();
}

$q_det = mysqli_query($conn, "SELECT * FROM invoice_detail WHERE id_invoice='$id'");
$details = [];
while ($d = mysqli_fetch_assoc($q_det)) {
    $details[] = $d;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Invoice - <?= $inv['no_invoice'] ?></title>
    <link rel="icon" type="image/png" href="assets/img/icon-192-nobg.png?v=4">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #1e293b; line-height: 1.6; margin: 0; padding: 40px; background: #f8fafc; }
        .invoice-box { max-width: 800px; margin: auto; padding: 40px; border: 1px solid #e2e8f0; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-radius: 12px; position: relative; }
        .stamp-wrapper {
            position: absolute;
            top: 20px;
            left: 40px;
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
            font-size: 16px;
            margin: 4px 0;
            line-height: 1.2;
        }
        .stamp-date {
            font-size: 9px;
            letter-spacing: 1px;
        }
        .stamp-paid {
            color: #10b981; /* Green */
            background: rgba(16, 185, 129, 0.05);
        }
        .stamp-unpaid {
            color: #ef4444; /* Red */
            background: rgba(239, 68, 68, 0.05);
        }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: 800; color: #1a367e; letter-spacing: -0.5px; }
        .logo span { color: #ffcc00; }
        .invoice-title { font-size: 32px; font-weight: 800; color: #1a367e; text-transform: uppercase; margin: 0; }
        .meta-info { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .meta-col { width: 48%; }
        .meta-title { font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
        .meta-data { font-size: 15px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table th { background: #f1f5f9; color: #475569; font-size: 13px; font-weight: 700; text-transform: uppercase; padding: 15px; text-align: left; }
        table th.right { text-align: right; }
        table td { padding: 15px; border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        table td.right { text-align: right; font-weight: 600; }
        .total-box { background: #f8fafc; padding: 20px; border-radius: 8px; width: 300px; float: right; border: 1px solid #e2e8f0; }
        .total-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; }
        .total-row.grand { border-top: 2px solid #cbd5e1; padding-top: 10px; font-size: 18px; font-weight: 800; color: #1a367e; margin-bottom: 0; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .footer { margin-top: 60px; text-align: center; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 6px; font-weight: 700; font-size: 12px; letter-spacing: 1px; }
        .paid { background: #d1fae5; color: #059669; border: 1px solid #a7f3d0; }
        .unpaid { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        
        @media print {
            body { background: #fff; padding: 0; }
            .invoice-box { box-shadow: none; border: none; padding: 0; }
            .btn-print { display: none; }
        }
    </style>
</head>
<body>

<div class="invoice-box">

    <div style="margin-bottom: 20px; text-align: right;">
        <button class="btn-print" onclick="window.print()" style="padding: 10px 20px; background: #1a367e; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: inherit;">Cetak / Save PDF</button>
    </div>

    <div class="header">
        <div>
            <div class="logo">GLOBAL<span>LOGISTICS</span></div>
            <div style="font-size: 13px; color: #64748b; margin-top: 5px;">
                Jl. Logistic Center No.1, Jakarta Raya<br>
                Telp: (021) 1234-5678 | Email: cs@globallogistics.com
            </div>
        </div>
        <div style="text-align: right;">
            <h1 class="invoice-title">INVOICE</h1>
            <div style="margin-top: 10px;">
                <span class="status-badge <?= strtolower($inv['status']) ?>"><?= $inv['status'] == 'PAID' ? 'LUNAS' : 'BELUM LUNAS' ?></span>
            </div>
        </div>
    </div>

    <div class="meta-info">
        <div class="meta-col">
            <div class="meta-title">Ditagihkan Kepada:</div>
            <div class="meta-data"><?= htmlspecialchars($inv['nama_klien']) ?></div>
            <div style="font-size: 14px; color: #475569; margin-top: 5px;"><?= nl2br(htmlspecialchars($inv['alamat_klien'])) ?></div>
        </div>
        <div class="meta-col" style="text-align: right;">
            <div style="margin-bottom: 15px;">
                <div class="meta-title">Nomor Invoice</div>
                <div class="meta-data"><?= $inv['no_invoice'] ?></div>
            </div>
            <div>
                <div class="meta-title">Tanggal Terbit</div>
                <div class="meta-data"><?= date('d F Y', strtotime($inv['tgl_invoice'])) ?></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Deskripsi Item</th>
                <th class="right">Qty</th>
                <th class="right">Tarif/Satuan</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $subtotal = 0;
            $ppn = 0;
            foreach($details as $d): 
                if (strpos($d['deskripsi'], 'PPN') !== false) {
                    $ppn = $d['subtotal'];
                    continue;
                }
                $subtotal += $d['subtotal'];
            ?>
            <tr>
                <td><?= htmlspecialchars($d['deskripsi']) ?></td>
                <td class="right"><?= $d['qty'] ?></td>
                <td class="right">Rp <?= number_format($d['tarif'], 0, ',', '.') ?></td>
                <td class="right">Rp <?= number_format($d['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="clearfix" style="margin-top: 30px;">
        <!-- Signature Block -->
        <div style="float: left; width: 250px; margin-top: 10px; position: relative; text-align: center;">
            <div style="font-size: 13px; font-weight: 700; margin-bottom: 80px; color: #475569;">Hormat Kami,</div>
            <div style="border-top: 1px solid #1e293b; font-weight: 700; font-size: 14px; padding-top: 5px; color: #1e293b;">PT Global Logistics</div>
            
            <!-- CSS Stamp -->
            <div class="stamp-wrapper stamp-<?= strtolower($inv['status']) ?>">
                <div class="stamp-inner">
                    <div class="stamp-brand">GLOBAL LOGISTICS</div>
                    <div class="stamp-title"><?= $inv['status'] == 'PAID' ? 'LUNAS' : 'BELUM LUNAS' ?></div>
                    <div class="stamp-date"><?= date('d.m.Y', strtotime($inv['tgl_invoice'])) ?></div>
                </div>
            </div>
        </div>

        <div class="total-box">
            <div class="total-row">
                <span>Subtotal</span>
                <span>Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
            </div>
            <div class="total-row">
                <span>PPN 11%</span>
                <span>Rp <?= number_format($ppn, 0, ',', '.') ?></span>
            </div>
            <div class="total-row grand">
                <span>TOTAL TAGIHAN</span>
                <span>Rp <?= number_format($inv['total_tagihan'], 0, ',', '.') ?></span>
            </div>
        </div>
    </div>

    <div class="footer">
        Harap melakukan pembayaran ke Rekening BCA 123456789 a/n PT Global Logistics sebelum jatuh tempo.<br>
        Terima kasih atas kepercayaan Anda menggunakan layanan kami.
    </div>
</div>

</body>
</html>
