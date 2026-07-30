<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function normalize_text(string $value, int $maxLength = MAX_TEXT_LENGTH): string
{
    $value = trim($value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $maxLength) : substr($value, 0, $maxLength);
}

function normalize_whatsapp(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $digits = (string)preg_replace('/\D+/', '', $value);
    if (substr($digits, 0, 2) !== '55') {
        throw new RuntimeException('O WhatsApp deve começar com +55.');
    }

    $national = substr($digits, 2);
    if (strlen($national) === 11 && $national[0] === '0') {
        $national = substr($national, 1);
    }

    $nationalLength = strlen($national);
    if (!in_array($nationalLength, [10, 11], true)) {
        throw new RuntimeException('Informe o DDD e um telefone com 8 ou 9 dígitos.');
    }

    $ddd = substr($national, 0, 2);
    $phone = substr($national, 2);
    $phoneFormatted = strlen($phone) === 9
        ? substr($phone, 0, 5) . '-' . substr($phone, 5)
        : substr($phone, 0, 4) . '-' . substr($phone, 4);

    return '+55 (' . $ddd . ') ' . $phoneFormatted;
}

function format_whatsapp(string $value): string
{
    try {
        return normalize_whatsapp($value);
    } catch (Throwable) {
        return trim($value);
    }
}

function valid_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    return $id === false ? 0 : (int)$id;
}

function site_media_url(?string $filename): string
{
    $filename = basename((string)$filename);
    return $filename === '' || $filename === '.' ? '' : SITE_MEDIA_WEB . '/' . rawurlencode($filename);
}

function external_url(string $url, string $fallback = '#'): string
{
    $parts = parse_url(trim($url));
    if (!$parts || !isset($parts['scheme']) || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
        return $fallback;
    }
    return $url;
}

function paragraphs_html(string $text): string
{
    $paragraphs = preg_split('/\R{2,}/', trim($text)) ?: [];
    $html = '';
    foreach ($paragraphs as $paragraph) {
        if (trim($paragraph) !== '') {
            $html .= '<p>' . nl2br(e($paragraph)) . '</p>';
        }
    }
    return $html;
}

function lines_html(string $text): string
{
    $html = '';
    foreach (preg_split('/\R/', trim($text)) ?: [] as $line) {
        if (trim($line) !== '') {
            $html .= '<li>' . e($line) . '</li>';
        }
    }
    return $html;
}

function format_admin_datetime(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '—';
    }

    try {
        return (new DateTime($value))->format('d/m/Y · H:i');
    } catch (Throwable) {
        return $value;
    }
}
