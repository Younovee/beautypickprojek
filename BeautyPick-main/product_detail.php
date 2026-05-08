<?php
session_start();
require 'koneksi.php';

$produk_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($produk_id <= 0) header("Location: product.php");

$stmt = mysqli_prepare($conn, "SELECT p.*, k.nama AS nama_kategori, ROUND(AVG(r.rating),1) AS rata_rating, COUNT(r.id) AS jml_rating FROM produk p LEFT JOIN kategori k ON p.kategori_id=k.id LEFT JOIN ratings r ON p.id=r.produk_id WHERE p.id=? GROUP BY p.id");
mysqli_stmt_bind_param($stmt,"i",$produk_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);
if (!$produk) header("Location: product.php");

$user_rating = null;
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $stmt2 = mysqli_prepare($conn, "SELECT rating FROM ratings WHERE user_id=? AND produk_id=?");
    mysqli_stmt_bind_param($stmt2,"ii",$uid,$produk_id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    $row2 = mysqli_fetch_assoc($res2);
    if ($row2) $user_rating = $row2['rating'];
}

$pesan_tipe = $pesan_isi = "";
if (isset($_POST['submit_rating'])) {
    if (!isset($_SESSION['user_id'])) { $pesan_tipe="danger"; $pesan_isi="Login dulu!"; }
    else {
        $user_id = (int)$_SESSION['user_id'];
        $rating = (int)$_POST['rating'];
        if ($rating<1 || $rating>5) { $pesan_tipe="danger"; $pesan_isi="Rating tidak valid!"; }
        else {
            $cek = mysqli_prepare($conn,"SELECT id FROM ratings WHERE user_id=? AND produk_id=?");
            mysqli_stmt_bind_param($cek,"ii",$user_id,$produk_id);
            mysqli_stmt_execute($cek);
            $cek_res = mysqli_stmt_get_result($cek);
            if (mysqli_num_rows($cek_res)>0) {
                $up = mysqli_prepare($conn,"UPDATE ratings SET rating=? WHERE user_id=? AND produk_id=?");
                mysqli_stmt_bind_param($up,"iii",$rating,$user_id,$produk_id);
                mysqli_stmt_execute($up);
                $pesan_tipe="success"; $pesan_isi="Rating diperbarui!";
            } else {
                $ins = mysqli_prepare($conn,"INSERT INTO ratings (user_id,produk_id,rating) VALUES (?,?,?)");
                mysqli_stmt_bind_param($ins,"iii",$user_id,$produk_id,$rating);
                mysqli_stmt_execute($ins);
                $pesan_tipe="success"; $pesan_isi="Rating disimpan!";
            }
            header("Location: product_detail.php?id=$produk_id&pesan=$pesan_tipe&isi=".urlencode($pesan_isi));
            exit;
        }
    }
}
if (isset($_GET['pesan'])) { $pesan_tipe = $_GET['pesan']; $pesan_isi = $_GET['isi']; }

$stmt_rec = mysqli_prepare($conn,"SELECT id,nama,harga,gambar,stok FROM produk WHERE kategori_id=? AND id!=? LIMIT 4");
mysqli_stmt_bind_param($stmt_rec,"ii",$produk['kategori_id'],$produk_id);
mysqli_stmt_execute($stmt_rec);
$rekomendasi = mysqli_stmt_get_result($stmt_rec);

