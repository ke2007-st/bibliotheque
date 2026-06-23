<?php

declare(strict_types=1);

function setSecurityHeaders(): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DB_NAME') ?: 'php_docker');
define('DB_USER', getenv('DB_USER') ?: 'kevine');
define('DB_PASS', getenv('DB_PASS') ?: 'kevine1234');

define('USER_SESSION', 'BIBLIO_USER_SESS');
define('ADMIN_SESSION', 'BIBLIO_ADMIN_SESS');
define('CSRF_KEY', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_SECONDS', 900);

function getConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $message, int $code = 400): void
{
    jsonResponse(['success' => false, 'error' => $message], $code);
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function sanitizeString(?string $value): string
{
    return trim($value ?? '');
}

function validateEmail(string $email): string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Adresse email invalide.');
    }

    return strtolower($email);
}

function validatePassword(string $password): void
{
    if (strlen($password) < 8) {
        jsonError('Le mot de passe doit contenir au moins 8 caracteres.');
    }
    if (!preg_match('/[A-Z]/', $password)) {
        jsonError('Le mot de passe doit contenir au moins une majuscule.');
    }
    if (!preg_match('/[a-z]/', $password)) {
        jsonError('Le mot de passe doit contenir au moins une minuscule.');
    }
    if (!preg_match('/[0-9]/', $password)) {
        jsonError('Le mot de passe doit contenir au moins un chiffre.');
    }
}

function validateStatut(string $statut): string
{
    $allowed = ['disponible', 'emprunte', 'reserve', 'vendu'];

    if (!in_array($statut, $allowed, true)) {
        jsonError('Statut invalide.');
    }

    return $statut;
}

function formatUser(array $user, bool $includeRole = true): array
{
    $data = [
        'id' => (int) $user['id'],
        'nom' => $user['nom'],
        'prenom' => $user['prenom'],
        'email' => $user['email'],
        'telephone' => $user['telephone'],
        'date_inscription' => $user['date_inscription'],
    ];

    if ($includeRole) {
        $data['role'] = $user['role'];
    }

    return $data;
}

function startSession(string $name): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() !== $name) {
            session_write_close();
        } else {
            return;
        }
    }

    session_name($name);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function destroySession(string $name): void
{
    startSession($name);
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function getClientIp(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function checkLoginRateLimit(PDO $pdo, string $context, string $email): void
{
    $ip = getClientIp();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_tentatives
         WHERE contexte = ? AND (email = ? OR ip = ?)
         AND date_tentative > DATE_SUB(NOW(), INTERVAL ? SECOND)'
    );
    $stmt->execute([$context, $email, $ip, LOGIN_LOCKOUT_SECONDS]);

    if ((int) $stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS) {
        jsonError('Trop de tentatives. Reessayez dans 15 minutes.', 429);
    }
}

function recordLoginAttempt(PDO $pdo, string $context, string $email, bool $success): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO login_tentatives (contexte, email, ip, reussie) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$context, $email, getClientIp(), $success ? 1 : 0]);

    if ($success) {
        $stmt = $pdo->prepare(
            'DELETE FROM login_tentatives WHERE contexte = ? AND email = ?'
        );
        $stmt->execute([$context, $email]);
    }
}

function generateCsrfToken(): string
{
    return bin2hex(random_bytes(32));
}

function storeCsrfToken(string $sessionName): string
{
    startSession($sessionName);

    if (empty($_SESSION[CSRF_KEY])) {
        $_SESSION[CSRF_KEY] = generateCsrfToken();
    }

    return $_SESSION[CSRF_KEY];
}

function verifyCsrfToken(string $sessionName): void
{
    startSession($sessionName);

    $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION[CSRF_KEY] ?? '';

    if ($header === '' || $sessionToken === '' || !hash_equals($sessionToken, $header)) {
        jsonError('Token CSRF invalide.', 403);
    }
}

function fetchUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    return $user ?: null;
}
