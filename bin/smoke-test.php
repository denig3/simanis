<?php
declare(strict_types=1);

use App\Infrastructure\Database;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/** @return array{status: int, body: string, headers: list<string>} */
function request(string $path, string $method = 'GET', string $body = '', string $csrf = ''): array
{
    static $cookie = '';
    $context = stream_context_create(['http' => [
        'method' => $method,
        'header' => "Content-Type: application/json\r\nCookie: {$cookie}\r\nX-CSRF-Token: {$csrf}\r\n",
        'content' => $body,
        'ignore_errors' => true,
        'follow_location' => 0,
        'timeout' => 10,
    ]]);
    $response = file_get_contents('http://127.0.0.1' . $path, false, $context);
    $headers = $http_response_header;
    preg_match('/\s(\d{3})(?:\s|$)/', $headers[0] ?? '', $status);
    foreach ($headers as $header) {
        if (preg_match('/^Set-Cookie: ([^;]+)/i', $header, $match)) {
            $cookie = $match[1];
        }
    }
    return ['status' => (int) ($status[1] ?? 0), 'body' => $response ?: '', 'headers' => $headers];
}

function check(bool $condition, string $label): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    echo 'PASS: ' . $label . PHP_EOL;
}

function csrfFrom(string $html): string
{
    preg_match('/name="csrf-token" content="([a-f0-9]+)"/', $html, $match);
    return $match[1] ?? '';
}

$pdo = Database::connect();
$email = 'smoke-' . bin2hex(random_bytes(8)) . '@example.test';
$password = bin2hex(random_bytes(16));
try {
    $registration = request('/register');
    $registrationToken = csrfFrom($registration['body']);
    check($registration['status'] === 200 && strlen($registrationToken) === 64, 'Registration page renders');
    check(request('/api/register')['status'] === 405, 'Registration rejects GET');
    $fields = ['name' => 'Smoke <test>', 'email' => strtoupper($email), 'password' => $password, 'password_confirmation' => $password];
    $registrationBody = json_encode($fields, JSON_THROW_ON_ERROR);
    check(request('/api/register', 'POST', $registrationBody)['status'] === 403, 'Registration requires CSRF');
    foreach ([['name' => ' '], ['email' => 'invalid'], ['password' => 'short'], ['password' => str_repeat('a', 73)], ['password_confirmation' => 'different'], ['name' => []]] as $invalid) {
        $body = json_encode(array_replace($fields, $invalid), JSON_THROW_ON_ERROR);
        check(request('/api/register', 'POST', $body, $registrationToken)['status'] === 422, 'Invalid registration rejected');
    }
    check(request('/api/register', 'POST', $registrationBody, $registrationToken)['status'] === 201, 'Account registered');
    $query = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
    $query->execute([$email]);
    $hash = (string) $query->fetchColumn();
    check($hash !== $password && password_verify($password, $hash), 'Registration stores a password hash');
    check(request('/api/register', 'POST', $registrationBody, $registrationToken)['status'] === 409, 'Duplicate email rejected');
    check(str_contains(request('/login')['body'], 'Akun berhasil dibuat.'), 'Registration success message displayed');
    check(!str_contains(request('/login')['body'], 'Akun berhasil dibuat.'), 'Success message shown only once');
    for ($i = 0; $i < 2; $i++) {
        request('/api/register', 'POST', $registrationBody, $registrationToken);
    }
    check(request('/api/register', 'POST', $registrationBody, $registrationToken)['status'] === 429, 'Registration rate limit enforced');
    check(request('/dashboard')['status'] === 302, 'Guest redirected from dashboard');
    $page = request('/login');
    $token = csrfFrom($page['body']);
    check($page['status'] === 200 && strlen($token) === 64, 'Login renders with CSRF token');
    check(request('/api/login', 'GET')['status'] === 405, 'Method guard');
    check(request('/api/login', 'POST', '{}')['status'] === 403, 'Missing CSRF rejected');
    check(request('/api/login', 'POST', '{', $token)['status'] === 400, 'Invalid JSON rejected');
    check(request('/api/login', 'POST', '{"email":"bad","password":"x"}', $token)['status'] === 422, 'Invalid email rejected');
    $wrong = json_encode(['email' => $email, 'password' => 'wrong'], JSON_THROW_ON_ERROR);
    check(request('/api/login', 'POST', $wrong, $token)['status'] === 401, 'Wrong password rejected');
    $valid = json_encode(['email' => $email, 'password' => $password], JSON_THROW_ON_ERROR);
    check(request('/api/login', 'POST', $valid, $token)['status'] === 200, 'Valid login succeeds');
    $dashboard = request('/dashboard');
    check($dashboard['status'] === 200 && str_contains($dashboard['body'], 'Smoke &lt;test&gt;'), 'Dashboard authenticated and output escaped');
    check(request('/login')['status'] === 302, 'Authenticated login redirects');
    check(request('/api/logout', 'POST', '{}', $token)['status'] === 403, 'Old token invalidated after login');
    check(request('/api/logout', 'POST', '{}', csrfFrom($dashboard['body']))['status'] === 200, 'Logout succeeds');
    check(request('/dashboard')['status'] === 302, 'Dashboard protected after logout');
    $token = csrfFrom(request('/login')['body']);
    for ($i = 0; $i < 3; $i++) {
        check(request('/api/login', 'POST', $wrong, $token)['status'] === 401, 'Failed login counted');
    }
    check(request('/api/login', 'POST', $valid, $token)['status'] === 429, 'Rate limit enforced');
} finally {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
    $pdo->prepare('DELETE FROM login_attempts WHERE bucket = ?')->execute([hash('sha256', 'register:127.0.0.1')]);
    $pdo->prepare('DELETE FROM login_attempts WHERE bucket IN (?, ?)')->execute([hash('sha256', 'email:' . $email), hash('sha256', 'ip:127.0.0.1')]);
}
