<?php
declare(strict_types=1);

// Configuração centralizada. Em produção, prefira definir estas variáveis no ambiente.
define('APP_ROOT', dirname(__DIR__));

// Carrega um .env simples quando presente; variáveis já definidas no servidor têm prioridade.
$localEnvFile = APP_ROOT . '/.env';
if (is_file($localEnvFile) && is_readable($localEnvFile)) {
    foreach (file($localEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name !== '' && getenv($name) === false) {
            putenv($name . '=' . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
}

define('APP_ENV', getenv('BAROES_APP_ENV') ?: 'production');
define('APP_TIMEZONE', getenv('BAROES_TIMEZONE') ?: 'America/Sao_Paulo');
date_default_timezone_set(APP_TIMEZONE);
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

define('STORAGE_DIR', getenv('BAROES_STORAGE_DIR') ?: APP_ROOT . '/storage');
define('DB_PATH', getenv('BAROES_DB_PATH') ?: STORAGE_DIR . '/data.sqlite');

define('PUBLIC_DIR', APP_ROOT . '/public');
define('PUBLIC_WEB', 'public');
define('GALLERY_DIR', PUBLIC_DIR . '/media/img/gallery');
define('GALLERY_WEB', 'media/img/gallery');
define('DIRETORIA_DIR', PUBLIC_DIR . '/media/img/diretoria');
define('DIRETORIA_WEB', 'media/img/diretoria');
define('EVENTOS_DIR', PUBLIC_DIR . '/media/img/eventos');
define('EVENTOS_WEB', 'media/img/eventos');
define('COLABORADORES_DIR', PUBLIC_DIR . '/media/img/colaboradores');
define('COLABORADORES_WEB', 'media/img/colaboradores');
define('COLABORADORES_PDF_DIR', PUBLIC_DIR . '/media/pdfs/colaboradores');
define('COLABORADORES_PDF_WEB', 'media/pdfs/colaboradores');
define('SITE_MEDIA_DIR', PUBLIC_DIR . '/media/img/site');
define('SITE_MEDIA_WEB', 'media/img/site');

define('PAGE_SIZE', 24);
define('MAX_BYTES', 8 * 1024 * 1024);
define('MAX_USERNAME_LENGTH', 80);
define('MAX_PASSWORD_LENGTH', 255);
define('MIN_PASSWORD_LENGTH', 12);
define('MAX_TEXT_LENGTH', 10000);
define('MAX_GALLERY_DESCRIPTION_LENGTH', 500);
define('SESSION_IDLE_SECONDS', 1800);
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('SITE_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico']);
define('SITE_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']);
define('AUDIO_EXT', ['mp3', 'wav', 'ogg']);
define('AUDIO_MIME', ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg']);
define('PDF_EXT', ['pdf']);
define('PDF_MIME', ['application/pdf']);
