<?php
session_start();
require 'koneksi.php';

// ── Proses Submit Rating ──
$pesan_tipe = $pesan_isi = "";

if (isset($_POST['submit_rating'])) {
    if (!isset($_SESSION['user_id'])) {
        $pesan_tipe = "danger";
        $pesan_isi  = "Kamu harus login dulu untuk memberi rating!";
    } else {
        $user_id   = (int) $_SESSION['user_id'];
        $produk_id = (int) $_POST['produk_id'];
        $rating    = (int) $_POST['rating'];

        if ($rating < 1 || $rating > 5) {
            $pesan_tipe = "danger";
            $pesan_isi  = "Nilai rating tidak valid!";
        } else {
            $cek = mysqli_query($conn,
                "SELECT id FROM ratings WHERE user_id='$user_id' AND produk_id='$produk_id'"
            );
            if (mysqli_num_rows($cek) > 0) {
                mysqli_query($conn,
                    "UPDATE ratings SET rating='$rating'
                     WHERE user_id='$user_id' AND produk_id='$produk_id'"
                );
                $pesan_tipe = "success";
                $pesan_isi  = "Rating berhasil diperbarui!";
            } else {
                mysqli_query($conn,
                    "INSERT INTO ratings (user_id, produk_id, rating)
                     VALUES ('$user_id','$produk_id','$rating')"
                );
                $pesan_tipe = "success";
                $pesan_isi  = "Rating berhasil disimpan!";
            }
        }
    }
}

// Ambil semua produk
$query_produk = mysqli_query($conn,
    "SELECT p.*, k.nama AS nama_kategori,
            ROUND(AVG(r.rating), 1) AS rata_rating,
            COUNT(r.id) AS jml_rating
     FROM produk p
     LEFT JOIN kategori k ON p.kategori_id = k.id
     LEFT JOIN ratings r  ON p.id = r.produk_id
     GROUP BY p.id
     ORDER BY p.kategori_id, p.nama"
);

