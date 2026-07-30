<?php
declare(strict_types=1);

namespace App\Repositories;

final class ContentRepository
{
    public static function activeDirectoria(): array
    {
        return db()->query('SELECT * FROM diretoria WHERE ativo = 1 ORDER BY ordem ASC, id ASC')->fetchAll();
    }

    public static function allDirectoria(): array
    {
        return db()->query('SELECT * FROM diretoria ORDER BY ativo DESC, ordem ASC, id DESC')->fetchAll();
    }

    public static function findDirectoria(int $id): ?array
    {
        return self::find('diretoria', $id);
    }

    public static function createDirectoria(string $nome, string $cargo, string $bio, int $ordem, int $ativo, ?string $foto): void
    {
        db()->prepare('INSERT INTO diretoria (nome, cargo, bio, foto, ordem, ativo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$nome, $cargo, $bio, $foto, $ordem, $ativo, date('c'), date('c')]);
    }

    public static function updateDirectoria(int $id, string $nome, string $cargo, string $bio, int $ordem, int $ativo, ?string $foto = null): void
    {
        if ($foto !== null) {
            db()->prepare('UPDATE diretoria SET nome=?, cargo=?, bio=?, foto=?, ordem=?, ativo=?, updated_at=? WHERE id=?')
                ->execute([$nome, $cargo, $bio, $foto, $ordem, $ativo, date('c'), $id]);
            return;
        }
        db()->prepare('UPDATE diretoria SET nome=?, cargo=?, bio=?, ordem=?, ativo=?, updated_at=? WHERE id=?')
            ->execute([$nome, $cargo, $bio, $ordem, $ativo, date('c'), $id]);
    }

    public static function deleteDirectoria(int $id): void
    {
        db()->prepare('DELETE FROM diretoria WHERE id=?')->execute([$id]);
    }

    public static function activeColaboradores(): array
    {
        return db()->query('SELECT * FROM colaboradores ORDER BY nome COLLATE NOCASE ASC, id ASC')->fetchAll();
    }

    public static function allColaboradores(): array
    {
        return self::activeColaboradores();
    }

    public static function findColaborador(int $id): ?array
    {
        return self::find('colaboradores', $id);
    }

    public static function createColaborador(string $nome, string $foto, string $descricao, string $siteUrl, string $socialUrl, string $whatsapp, string $pdf = ''): void
    {
        db()->prepare('INSERT INTO colaboradores (nome, foto, descricao, site_url, social_url, whatsapp, pdf, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$nome, $foto, $descricao, $siteUrl, $socialUrl, $whatsapp, $pdf, date('c'), date('c')]);
    }

    public static function updateColaborador(int $id, string $nome, string $descricao, string $siteUrl, string $socialUrl, string $whatsapp, ?string $foto = null, ?string $pdf = null): void
    {
        if ($foto !== null || $pdf !== null) {
            $fields = ['nome=?', 'descricao=?', 'site_url=?', 'social_url=?', 'whatsapp=?'];
            $values = [$nome, $descricao, $siteUrl, $socialUrl, $whatsapp];
            if ($foto !== null) {
                $fields[] = 'foto=?';
                $values[] = $foto;
            }
            if ($pdf !== null) {
                $fields[] = 'pdf=?';
                $values[] = $pdf;
            }
            $fields[] = 'updated_at=?';
            $values[] = date('c');
            $values[] = $id;
            db()->prepare('UPDATE colaboradores SET ' . implode(', ', $fields) . ' WHERE id=?')->execute($values);
            return;
        }
        db()->prepare('UPDATE colaboradores SET nome=?, descricao=?, site_url=?, social_url=?, whatsapp=?, updated_at=? WHERE id=?')
            ->execute([$nome, $descricao, $siteUrl, $socialUrl, $whatsapp, date('c'), $id]);
    }

    public static function deleteColaborador(int $id): void
    {
        db()->prepare('DELETE FROM colaboradores WHERE id=?')->execute([$id]);
    }

    public static function activeEventos(): array
    {
        return db()->query("SELECT * FROM eventos WHERE ativo = 1 AND (data_fim IS NULL OR datetime(data_fim) >= datetime('now')) ORDER BY datetime(data_inicio) ASC, id ASC")->fetchAll();
    }

    public static function allEventos(): array
    {
        return db()->query('SELECT * FROM eventos ORDER BY datetime(data_inicio) DESC, id DESC')->fetchAll();
    }

    public static function findEvento(int $id): ?array
    {
        return self::find('eventos', $id);
    }

    public static function createEvento(string $titulo, string $descricao, string $local, string $inicio, ?string $fim, int $ativo, ?string $banner): void
    {
        db()->prepare('INSERT INTO eventos (titulo, descricao, local, data_inicio, data_fim, banner, ativo, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$titulo, $descricao, $local, $inicio, $fim, $banner, $ativo, date('c'), date('c')]);
    }

    public static function updateEvento(int $id, string $titulo, string $descricao, string $local, string $inicio, ?string $fim, int $ativo, ?string $banner = null): void
    {
        if ($banner !== null) {
            db()->prepare('UPDATE eventos SET titulo=?, descricao=?, local=?, data_inicio=?, data_fim=?, banner=?, ativo=?, updated_at=? WHERE id=?')
                ->execute([$titulo, $descricao, $local, $inicio, $fim, $banner, $ativo, date('c'), $id]);
            return;
        }
        db()->prepare('UPDATE eventos SET titulo=?, descricao=?, local=?, data_inicio=?, data_fim=?, ativo=?, updated_at=? WHERE id=?')
            ->execute([$titulo, $descricao, $local, $inicio, $fim, $ativo, date('c'), $id]);
    }

    public static function deleteEvento(int $id): void
    {
        db()->prepare('DELETE FROM eventos WHERE id=?')->execute([$id]);
    }

    public static function galleryMetadata(): array
    {
        $metadata = [];
        foreach (db()->query('SELECT file_name, description, updated_at FROM gallery_images ORDER BY file_name ASC')->fetchAll() as $row) {
            $metadata[(string)$row['file_name']] = $row;
        }
        return $metadata;
    }

    public static function randomGalleryMetadata(): array
    {
        $metadata = [];
        foreach (db()->query('SELECT file_name, description, updated_at FROM gallery_images ORDER BY RANDOM()')->fetchAll() as $row) {
            $metadata[(string)$row['file_name']] = $row;
        }
        return $metadata;
    }

    public static function upsertGalleryImage(string $fileName, string $description): void
    {
        db()->prepare('INSERT INTO gallery_images (file_name, description, created_at, updated_at) VALUES (?, ?, ?, ?) ON CONFLICT(file_name) DO UPDATE SET description=excluded.description, updated_at=excluded.updated_at')
            ->execute([$fileName, $description, date('c'), date('c')]);
    }

    public static function deleteGalleryImage(string $fileName): void
    {
        db()->prepare('DELETE FROM gallery_images WHERE file_name=?')->execute([$fileName]);
    }

    public static function allUsers(): array
    {
        return db()->query('SELECT id, username, is_admin, created_at FROM users ORDER BY username ASC')->fetchAll();
    }

    private static function find(string $table, int $id): ?array
    {
        $allowed = ['diretoria', 'eventos', 'colaboradores'];
        if (!in_array($table, $allowed, true) || $id < 1) {
            return null;
        }
        $stmt = db()->prepare('SELECT * FROM ' . $table . ' WHERE id=?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
