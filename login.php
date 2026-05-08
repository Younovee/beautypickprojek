<?php
session_start();
require 'koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$pesan = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $pesan = "Username dan password wajib diisi!";
    } else {
        $hasil = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
        if (mysqli_num_rows($hasil) === 1) {
            $user = mysqli_fetch_assoc($hasil);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $pesan = "Password salah!";
            }
        } else {
            $pesan = "Username tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Beauty Pick</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<nav class="navbar bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-leaf"></i> Beauty Pick
        </a>
    </div>
</nav>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h4><i class="fas fa-lock"></i> Login</h4>
            <p>Masuk ke akun Beauty Pick-mu</p>
        </div>

        <?php if (isset($_GET['sukses'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Registrasi berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if ($pesan): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($pesan) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-key"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" name="login" class="btn btn-auth">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </button>
        </form>

        <hr>
        <p class="text-center mb-0">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>