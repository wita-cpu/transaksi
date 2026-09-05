<?php
include 'koneksi.php';

// Inisialisasi keranjang transaksi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambah Barang ke Keranjang
if (isset($_POST['add_to_cart'])) {
    $id_barang = $_POST['id_barang'];
    $jumlah = (int)$_POST['jumlah'];

    $query = mysqli_query($koneksi, "SELECT * FROM barang WHERE id_barang = '$id_barang'");
    $barang = mysqli_fetch_assoc($query);

    if ($barang) {
        $stok_tersedia = $barang['stok'];
        $jumlah_di_cart = isset($_SESSION['cart'][$id_barang]) ? $_SESSION['cart'][$id_barang]['jumlah'] : 0;

        if (($jumlah_di_cart + $jumlah) > $stok_tersedia) {
            echo "<script>alert('Stok tidak mencukupi! Stok tersisa: {$stok_tersedia}');</script>";
        } else {
            if (isset($_SESSION['cart'][$id_barang])) {
                $_SESSION['cart'][$id_barang]['jumlah'] += $jumlah;
            } else {
                $_SESSION['cart'][$id_barang] = [
                    'id_barang'   => $barang['id_barang'],
                    'nama_barang' => $barang['nama_barang'],
                    'harga'       => $barang['harga'],
                    'gambar'      => $barang['gambar'],
                    'jumlah'      => $jumlah,
                ];
            }
            $_SESSION['cart'][$id_barang]['subtotal'] = $_SESSION['cart'][$id_barang]['harga'] * $_SESSION['cart'][$id_barang]['jumlah'];
        }
    }
}

// Update Jumlah (Plus / Minus / Hapus)
if (isset($_POST['update_cart'])) {
    $id_barang = $_POST['id_barang'];
    $action    = $_POST['action'];

    if (isset($_SESSION['cart'][$id_barang])) {
        if ($action == 'plus') {
            $query = mysqli_query($koneksi, "SELECT stok FROM barang WHERE id_barang = '$id_barang'");
            $data  = mysqli_fetch_assoc($query);
            if ($_SESSION['cart'][$id_barang]['jumlah'] < $data['stok']) {
                $_SESSION['cart'][$id_barang]['jumlah']++;
            } else {
                echo "<script>alert('Stok maksimal telah tercapai!');</script>";
            }
        } elseif ($action == 'minus') {
            $_SESSION['cart'][$id_barang]['jumlah']--;
            if ($_SESSION['cart'][$id_barang]['jumlah'] <= 0) {
                unset($_SESSION['cart'][$id_barang]);
            }
        } elseif ($action == 'delete') {
            unset($_SESSION['cart'][$id_barang]);
        }

        if (isset($_SESSION['cart'][$id_barang])) {
            $_SESSION['cart'][$id_barang]['subtotal'] = $_SESSION['cart'][$id_barang]['harga'] * $_SESSION['cart'][$id_barang]['jumlah'];
        }
    }
}

// Tombol Batal Transaksi (Reset Keranjang)
if (isset($_POST['batal_transaksi'])) {
    $_SESSION['cart'] = [];
    header("Location: index.php");
    exit();
}

