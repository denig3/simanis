<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Daftar - Inventory &amp; Order</title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
    <script src="/assets/app.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <main class="login-container">
        <div class="app-mark" aria-hidden="true">IO</div>
        <h1>Inventory &amp; Order Management</h1>
        <p class="muted">Proyek pengelolaan stok dan pesanan.</p>
        <section class="panel" aria-labelledby="register-title">
            <h2 id="register-title">Daftar akun</h2>
            <p class="muted">Isi data berikut untuk membuat akun baru.</p>
            <form id="register-form" action="/api/register" method="post">
                <div class="field">
                    <label for="name">Nama</label>
                    <input id="name" name="name" autocomplete="name" maxlength="100" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-row">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="password-hint" required>
                        <button id="toggle-password" class="secondary-button" type="button" aria-label="Tampilkan password" aria-pressed="false">Lihat</button>
                    </div>
                    <small id="password-hint" class="muted">Minimal 12 karakter, maksimal 72 byte.</small>
                </div>
                <div class="field">
                    <label for="password-confirmation">Konfirmasi password</label>
                    <input id="password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
                </div>
                <div id="form-message" class="form-message" role="alert" hidden></div>
                <button id="register-button" type="submit">Daftar</button>
                <noscript><p>Aktifkan JavaScript untuk menggunakan form daftar.</p></noscript>
            </form>
            <p class="help-text">Sudah punya akun? <a href="/login">Login</a></p>
        </section>
        <p class="footer-note"><span class="phase-label">Fase 1</span> Pendaftaran akun</p>
    </main>
</body>
</html>
