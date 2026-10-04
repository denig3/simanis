<?php
declare(strict_types=1);

use App\Infrastructure\Database;

require dirname(__DIR__) . '/vendor/autoload.php';
if (PHP_SAPI !== 'cli') {
    exit(1);
}
$name = trim(readline('Nama: ') ?: '');
$email = strtolower(trim(readline('Email: ') ?: ''));
echo 'Password (minimal 12 karakter; input disembunyikan): ';
system('stty -echo');
try {
    $password = rtrim(fgets(STDIN) ?: '', "\r\n");
} finally {
    system('stty echo');
    echo PHP_EOL;
}
if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || strlen($password) < 12 || strlen($password) > 72) {
    fwrite(STDERR, "Nama/email tidak valid atau panjang password bukan 12–72 byte.\n");
    exit(1);
}
try {
    $query = Database::connect()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $query->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Akun berhasil dibuat. Silakan login.\n";
} catch (PDOException $exception) {
    fwrite(STDERR, $exception->getCode() === '23000' ? "Email sudah terdaftar.\n" : "Gagal membuat akun. Periksa koneksi database.\n");
    exit(1);
}
