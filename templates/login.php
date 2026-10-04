<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Login - Inventory &amp; Order</title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
    <script src="/assets/app.js?v=<?= time() ?>" defer></script>
</head>
<body>
    <main class="login-container">
        <div class="app-mark" aria-hidden="true">IO</div>
        <h1>Inventory &amp; Order Management</h1>
        <p class="muted">Proyek pengelolaan stok dan pesanan.</p>
        <section class="panel" aria-labelledby="login-title">
            <h2 id="login-title">Login</h2>
            <p class="muted">Masuk menggunakan akun yang sudah dibuat.</p>
            <?php if ($registered): ?>
                <p class="success-message" role="status">Akun berhasil dibuat. Silakan login dengan email dan password Anda.</p>
            <?php endif; ?>
            <form id="login-form" action="/api/login" method="post">
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="username" maxlength="190" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-row">
                        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="1024" required>
                        <button id="toggle-password" class="secondary-button" type="button" aria-label="Tampilkan password" aria-pressed="false">Lihat</button>
                    </div>
                </div>
                <div id="form-message" class="form-message" role="alert" hidden></div>
                <button id="login-button" type="submit">Masuk</button>
                <noscript><p>Aktifkan JavaScript untuk menggunakan form login.</p></noscript>
            </form>

            <p class="help-text">Belum punya akun? <a href="/register">Daftar</a></p>
            <p class="help-text">Email: admin@example.com | Password: Admin12345678!</p>
        </section>
        <p class="footer-note"><span class="phase-label">Fase 1</span> Login dan dashboard awal</p>
    </main>
</body>
</html>
