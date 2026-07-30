<?php
declare(strict_types=1);

$temporaryDatabase = tempnam(sys_get_temp_dir(), 'baroesmc-smoke-');
if ($temporaryDatabase === false) {
    throw new RuntimeException('Não foi possível criar banco temporário.');
}
unlink($temporaryDatabase);
putenv('BAROES_DB_PATH=' . $temporaryDatabase);

require dirname(__DIR__) . '/app/bootstrap.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $pdo = db();
    check((int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 5, 'Migrations não aplicadas.');
    check((int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='gallery_images'")->fetchColumn() === 1, 'Tabela de descrições da galeria não foi criada.');
    check((int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='colaboradores'")->fetchColumn() === 1, 'Tabela de colaboradores não foi criada.');
    check((int)$pdo->query("SELECT COUNT(*) FROM pragma_table_info('colaboradores') WHERE name='pdf'")->fetchColumn() === 1, 'Campo de PDF dos colaboradores não foi criado.');
    check((int)$pdo->query('SELECT COUNT(*) FROM site_settings')->fetchColumn() >= 30, 'Configurações padrão não foram criadas.');
    check((int)$pdo->query('SELECT COUNT(*) FROM diretoria')->fetchColumn() === 0, 'Banco temporário não está vazio.');
    check(e('<script>') === '&lt;script&gt;', 'Escape HTML falhou.');

    \App\Repositories\ContentRepository::upsertGalleryImage('smoke.jpg', 'Descrição de teste');
    $galleryMetadata = \App\Repositories\ContentRepository::galleryMetadata();
    check(($galleryMetadata['smoke.jpg']['description'] ?? '') === 'Descrição de teste', 'Descrição da galeria não foi salva.');
    \App\Repositories\ContentRepository::deleteGalleryImage('smoke.jpg');

    \App\Repositories\ContentRepository::createColaborador('Colaborador Smoke', '', 'Descrição', 'https://example.com', 'https://instagram.com/example', '+55 24 99999-9999', 'smoke.pdf');
    $colaboradores = \App\Repositories\ContentRepository::allColaboradores();
    check(count($colaboradores) === 1 && $colaboradores[0]['nome'] === 'Colaborador Smoke' && $colaboradores[0]['pdf'] === 'smoke.pdf', 'Colaborador não foi criado.');
    \App\Repositories\ContentRepository::updateColaborador((int)$colaboradores[0]['id'], 'Colaborador Atualizado', 'Nova descrição', '', '', '');
    check(\App\Repositories\ContentRepository::findColaborador((int)$colaboradores[0]['id'])['nome'] === 'Colaborador Atualizado', 'Colaborador não foi atualizado.');
    \App\Repositories\ContentRepository::deleteColaborador((int)$colaboradores[0]['id']);
    check(count(\App\Repositories\ContentRepository::allColaboradores()) === 0, 'Colaborador não foi removido.');

    $pdo->prepare('INSERT INTO users (username, password_hash, is_admin, created_at) VALUES (?, ?, ?, ?)')
        ->execute(['smoke', password_hash('senha-de-teste-123', PASSWORD_DEFAULT), 1, date('c')]);
    check(password_verify('senha-de-teste-123', (string)$pdo->query("SELECT password_hash FROM users WHERE username = 'smoke'")->fetchColumn()), 'Hash de senha falhou.');
    echo "SMOKE_TEST_OK\n";
} finally {
    foreach ([$temporaryDatabase, $temporaryDatabase . '-wal', $temporaryDatabase . '-shm'] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}
