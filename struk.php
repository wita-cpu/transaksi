<?php
include 'koneksi.php';

if (!isset($_SESSION['last_transaction'])) {
    header("Location: index.php");
    exit();
}

$trx = $_SESSION['last_transaction'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembayaran - #<?= $trx['id_transaksi']; ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Courier New', Courier, monospace; background-color: #f8fafc; padding: 20px; color: #0f172a; }
        
        .receipt-card { background: #fff; width: 320px; margin: auto; padding: 20px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0; }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .title { font-size: 16px; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; }
        .subtitle { font-size: 11px; color: #475569; margin-bottom: 2px; }
        
        .divider { border-bottom: 1px dashed #94a3b8; margin: 12px 0; }
        
        table { width: 100%; font-size: 12px; }
        td { padding: 3px 0; vertical-align: top; }
        
        .customer-info { font-size: 11px; }
        .customer-info td:first-child { color: #64748b; width: 30%; }
        
        .item-name { font-weight: bold; font-size: 12px; }
        .item-detail { color: #475569; }
        
        .btn-area { margin-top: 20px; display: flex; gap: 10px; width: 320px; margin-left: auto; margin-right: auto; }
        .btn { padding: 10px; font-family: sans-serif; font-size: 13px; font-weight: bold; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; flex: 1; text-align: center; }
        .btn-print { background-color: #10b981; color: white; }
        .btn-back { background-color: #4f46e5; color: white; }

        @media print {
            body { background: transparent; padding: 0; }
            .receipt-card { box-shadow: none; border: none; width: 100%; padding: 0; }
            .btn-area { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-card">
    <div class="text-center">
        <div class="title">Toko Struk Transaksi</div>
        <div class="subtitle"><?= $trx['waktu']; ?></div>
        <div class="subtitle">Kasir: <?= htmlspecialchars($trx['kasir']); ?></div>
    </div>

    <div class="divider"></div>

    <!-- Data Pelanggan -->
    <table class="customer-info">
        <tr>
            <td>Pelanggan</td>
            <td>: <strong><?= htmlspecialchars($trx['nama_pelanggan']); ?></strong></td>
        </tr>
        <tr>
            <td>No. HP</td>
            <td>: <?= htmlspecialchars($trx['no_hp']); ?></td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>: <?= htmlspecialchars($trx['alamat']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- Daftar Belanja -->
    <table>
        <?php foreach ($trx['items'] as $item): ?>
        <tr>
            <td colspan="2" class="item-name"><?= htmlspecialchars($item['nama_barang']); ?></td>
        </tr>
        <tr>
            <td class="item-detail"><?= $item['jumlah']; ?> x Rp <?= number_format($item['harga']); ?></td>
            <td class="text-right">Rp <?= number_format($item['subtotal']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="divider"></div>

    <!-- Rincian Pembayaran -->
    <table>
        <tr>
            <td>Total</td>
            <td class="text-right"><strong>Rp <?= number_format($trx['total']); ?></strong></td>
        </tr>
        <tr>
            <td>Bayar</td>
            <td class="text-right">Rp <?= number_format($trx['bayar']); ?></td>
        </tr>
        <tr>
            <td>Kembalian</td>
            <td class="text-right">Rp <?= number_format($trx['kembalian']); ?></td>
        </tr>
    </table>

    <div class="divider"></div>
    <p class="text-center subtitle">-- Terima Kasih Atas Kunjungan Anda --</p>
</div>

<div class="btn-area">
    <button onclick="window.print()" class="btn btn-print">🖨️ Cetak Struk</button>
    <a href="transaksi.php" class="btn btn-back">➕ Transaksi Baru</a>
</div>

</body>
</html>