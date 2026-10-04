<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Login — SIMANIS (Sistem Manajemen Inventaris)</title>
    <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
    <script src="/assets/app.js?v=<?= time() ?>" defer></script>
</head>
<body class="auth-body">
    <main class="login-container">
        <h1>SIMANIS</h1>
        <p class="muted">Sistem Manajemen Inventaris</p>
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
            <div class="demo-accounts" aria-label="Akun demo per peran">
                <p class="demo-title">Akun demo (klik "Pakai" untuk mengisi form)</p>
                <?php foreach ([
                    ['Admin', 'admin@example.com', 'Admin12345678!'],
                    ['Sales', 'sales@example.com', 'Sales12345678!'],
                    ['Gudang', 'warehouse@example.com', 'Warehouse12345678!'],
                ] as [$demoRole, $demoEmail, $demoPassword]): ?>
                    <div class="demo-row">
                        <span class="demo-role"><?= htmlspecialchars($demoRole, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="demo-cred"><code><?= htmlspecialchars($demoEmail, ENT_QUOTES, 'UTF-8') ?></code><code><?= htmlspecialchars($demoPassword, ENT_QUOTES, 'UTF-8') ?></code></span>
                        <button type="button" class="demo-fill secondary-button" data-email="<?= htmlspecialchars($demoEmail, ENT_QUOTES, 'UTF-8') ?>" data-password="<?= htmlspecialchars($demoPassword, ENT_QUOTES, 'UTF-8') ?>">Pakai</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <script>
        document.querySelectorAll('.demo-fill').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('email').value = btn.dataset.email;
                document.getElementById('password').value = btn.dataset.password;
                document.getElementById('login-button').focus();
            });
        });
    </script>
</body>
</html>