// Rating user
$rating_user = [];
if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];
    $q   = mysqli_query($conn, "SELECT produk_id, rating FROM ratings WHERE user_id='$uid'");
    while ($r = mysqli_fetch_assoc($q)) {
        $rating_user[$r['produk_id']] = $r['rating'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beauty Pick - Skincare Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-leaf"></i> Beauty Pick
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href="product.php"><i class="fas fa-box"></i> Produk</a></li>
            </ul>
            <div class="d-flex gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text me-2"><i class="fas fa-user-circle"></i> Halo, <b><?= htmlspecialchars($_SESSION['username']) ?></b></span>
                    <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalHapusAkun"><i class="fas fa-trash-alt"></i> Hapus Akun</button>
                    <a href="logout.php" class="btn btn-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- MODAL HAPUS AKUN -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="modal fade" id="modalHapusAkun" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Konfirmasi Hapus Akun</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah kamu yakin ingin menghapus akun <b><?= htmlspecialchars($_SESSION['username']) ?></b>?</p>
                <p class="text-danger mb-0"><small><i class="fas fa-info-circle"></i> Semua data rating akan ikut terhapus. Tindakan ini tidak bisa dibatalkan.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="hapus_akun.php" class="btn btn-danger">Ya, Hapus Akun Saya</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <h1 class="animate-fadeInUp">Selamat Datang di Beauty Pick</h1>
        <p class="animate-fadeInUp delay-1">Informasi lengkap skincare berkualitas premium – temukan produk terbaik untuk kulitmu</p>
        <a href="product.php" class="btn btn-hero animate-fadeInUp delay-2"><i class="fas fa-search"></i> Jelajahi Produk</a>
    </div>
</section>

<!-- PRODUK UNGGULAN -->
<section class="container my-5">
    <div class="text-center mb-5">
        <h2 class="section-title">✨ Koleksi Skincare</h2>
        <p class="section-subtitle">Lihat detail harga, rating, dan deskripsi produk</p>
    </div>

    <?php if ($pesan_isi): ?>
        <div class="alert alert-<?= $pesan_tipe ?> alert-dismissible fade show" role="alert">
            <i class="fas fa-<?= $pesan_tipe == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($pesan_isi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php
    $ikon_kategori = ['Moisturizer' => '🧴', 'Serum' => '💧', 'Sunscreen' => '☀️', 'Toner' => '🌿', 'Cleanser' => '🫧'];
    $kategori_aktif = "";
    $buka_row = false;
    while ($p = mysqli_fetch_assoc($query_produk)):
        if ($p['nama_kategori'] !== $kategori_aktif):
            if ($buka_row) echo '</div>';
            $kategori_aktif = $p['nama_kategori'];
            $ikon = $ikon_kategori[$kategori_aktif] ?? '✨';
    ?>
            <div class="category-header"><h4 class="category-title"><?= $ikon . ' ' . htmlspecialchars($kategori_aktif) ?></h4></div>
            <div class="row g-4">
    <?php
            $buka_row = true;
        endif;

        $rata = $p['rata_rating'];
        $bintang_html = "";
        for ($i = 1; $i <= 5; $i++) {
            $bintang_html .= ($rata && $i <= round($rata)) ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>';
        }
        $slider_val = $rating_user[$p['id']] ?? 3;
        $harga_fmt = 'Rp ' . number_format($p['harga'], 0, ',', '.');
    ?>
        <div class="col-xl-3 col-md-4 col-sm-6">
            <div class="product-card">
                <div class="product-image-wrapper">
<img src="<?= htmlspecialchars($p['gambar']) ?>" class="product-image" alt="<?= htmlspecialchars($p['nama']) ?>">                    <div class="product-overlay">
                        <a href="product_detail.php?id=<?= $p['id'] ?>" class="btn btn-view"><i class="fas fa-eye"></i> Detail</a>
                    </div>
                </div>
                <div class="product-body">
                    <span class="badge-category"><?= htmlspecialchars($p['nama_kategori']) ?></span>
                    <h5 class="product-title"><?= htmlspecialchars($p['nama']) ?></h5>
                    <div class="product-price"><?= $harga_fmt ?></div>
                    <div class="product-stock"><i class="fas fa-boxes"></i> Stok: <?= (int)$p['stok'] ?> pcs</div>
                    <div class="product-rating mb-2">
                        <?= $bintang_html ?>
                        <?php if ($rata): ?>
                            <span class="rating-score"><?= $rata ?>/5</span>
                            <span class="rating-count">(<?= (int)$p['jml_rating'] ?> rating)</span>
                        <?php else: ?>
                            <span class="text-muted">Belum ada rating</span>
                        <?php endif; ?>
                    </div>
                    <p class="product-description"><?= htmlspecialchars(substr($p['deskripsi'], 0, 80)) ?>...</p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" class="rating-form">
                            <input type="hidden" name="produk_id" value="<?= (int)$p['id'] ?>">
                            <div class="rating-slider-wrapper">
                                <label class="rating-label"><i class="fas fa-star text-warning"></i> Rating: <span id="val-<?= $p['id'] ?>"><?= $slider_val ?></span>/5</label>
                                <input type="range" name="rating" class="form-range rating-range" min="1" max="5" step="1" value="<?= $slider_val ?>" oninput="document.getElementById('val-<?= $p['id'] ?>').textContent = this.value">
                            </div>
                            <button type="submit" name="submit_rating" class="btn btn-rating"><i class="fas fa-star"></i> <?= isset($rating_user[$p['id']]) ? 'Perbarui Rating' : 'Beri Rating' ?></button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mt-2"><a href="login.php" class="btn btn-login-rating"><i class="fas fa-sign-in-alt"></i> Login untuk rating</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endwhile; if ($buka_row) echo '</div>'; ?>
</section>

<!-- INFO SECTION -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h3><i class="fas fa-info-circle"></i> Butuh Rekomendasi Skincare?</h3>
            <p>Konsultasikan kebutuhan kulitmu dengan kami dan temukan produk yang tepat</p>
            <a href="product.php" class="btn btn-cta">Lihat Semua Produk <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4"><h5><i class="fas fa-leaf"></i> Beauty Pick</h5><p>Pusat informasi skincare berkualitas premium.</p><div class="social-links"><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-facebook"></i></a><a href="#"><i class="fab fa-twitter"></i></a></div></div>
            <div class="col-md-4 mb-4"><h5>Informasi</h5><ul class="footer-links"><li><a href="#">Tentang Kami</a></li><li><a href="#">Kebijakan Privasi</a></li><li><a href="#">Syarat & Ketentuan</a></li></ul></div>
            <div class="col-md-4 mb-4"><h5>Kontak</h5><ul class="footer-links"><li><i class="fas fa-envelope"></i> hello@beautypick.com</li><li><i class="fas fa-phone"></i> (021) 1234-5678</li></ul></div>
        </div>
        <hr><div class="text-center"><small>© 2025 Beauty Pick. All rights reserved.</small></div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