// Generate ID Transaksi Otomatis
$query_auto = mysqli_query($koneksi, "SELECT MAX(id_transaksi) AS max_id FROM transaksi");
$data_auto  = mysqli_fetch_assoc($query_auto);
$next_id    = $data_auto['max_id'] ? $data_auto['max_id'] + 1 : 1;
$tgl_sekarang = date('Y-m-d H:i:s');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System - Kasir & Transaksi</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --bg-main: #f1f5f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: var(--bg-main); color: var(--text-dark); padding: 24px; }
        
        .header-title { margin-bottom: 24px; font-size: 24px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 10px; }
        .header-title span { background: var(--primary); color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; }

        .grid-container { display: flex; gap: 24px; }
        @media (max-width: 992px) { .grid-container { flex-direction: column; } }

        .box { background: var(--card-bg); border-radius: 16px; padding: 24px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border: 1px solid var(--border-color); }
        .left-panel { flex: 1.2; }
        .right-panel { flex: 1.8; }

        .panel-header { font-size: 18px; font-weight: 600; margin-bottom: 16px; border-bottom: 2px solid var(--bg-main); padding-bottom: 10px; color: var(--text-dark); }

        /* Product Grid */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; max-height: 580px; overflow-y: auto; padding-right: 6px; }
        .product-card { border: 1px solid var(--border-color); border-radius: 12px; padding: 12px; text-align: center; background: #fff; transition: all 0.2s ease; display: flex; flex-direction: column; justify-content: space-between; }
        .product-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15); transform: translateY(-3px); }
        .product-img { width: 100%; height: 100px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; }
        .product-title { font-weight: 600; font-size: 14px; color: var(--text-dark); line-height: 1.3; height: 36px; overflow: hidden; margin-bottom: 4px; }
        .product-price { color: var(--primary); font-weight: 700; font-size: 14px; margin-bottom: 4px; }
        .product-stock { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }

        /* Table Cart */
        .table-responsive { overflow-x: auto; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px; text-align: left; }
        td { border-bottom: 1px solid var(--border-color); padding: 12px; vertical-align: middle; font-size: 14px; }
        .cart-img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; }

        /* Buttons */
        .btn { padding: 8px 14px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-sm { padding: 4px 8px; font-size: 12px; border-radius: 6px; }
        .btn-plus { background: var(--accent-green); color: white; }
        .btn-minus { background: var(--accent-amber); color: white; }
        .btn-danger { background: #fee2e2; color: var(--accent-red); }
        .btn-danger:hover { background: var(--accent-red); color: white; }
        .btn-primary { background: var(--primary); color: white; width: 100%; padding: 14px; font-size: 16px; border-radius: 10px; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-batal { background: #f1f5f9; color: var(--text-muted); width: 100%; padding: 10px; font-size: 14px; border-radius: 8px; margin-top: 8px; }
        .btn-batal:hover { background: #e2e8f0; color: var(--text-dark); }

        /* Form Inputs Layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 15px; }
        .form-group { margin-bottom: 12px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 4px; }
        input[type="number"], input[type="text"], textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 14px; background: #f8fafc; outline: none; transition: 0.2s; }
        input:focus, textarea:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        input[readonly] { background: #e2e8f0; color: #475569; font-weight: 600; cursor: not-allowed; }

        .total-box { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin-top: 15px; }
        .total-box .total-label { font-size: 16px; font-weight: 600; color: #166534; }
        .total-box .total-amount { font-size: 22px; font-weight: 800; color: #15803d; }

        .kembalian-box { background: #e0f2fe; border: 1px solid #bae6fd; padding: 12px 16px; border-radius: 8px; font-size: 15px; font-weight: 600; color: #0369a1; margin-bottom: 15px; display: flex; justify-content: space-between; }
    </style>
</head>
<body>

<div class="header-title">
    🛒 POS System <span>Kasir v2.0</span>
</div>

<div class="grid-container">
    
    <!-- PANEL KIRI: DAFTAR BARANG -->
    <div class="box left-panel">
        <div class="panel-header">Katalog Produk</div>
        <div class="product-grid">
            <?php
            $get_barang = mysqli_query($koneksi, "SELECT * FROM barang WHERE stok > 0");
            while ($b = mysqli_fetch_assoc($get_barang)) {
                $imgPath = "uploads/" . $b['gambar'];
                if (empty($b['gambar']) || !file_exists($imgPath)) {
                    $imgPath = "https://via.placeholder.com/150?text=No+Image";
                }
            ?>
                <div class="product-card">
                    <div>
                        <img src="<?= $imgPath; ?>" class="product-img" alt="<?= htmlspecialchars($b['nama_barang']); ?>">
                        <div class="product-title"><?= htmlspecialchars($b['nama_barang']); ?></div>
                        <div class="product-price">Rp <?= number_format($b['harga']); ?></div>
                        <div class="product-stock">Stok: <?= $b['stok']; ?></div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="id_barang" value="<?= $b['id_barang']; ?>">
                        <input type="hidden" name="jumlah" value="1">
                        <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">+ Pilih</button>
                    </form>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- PANEL KANAN: KERANJANG & CHECKOUT -->
    <div class="box right-panel">
        <div class="panel-header">Keranjang Belanja</div>
        
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Barang</th>
                        <th>Harga</th>
                        <th style="text-align: center;">Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_bayar = 0;
                    if (!empty($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $item) {
                            $total_bayar += $item['subtotal'];
                            $itemImg = "uploads/" . $item['gambar'];
                            if (empty($item['gambar']) || !file_exists($itemImg)) {
                                $itemImg = "https://via.placeholder.com/150?text=No+Image";
                            }
                    ?>
                            <tr>
                                <td><img src="<?= $itemImg; ?>" class="cart-img"></td>
                                <td><strong><?= htmlspecialchars($item['nama_barang']); ?></strong></td>
                                <td>Rp <?= number_format($item['harga']); ?></td>
                                <td style="text-align: center;">
                                    <form method="POST" style="display:inline-flex; align-items:center; gap:6px;">
                                        <input type="hidden" name="id_barang" value="<?= $item['id_barang']; ?>">
                                        <button type="submit" name="action" value="minus" class="btn btn-sm btn-minus">-</button>
                                        <span style="min-width: 24px; text-align:center; font-weight:bold;"><?= $item['jumlah']; ?></span>
                                        <button type="submit" name="action" value="plus" class="btn btn-sm btn-plus">+</button>
                                        <input type="hidden" name="update_cart" value="1">
                                    </form>
                                </td>
                                <td><strong>Rp <?= number_format($item['subtotal']); ?></strong></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_barang" value="<?= $item['id_barang']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger">✕</button>
                                        <input type="hidden" name="update_cart" value="1">
                                    </form>
                                </td>
                            </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; color:#94a3b8; padding: 30px;'>Keranjang masih kosong. Pilih produk di sebelah kiri.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="total-box">
            <span class="total-label">Total Pembayaran:</span>
            <span class="total-amount">Rp <?= number_format($total_bayar); ?></span>
        </div>

        <!-- Form Transaksi & Pelanggan -->
        <?php if (!empty($_SESSION['cart'])) : ?>
            <form action="proses_bayar.php" method="POST">
                <input type="hidden" name="total_bayar" id="total_bayar" value="<?= $total_bayar; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>ID Transaksi</label>
                        <input type="text" name="id_transaksi" value="<?= $next_id; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="text" name="tanggal_transaksi" value="<?= $tgl_sekarang; ?>" readonly>
                    </div>

                    <div class="form-group full">
                        <label>Nama Kasir</label>
                        <input type="text" name="nama_kasir" placeholder="Masukkan nama kasir..." required>
                    </div>

                    <!-- Input Pelanggan -->
                    <div class="form-group">
                        <label>Nama Pelanggan</label>
                        <input type="text" name="nama_pelanggan" value="customer" placeholder="Nama pelanggan..." required>
                    </div>

                    <div class="form-group">
                        <label>No. HP Pelanggan</label>
                        <input type="text" name="no_hp" value="-" placeholder="masukan no.hp" required>
                    </div>

                    <div class="form-group full">
                        <label>Alamat Pelanggan</label>
                        <textarea name="alamat" rows="2" value="-" placeholder="Alamat lengkap..." required></textarea>
                    </div>

                    <div class="form-group full">
                        <label>Uang Dibayar (Rp)</label>
                        <input type="number" name="uang_dibayar" id="uang_dibayar" min="<?= $total_bayar; ?>" placeholder="0" required oninput="hitungKembalian()">
                    </div>
                </div>

                <div class="kembalian-box">
                    <span>Kembalian:</span>
                    <span id="kembalian_text">Rp 0</span>
                </div>

                <button type="submit" name="proses_transaksi" class="btn btn-primary">Proses & Cetak Struk</button>
            </form>

            <form method="POST">
                <button type="submit" name="batal_transaksi" class="btn btn-batal" onclick="return confirm('Batalkan transaksi ini?')">Batal Transaksi</button>
            </form>
        <?php endif; ?>
    </div>

</div>

<script>
function hitungKembalian() {
    let total = parseFloat(document.getElementById('total_bayar').value) || 0;
    let bayar = parseFloat(document.getElementById('uang_dibayar').value) || 0;
    let kembalian = bayar - total;

    if (kembalian >= 0) {
        document.getElementById('kembalian_text').innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
    } else {
        document.getElementById('kembalian_text').innerText = 'Uang Kurang';
    }
}
</script>

</body>
</html>