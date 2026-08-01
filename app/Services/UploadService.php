<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class UploadService
{
    public static function store(array $file, string $destination, ?array $extensions = null, ?array $mimes = null, bool $imageOnly = true): ?string
    {
        $extensions ??= ALLOWED_EXT;
        $mimes ??= ALLOWED_MIME;
        $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::uploadErrorMessage($error));
        }
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('O upload não pôde ser processado pelo servidor.');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > MAX_BYTES) {
            throw new RuntimeException('A imagem deve ter entre 1 byte e ' . (MAX_BYTES / 1024 / 1024) . ' MB.');
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $extensions, true)) {
            throw new RuntimeException('Extensão de imagem não permitida.');
        }

        $mime = self::detectMime($file['tmp_name']);
        if (!in_array($mime, $mimes, true) || ($imageOnly && !self::isImage($file['tmp_name']))) {
            throw new RuntimeException($imageOnly ? 'O arquivo não é uma imagem válida.' : 'O arquivo não é válido.');
        }

        if (!is_dir($destination) && !mkdir($destination, 0750, true) && !is_dir($destination)) {
            throw new RuntimeException('Não foi possível preparar o armazenamento da imagem.');
        }

        $newName = bin2hex(random_bytes(16)) . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        $target = rtrim($destination, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Não foi possível salvar a imagem.');
        }
        chmod($target, 0640);
        return $newName;
    }

    public static function delete(?string $filename, string $directory): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $base = realpath($directory);
        $target = realpath($directory . DIRECTORY_SEPARATOR . basename($filename));
        if ($base === false || $target === false || !is_file($target)) {
            return;
        }
        if (strncmp($target, $base . DIRECTORY_SEPARATOR, strlen($base . DIRECTORY_SEPARATOR)) !== 0) {
            throw new RuntimeException('Caminho de imagem inválido.');
        }
        if (!unlink($target)) {
            throw new RuntimeException('Não foi possível remover a imagem antiga.');
        }
    }

    private static function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new RuntimeException('O servidor não tem suporte à validação de MIME.');
        }
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);
        return is_string($mime) ? $mime : '';
    }

    private static function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite configurado no servidor. Aumente upload_max_filesize e post_max_size.',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite permitido pelo formulário.',
            UPLOAD_ERR_PARTIAL => 'O upload foi interrompido antes de terminar. Tente novamente.',
            UPLOAD_ERR_NO_TMP_DIR => 'O servidor não possui diretório temporário para uploads.',
            UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o arquivo enviado.',
            UPLOAD_ERR_EXTENSION => 'Uma extensão do PHP interrompeu o upload.',
            default => 'O upload não pôde ser processado pelo servidor.',
        };
    }

    private static function isImage(string $path): bool
    {
        set_error_handler(static fn(): bool => true);
        try {
            return getimagesize($path) !== false;
        } finally {
            restore_error_handler();
        }
    }
}
