<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once __DIR__ . '/Support/helpers.php';
require_once __DIR__ . '/Repositories/SettingsRepository.php';
require_once __DIR__ . '/Support/database.php';
require_once __DIR__ . '/Services/UploadService.php';
require_once __DIR__ . '/Repositories/ContentRepository.php';

use App\Services\UploadService;

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (!empty($_SESSION['last_activity']) && (time() - (int)$_SESSION['last_activity']) > SESSION_IDLE_SECONDS) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['last_activity'] = time();
}

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function csrf_check(): void
{
    $provided = (string)($_POST['csrf'] ?? '');
    $expected = (string)($_SESSION['csrf'] ?? '');
    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(400);
        exit('CSRF inválido');
    }
}

function is_logged(): bool
{
    return !empty($_SESSION['uid']);
}

function current_user(): ?array
{
    if (!is_logged()) {
        return null;
    }
    return [
        'id' => (int)($_SESSION['uid'] ?? 0),
        'username' => (string)($_SESSION['username'] ?? ''),
        'is_admin' => (int)($_SESSION['is_admin'] ?? 0),
    ];
}

function login_try(string $username, string $password): bool
{
    $now = time();
    if (!empty($_SESSION['login_blocked_until']) && $now < (int)$_SESSION['login_blocked_until']) {
        return false;
    }
    $stmt = db()->prepare('SELECT id, username, password_hash, is_admin FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, (string)$row['password_hash'])) {
        $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_blocked_until'] = $now + 300;
        }
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['id'];
    $_SESSION['username'] = (string)$row['username'];
    $_SESSION['is_admin'] = (int)$row['is_admin'];
    $_SESSION['last_activity'] = time();
    unset($_SESSION['login_attempts'], $_SESSION['login_blocked_until']);
    return true;
}

function require_login(): void
{
    if (!is_logged()) {
        redirect('admin.php');
    }
}

function require_admin(): void
{
    $user = current_user();
    if (!$user || $user['is_admin'] !== 1) {
        http_response_code(403);
        exit('Acesso negado');
    }
}

function ensure_dirs(): void
{
    foreach ([STORAGE_DIR, GALLERY_DIR, DIRETORIA_DIR, EVENTOS_DIR, COLABORADORES_DIR, COLABORADORES_PDF_DIR, SITE_MEDIA_DIR] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Não foi possível criar o diretório ' . $directory);
        }
    }
}

function safe_basename(string $name): string
{
    $basename = basename($name);
    return (string)preg_replace('/[^A-Za-z0-9._-]/', '_', $basename);
}

function ext_of(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function is_allowed_ext(string $extension): bool
{
    return in_array(strtolower($extension), ALLOWED_EXT, true);
}

function list_images(): array
{
    if (!is_dir(GALLERY_DIR)) {
        return [];
    }
    $files = [];
    foreach (scandir(GALLERY_DIR) ?: [] as $file) {
        $path = GALLERY_DIR . DIRECTORY_SEPARATOR . $file;
        if ($file === '.' || $file === '..' || !is_file($path) || !is_allowed_ext(ext_of($file))) {
            continue;
        }
        $files[] = $file;
    }
    usort($files, static fn(string $a, string $b): int => filemtime(GALLERY_DIR . '/' . $b) <=> filemtime(GALLERY_DIR . '/' . $a));
    return $files;
}

function gallery_items(): array
{
    $metadata = \App\Repositories\ContentRepository::galleryMetadata();
    $items = [];
    foreach (list_images() as $file) {
        $description = trim((string)($metadata[$file]['description'] ?? ''));
        $items[] = [
            'file' => $file,
            'description' => $description !== '' ? $description : 'Imagem da galeria do Barões MC',
        ];
    }
    return $items;
}

function random_gallery_items(int $limit = 20): array
{
    $limit = max(1, $limit);
    $metadata = \App\Repositories\ContentRepository::randomGalleryMetadata();
    $items = [];
    $seenHashes = [];

    foreach ($metadata as $file => $row) {
        $path = GALLERY_DIR . DIRECTORY_SEPARATOR . $file;
        if (!is_file($path) || !is_allowed_ext(ext_of($file))) {
            continue;
        }

        $contentHash = hash_file('sha256', $path);
        if ($contentHash === false || isset($seenHashes[$contentHash])) {
            continue;
        }
        $seenHashes[$contentHash] = true;

        $description = trim((string)($row['description'] ?? ''));
        $items[] = [
            'file' => $file,
            'description' => $description !== '' ? $description : 'Imagem da galeria do Barões MC',
        ];

        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function paginate(array $items, int $page, int $pageSize = PAGE_SIZE): array
{
    $total = count($items);
    $pages = max(1, (int)ceil($total / $pageSize));
    $page = max(1, min($page, $pages));
    return [array_slice($items, ($page - 1) * $pageSize, $pageSize), $page, $pages, $total];
}

function flash_add(string $type, string $message): void
{
    $_SESSION['flash'][] = [$type, $message];
}

function flash_get(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

// Garante as pastas antes de qualquer operação de mídia.
ensure_dirs();
