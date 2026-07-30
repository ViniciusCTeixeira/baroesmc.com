<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $storageParent = dirname(DB_PATH);
    if (!is_dir($storageParent) && !mkdir($storageParent, 0700, true) && !is_dir($storageParent)) {
        throw new RuntimeException('Não foi possível criar o diretório de armazenamento.');
    }

    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA journal_mode = WAL');

    migrate($pdo);
    return $pdo;
}

function migrate(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
        version TEXT PRIMARY KEY,
        applied_at TEXT NOT NULL
    )');

    $migrationDir = APP_ROOT . '/database/migrations';
    $files = glob($migrationDir . '/*.sql') ?: [];
    sort($files, SORT_STRING);

    foreach ($files as $file) {
        $version = basename($file);
        $stmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = ?');
        $stmt->execute([$version]);
        if ($stmt->fetchColumn()) {
            continue;
        }

        $pdo->beginTransaction();
        try {
            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new RuntimeException('Não foi possível ler a migration ' . $version);
            }
            $pdo->exec($sql);
            $pdo->prepare('INSERT INTO schema_migrations (version, applied_at) VALUES (?, ?)')
                ->execute([$version, date('c')]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    // Compatibilidade com o banco antigo, criado antes das migrations.
    foreach (['users', 'diretoria', 'eventos', 'colaboradores'] as $table) {
        ensure_column($pdo, $table, 'updated_at', 'TEXT DEFAULT NULL');
    }

    \App\Repositories\SettingsRepository::seedDefaults();

    $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $initialUser = trim((string)(getenv('BAROES_ADMIN_USERNAME') ?: ''));
    $initialPassword = (string)(getenv('BAROES_ADMIN_PASSWORD') ?: '');
    if ($count === 0 && $initialUser !== '' && strlen($initialPassword) >= MIN_PASSWORD_LENGTH) {
        $pdo->prepare('INSERT INTO users (username, password_hash, is_admin, created_at, updated_at) VALUES (?, ?, 1, ?, ?)')
            ->execute([$initialUser, password_hash($initialPassword, PASSWORD_DEFAULT), date('c'), date('c')]);
    }
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $columns = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll();
    foreach ($columns as $existing) {
        if ((string)$existing['name'] === $column) {
            return;
        }
    }
    $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}
