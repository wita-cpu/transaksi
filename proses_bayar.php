<?php
include 'koneksi.php';



if (isset($_POST['proses_transaksi'])) {
    $nama_kasir     = mysqli_real_escape_string($koneksi, $_POST['nama_kasir']);
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $no_hp          = mysqli_real_escape_string($koneksi, $_POST['no_hp']);
    $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $total_bayar    = (int)$_POST['total_bayar'];
    $uang_dibayar   = (int)$_POST['uang_dibayar'];
    $tgl_transaksi  = mysqli_real_escape_string($koneksi, $_POST['tanggal_transaksi']);

    if ($uang_dibayar < $total_bayar) {
        echo "<script>alert('Uang pembayaran kurang!'); window.location='transaksi.php';</script>";
        exit();
    }

    $kembalian = $uang_dibayar - $total_bayar;

    // 1. Simpan data ke tabel pelangan
    $query_pelanggan = "INSERT INTO pelanggan (nama_pelanggan, alamat, no_hp) 
                        VALUES ('$nama_pelanggan', '$alamat', '$no_hp')";
    
    if (mysqli_query($koneksi, $query_pelanggan)) {
        $id_pelanggan = mysqli_insert_id($koneksi);

        // 2. Simpan transaksi ke tabel transaksi
        $query_transaksi = "INSERT INTO transaksi (tanggal, total, nama_kasir) 
                            VALUES ('$tgl_transaksi', '$total_bayar', '$nama_kasir')";
        
        if (mysqli_query($koneksi, $query_transaksi)) {
            $id_transaksi = mysqli_insert_id($koneksi);

            // 3. Kurangi stok barang di tabel barang
            foreach ($_SESSION['cart'] as $item) {
                $id_barang = $item['id_barang'];
                $jumlah    = $item['jumlah'];

                mysqli_query($koneksi, "UPDATE barang SET stok = stok - $jumlah WHERE id_barang = '$id_barang'");
            }

            // 4. Simpan data transaksi dan pelanggan ke Session untuk cetak Struk
            $_SESSION['last_transaction'] = [
                'id_transaksi'   => $id_transaksi,
                'kasir'          => $nama_kasir,
                'nama_pelanggan' => $nama_pelanggan,
                'no_hp'          => $no_hp,
                'alamat'         => $alamat,
                'items'          => $_SESSION['cart'],
                'total'          => $total_bayar,
                'bayar'          => $uang_dibayar,
                'kembalian'      => $kembalian,
                'waktu'          => $tgl_transaksi
            ];

            unset($_SESSION['cart']);

            header("Location: struk.php");
            exit();
        } else {
            echo "Gagal menyimpan transaksi: " . mysqli_error($koneksi);
        }
    } else {
        echo "Gagal menyimpan data pelanggan: " . mysqli_error($koneksi);
    }
}
?>