function formatRupiah($angka){ return 'Rp '.number_format($angka,0,',','.'); }
function tampilkanBintang($rating){
    $html='';
    for($i=1;$i<=5;$i++) $html .= ($rating && $i<=round($rating)) ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title><?= htmlspecialchars($produk['nama']) ?> - Beauty Pick</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><link rel="stylesheet" href="style.css"></head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top"><div class="container"><a class="navbar-brand" href="index.php"><i class="fas fa-leaf"></i> Beauty Pick</a><button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav me-auto"><li class="nav-item"><a class="nav-link" href="index.php">Home</a></li><li class="nav-item"><a class="nav-link" href="product.php">Produk</a></li></ul><div class="d-flex gap-2"><?php if(isset($_SESSION['user_id'])): ?><span class="navbar-text me-2"><i class="fas fa-user-circle"></i> <b><?= htmlspecialchars($_SESSION['username']) ?></b></span><button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapusAkun"><i class="fas fa-trash-alt"></i> Hapus Akun</button><a href="logout.php" class="btn btn-danger btn-sm">Logout</a><?php else: ?><a href="login.php" class="btn btn-outline-primary btn-sm">Login</a><a href="register.php" class="btn btn-primary btn-sm">Register</a><?php endif; ?></div></div></div></nav>
<?php if(isset($_SESSION['user_id'])): ?><div class="modal fade" id="modalHapusAkun" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header bg-danger text-white"><h5 class="modal-title">Konfirmasi Hapus Akun</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p>Hapus akun <b><?= htmlspecialchars($_SESSION['username']) ?></b>?</p></div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><a href="hapus_akun.php" class="btn btn-danger">Ya, Hapus</a></div></div></div></div><?php endif; ?>
<div class="container my-5">
    <?php if($pesan_isi): ?><div class="alert alert-<?= htmlspecialchars($pesan_tipe) ?>"><?= htmlspecialchars($pesan_isi) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <div class="row">
        <div class="col-md-6 mb-4">
    <div class="product-detail-image-wrapper">
        <img src="<?= htmlspecialchars($produk['gambar']) ?>" 
             alt="<?= htmlspecialchars($produk['nama']) ?>">
    </div>
</div>
        <div class="col-md-6">
            <span class="badge-category mb-2 d-inline-block"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
            <h1 class="display-6 fw-bold mb-3"><?= htmlspecialchars($produk['nama']) ?></h1>
            <div class="product-price-detail mb-3"><?= formatRupiah($produk['harga']) ?></div>
            <div class="mb-3"><div class="product-rating-detail"><?= tampilkanBintang($produk['rata_rating']) ?><?php if($produk['rata_rating']): ?><span class="rating-score ms-2"><b><?= $produk['rata_rating'] ?>/5</b></span><span class="rating-count text-muted">(<?= (int)$produk['jml_rating'] ?> rating)</span><?php else: ?><span class="text-muted">Belum ada rating</span><?php endif; ?></div></div>
            <div class="mb-4"><div class="stock-info <?= $produk['stok']>0 ? 'text-success' : 'text-danger' ?>"><i class="fas <?= $produk['stok']>0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i> Stok: <?= (int)$produk['stok'] ?> pcs</div></div>
            <div class="product-description-detail mb-4"><h5><i class="fas fa-align-left"></i> Deskripsi Produk</h5><p class="text-muted"><?= nl2br(htmlspecialchars($produk['deskripsi'])) ?></p></div>
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="rating-form-detail card p-3 mb-4 bg-light"><h6><i class="fas fa-star text-warning"></i> Beri Rating</h6><form method="POST"><div class="row align-items-center"><div class="col-auto"><span class="fw-bold me-2">Rating Anda:</span></div><div class="col"><input type="range" name="rating" class="form-range" min="1" max="5" step="1" value="<?= $user_rating ?? 3 ?>" oninput="document.getElementById('ratingValuePreview').textContent=this.value"><div class="text-center mt-1"><span id="ratingValuePreview"><?= $user_rating ?? 3 ?></span>/5 <i class="fas fa-star text-warning"></i></div></div><div class="col-auto"><button type="submit" name="submit_rating" class="btn btn-rating"><i class="fas fa-save"></i> <?= $user_rating ? 'Perbarui' : 'Simpan' ?> Rating</button></div></div></form></div>
            <?php else: ?><div class="alert alert-info"><i class="fas fa-info-circle"></i> <a href="login.php">Login</a> untuk memberi rating.</div><?php endif; ?>
            <a href="product.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Katalog</a>
        </div>
    </div>
    <?php if(mysqli_num_rows($rekomendasi)>0): ?>
        <div class="related-products mt-5 pt-4"><h4 class="section-title"><i class="fas fa-thumbs-up"></i> Produk Terkait</h4><div class="row g-4 mt-2"><?php while($rek = mysqli_fetch_assoc($rekomendasi)): ?><div class="col-md-3 col-sm-6"><div class="product-card h-100"><div class="product-image-wrapper" style="height:160px;"><img src="<?= htmlspecialchars($rek['gambar']) ?>" class="product-image" alt="<?= htmlspecialchars($rek['nama']) ?>"><div class="product-overlay"><a href="product_detail.php?id=<?= $rek['id'] ?>" class="btn btn-view btn-sm">Detail</a></div></div><div class="product-body p-3"><h6 class="product-title"><?= htmlspecialchars($rek['nama']) ?></h6><div class="product-price"><?= formatRupiah($rek['harga']) ?></div><div class="product-stock">Stok: <?= (int)$rek['stok'] ?> pcs</div><a href="product_detail.php?id=<?= $rek['id'] ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">Lihat Detail</a></div></div></div><?php endwhile; ?></div></div>
    <?php endif; ?>
</div>
<footer class="footer"><div class="container"><div class="row"><div class="col-md-4 mb-4"><h5><i class="fas fa-leaf"></i> Beauty Pick</h5><p>Informasi skincare lengkap.</p><div class="social-links"><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-facebook"></i></a><a href="#"><i class="fab fa-twitter"></i></a></div></div><div class="col-md-4 mb-4"><h5>Informasi</h5><ul class="footer-links"><li><a href="#">Tentang Kami</a></li><li><a href="#">Kebijakan Privasi</a></li></ul></div><div class="col-md-4 mb-4"><h5>Kontak</h5><ul class="footer-links"><li><i class="fas fa-envelope"></i> hello@beautypick.com</li><li><i class="fas fa-phone"></i> (021) 1234-5678</li></ul></div></div><hr><div class="text-center"><small>© 2025 Beauty Pick</small></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>