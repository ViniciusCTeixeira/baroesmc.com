<?php
declare(strict_types=1);

use App\Repositories\ContentRepository;
use App\Repositories\SettingsRepository;
use App\Services\UploadService;

final class AdminController
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = (string)($_POST['action'] ?? '');
        if ($action === 'login') {
            $this->login();
            return;
        }

        require_login();
        if (($_POST['resource'] ?? '') === 'diretoria') {
            $this->handleDirectoria();
            return;
        }
        if (($_POST['resource'] ?? '') === 'eventos') {
            $this->handleEventos();
            return;
        }
        if (($_POST['resource'] ?? '') === 'colaboradores') {
            $this->handleColaboradores();
            return;
        }
        if (($_POST['resource'] ?? '') === 'site-settings') {
            $this->handleSiteSettings();
            return;
        }

        switch ($action) {
            case 'upload':
                $this->uploadGallery();
                return;
            case 'delete':
                $this->deleteGalleryImage();
                return;
            case 'gallery_update':
                $this->updateGalleryDescription();
                return;
            case 'change_pass':
                $this->changePassword();
                return;
            case 'user_create':
                $this->createUser();
                return;
            case 'user_update':
                $this->updateUser();
                return;
            case 'user_delete':
                $this->deleteUser();
                return;
            default:
                redirect('admin.php');
        }
    }

    private function login(): void
    {
        csrf_check();
        $username = normalize_text((string)($_POST['username'] ?? ''), MAX_USERNAME_LENGTH);
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            flash_add('danger', 'Informe usuário e senha.');
            return;
        }
        if (!login_try($username, $password)) {
            flash_add('danger', 'Credenciais inválidas.');
            return;
        }
        redirect('admin.php');
    }

    private function uploadGallery(): void
    {
        require_admin();
        csrf_check();
        $files = $_FILES['images'] ?? null;
        if (!is_array($files) || !isset($files['name']) || !is_array($files['name'])) {
            flash_add('danger', 'Selecione ao menos um arquivo.');
            redirect('admin.php');
        }

        $description = normalize_text((string)($_POST['gallery_description'] ?? ''), MAX_GALLERY_DESCRIPTION_LENGTH);
        $count = count($files['name']);
        for ($index = 0; $index < $count; $index++) {
            $file = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
            $originalName = safe_basename((string)$file['name']);
            try {
                $newFile = UploadService::store($file, GALLERY_DIR);
                if ($newFile !== null) {
                    ContentRepository::upsertGalleryImage($newFile, $description);
                }
                flash_add('success', $originalName . ': enviado com sucesso.');
            } catch (Throwable $exception) {
                flash_add('warning', $originalName . ': ' . $this->messageForUser($exception));
            }
        }
        redirect('admin.php');
    }

    private function deleteGalleryImage(): void
    {
        require_admin();
        csrf_check();
        $file = safe_basename((string)($_POST['file'] ?? ''));
        if ($file === '') {
            flash_add('danger', 'Arquivo inválido.');
            redirect('admin.php');
        }
        try {
            UploadService::delete($file, GALLERY_DIR);
            ContentRepository::deleteGalleryImage($file);
            flash_add('success', 'Imagem removida.');
        } catch (Throwable $exception) {
            flash_add('danger', $this->messageForUser($exception));
        }
        redirect('admin.php');
    }

    private function updateGalleryDescription(): void
    {
        require_admin();
        csrf_check();
        $file = safe_basename((string)($_POST['file'] ?? ''));
        $page = max(1, (int)($_POST['page'] ?? 1));
        $path = GALLERY_DIR . DIRECTORY_SEPARATOR . $file;
        if ($file === '' || !is_file($path) || !is_allowed_ext(ext_of($file))) {
            flash_add('danger', 'Imagem inválida.');
            redirect('admin.php?page=' . $page . '#tab-gallery');
        }

        $description = normalize_text((string)($_POST['description'] ?? ''), MAX_GALLERY_DESCRIPTION_LENGTH);
        ContentRepository::upsertGalleryImage($file, $description);
        flash_add('success', 'Descrição da imagem atualizada.');
        redirect('admin.php?page=' . $page . '#tab-gallery');
    }

    private function changePassword(): void
    {
        csrf_check();
        $current = (string)($_POST['current'] ?? '');
        $newPassword = (string)($_POST['new1'] ?? '');
        $confirmation = (string)($_POST['new2'] ?? '');
        if (strlen($newPassword) < MIN_PASSWORD_LENGTH || strlen($newPassword) > MAX_PASSWORD_LENGTH) {
            flash_add('danger', 'A nova senha deve ter entre ' . MIN_PASSWORD_LENGTH . ' e ' . MAX_PASSWORD_LENGTH . ' caracteres.');
        } elseif ($newPassword !== $confirmation) {
            flash_add('danger', 'As novas senhas não conferem.');
        } else {
            $user = current_user();
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($current, (string)$hash)) {
                flash_add('danger', 'Senha atual incorreta.');
            } else {
                db()->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?')
                    ->execute([password_hash($newPassword, PASSWORD_DEFAULT), date('c'), $user['id']]);
                flash_add('success', 'Senha alterada com sucesso.');
            }
        }
        redirect('admin.php#tab-password');
    }

    private function createUser(): void
    {
        require_admin();
        csrf_check();
        $username = normalize_text((string)($_POST['new_username'] ?? ''), MAX_USERNAME_LENGTH);
        $password = (string)($_POST['new_password'] ?? '');
        $isAdmin = isset($_POST['new_is_admin']) ? 1 : 0;
        if (!preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
            flash_add('danger', 'Usuário inválido. Use de 3 a 80 caracteres: letras, números, ponto, hífen ou sublinhado.');
        } elseif (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH) {
            flash_add('danger', 'A senha deve ter entre ' . MIN_PASSWORD_LENGTH . ' e ' . MAX_PASSWORD_LENGTH . ' caracteres.');
        } else {
            try {
                db()->prepare('INSERT INTO users (username, password_hash, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $isAdmin, date('c'), date('c')]);
                flash_add('success', 'Usuário criado.');
            } catch (Throwable $exception) {
                flash_add('danger', 'Não foi possível criar o usuário. Verifique se ele já existe.');
            }
        }
        redirect('admin.php#tab-users');
    }

    private function deleteUser(): void
    {
        require_admin();
        csrf_check();
        $id = valid_id($_POST['uid'] ?? 0);
        $me = current_user();
        if ($id === 0 || $id === (int)$me['id']) {
            flash_add('danger', 'Usuário inválido ou operação não permitida.');
        } else {
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            flash_add('success', 'Usuário removido.');
        }
        redirect('admin.php#tab-users');
    }

    private function updateUser(): void
    {
        require_admin();
        csrf_check();
        $id = valid_id($_POST['uid'] ?? 0);
        $me = current_user();
        $username = normalize_text((string)($_POST['edit_username'] ?? ''), MAX_USERNAME_LENGTH);
        $password = (string)($_POST['edit_password'] ?? '');
        $isAdmin = isset($_POST['edit_is_admin']) ? 1 : 0;

        if ($id === 0 || $username === '' || !preg_match('/^[A-Za-z0-9._-]{3,80}$/', $username)) {
            flash_add('danger', 'Dados do usuário inválidos.');
        } elseif ($password !== '' && (strlen($password) < MIN_PASSWORD_LENGTH || strlen($password) > MAX_PASSWORD_LENGTH)) {
            flash_add('danger', 'A nova senha deve ter entre ' . MIN_PASSWORD_LENGTH . ' e ' . MAX_PASSWORD_LENGTH . ' caracteres.');
        } else {
            if ($id === (int)$me['id']) {
                $isAdmin = 1;
            }
            try {
                if ($password !== '') {
                    db()->prepare('UPDATE users SET username=?, password_hash=?, is_admin=?, updated_at=? WHERE id=?')
                        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $isAdmin, date('c'), $id]);
                } else {
                    db()->prepare('UPDATE users SET username=?, is_admin=?, updated_at=? WHERE id=?')
                        ->execute([$username, $isAdmin, date('c'), $id]);
                }
                flash_add('success', 'Usuário atualizado.');
            } catch (Throwable $exception) {
                flash_add('danger', 'Não foi possível atualizar o usuário. Verifique se o nome já existe.');
            }
        }
        redirect('admin.php#tab-users');
    }

    private function handleDirectoria(): void
    {
        require_admin();
        csrf_check();
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'create') {
                $nome = normalize_text((string)($_POST['nome'] ?? ''), 150);
                $cargo = normalize_text((string)($_POST['cargo'] ?? ''), 150);
                if ($nome === '' || $cargo === '') {
                    throw new RuntimeException('Nome e cargo são obrigatórios.');
                }
                $foto = UploadService::store($_FILES['foto'] ?? [], DIRETORIA_DIR);
                ContentRepository::createDirectoria($nome, $cargo, normalize_text((string)($_POST['bio'] ?? '')), (int)($_POST['ordem'] ?? 0), $this->activeValue(), $foto);
                flash_add('success', 'Membro adicionado.');
            } elseif ($action === 'update') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = ContentRepository::findDirectoria($id);
                if (!$old) {
                    throw new RuntimeException('Membro não encontrado.');
                }
                $nome = normalize_text((string)($_POST['nome'] ?? ''), 150);
                $cargo = normalize_text((string)($_POST['cargo'] ?? ''), 150);
                if ($nome === '' || $cargo === '') {
                    throw new RuntimeException('Nome e cargo são obrigatórios.');
                }
                $foto = UploadService::store($_FILES['foto'] ?? [], DIRETORIA_DIR);
                ContentRepository::updateDirectoria($id, $nome, $cargo, normalize_text((string)($_POST['bio'] ?? '')), (int)($_POST['ordem'] ?? 0), $this->activeValue(), $foto);
                if ($foto) {
                    UploadService::delete((string)$old['foto'], DIRETORIA_DIR);
                }
                flash_add('success', 'Membro atualizado.');
            } elseif ($action === 'delete') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = ContentRepository::findDirectoria($id);
                if (!$old) {
                    throw new RuntimeException('Membro não encontrado.');
                }
                ContentRepository::deleteDirectoria($id);
                UploadService::delete((string)$old['foto'], DIRETORIA_DIR);
                flash_add('success', 'Membro removido.');
            }
        } catch (Throwable $exception) {
            flash_add('danger', $this->messageForUser($exception));
        }
        redirect('admin.php#tab-diretoria');
    }

    private function handleSiteSettings(): void
    {
        require_admin();
        csrf_check();
        $defaults = SettingsRepository::defaults();
        $textKeys = array_diff(array_keys($defaults), [
            'logo_path', 'banner_path', 'favicon_path', 'favicon_mime', 'audio_path', 'audio_enabled', 'audio_volume',
        ]);
        $values = [];
        foreach ($textKeys as $key) {
            $max = str_ends_with($key, '_text') ? MAX_TEXT_LENGTH : 255;
            $values[$key] = normalize_text((string)($_POST[$key] ?? $defaults[$key]), $max);
        }
        $values['audio_enabled'] = ((string)($_POST['audio_enabled'] ?? '1') === '1') ? '1' : '0';
        $volume = (float)($_POST['audio_volume'] ?? 0.2);
        $values['audio_volume'] = (string)max(0, min(1, $volume));

        $current = SettingsRepository::all();
        $newFiles = [];
        $replacedFiles = [];
        $uploads = [
            'logo_file' => ['setting' => 'logo_path', 'extensions' => SITE_IMAGE_EXT, 'mimes' => SITE_IMAGE_MIME, 'image' => true],
            'banner_file' => ['setting' => 'banner_path', 'extensions' => SITE_IMAGE_EXT, 'mimes' => SITE_IMAGE_MIME, 'image' => true],
            'favicon_file' => ['setting' => 'favicon_path', 'extensions' => SITE_IMAGE_EXT, 'mimes' => SITE_IMAGE_MIME, 'image' => true],
            'audio_file' => ['setting' => 'audio_path', 'extensions' => AUDIO_EXT, 'mimes' => AUDIO_MIME, 'image' => false],
        ];
        try {
            foreach ($uploads as $field => $definition) {
                if (!isset($_FILES[$field]) || (int)($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $newFile = UploadService::store($_FILES[$field], SITE_MEDIA_DIR, $definition['extensions'], $definition['mimes'], $definition['image']);
                if ($newFile) {
                    $setting = $definition['setting'];
                    $values[$setting] = $newFile;
                    $newFiles[] = $newFile;
                    if (!empty($current[$setting])) {
                        $replacedFiles[] = (string)$current[$setting];
                    }
                    if ($setting === 'favicon_path') {
                        $values['favicon_mime'] = (string)($_FILES[$field]['type'] ?? 'image/x-icon');
                    }
                }
            }
            SettingsRepository::setMany($values);
            foreach ($replacedFiles as $oldFile) {
                UploadService::delete($oldFile, SITE_MEDIA_DIR);
            }
            flash_add('success', 'Configurações do site atualizadas.');
        } catch (Throwable $exception) {
            foreach ($newFiles as $newFile) {
                try {
                    UploadService::delete($newFile, SITE_MEDIA_DIR);
                } catch (Throwable) {
                    // Não substitui a mensagem principal da operação.
                }
            }
            flash_add('danger', $this->messageForUser($exception));
        }
        redirect('admin.php#tab-site');
    }

    private function handleColaboradores(): void
    {
        require_admin();
        csrf_check();
        $action = (string)($_POST['action'] ?? '');
        $newPhoto = null;
        $newPdf = null;
        try {
            if ($action === 'create' || $action === 'update') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = $action === 'update' ? ContentRepository::findColaborador($id) : null;
                if ($action === 'update' && !$old) {
                    throw new RuntimeException('Colaborador não encontrado.');
                }

                $nome = normalize_text((string)($_POST['nome'] ?? ''), 150);
                $descricao = normalize_text((string)($_POST['descricao'] ?? ''), MAX_TEXT_LENGTH);
                $siteUrl = $this->optionalExternalUrl($_POST['site_url'] ?? null, 'O link do site');
                $socialUrl = $this->optionalExternalUrl($_POST['social_url'] ?? null, 'O link da rede social');
                $whatsapp = normalize_whatsapp((string)($_POST['whatsapp'] ?? ''));
                if ($nome === '') {
                    throw new RuntimeException('O nome é obrigatório.');
                }

                $newPhoto = UploadService::store($_FILES['foto'] ?? [], COLABORADORES_DIR);
                $newPdf = UploadService::store($_FILES['pdf'] ?? [], COLABORADORES_PDF_DIR, PDF_EXT, PDF_MIME, false);
                if ($action === 'create') {
                    ContentRepository::createColaborador($nome, $newPhoto ?? '', $descricao, $siteUrl, $socialUrl, $whatsapp, $newPdf ?? '');
                    flash_add('success', 'Colaborador adicionado.');
                } else {
                    ContentRepository::updateColaborador($id, $nome, $descricao, $siteUrl, $socialUrl, $whatsapp, $newPhoto, $newPdf);
                    if ($newPhoto !== null) {
                        UploadService::delete((string)$old['foto'], COLABORADORES_DIR);
                    }
                    if ($newPdf !== null) {
                        UploadService::delete((string)($old['pdf'] ?? ''), COLABORADORES_PDF_DIR);
                    }
                    flash_add('success', 'Colaborador atualizado.');
                }
            } elseif ($action === 'delete') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = ContentRepository::findColaborador($id);
                if (!$old) {
                    throw new RuntimeException('Colaborador não encontrado.');
                }
                ContentRepository::deleteColaborador($id);
                UploadService::delete((string)$old['foto'], COLABORADORES_DIR);
                UploadService::delete((string)($old['pdf'] ?? ''), COLABORADORES_PDF_DIR);
                flash_add('success', 'Colaborador removido.');
            }
        } catch (Throwable $exception) {
            if ($newPhoto !== null) {
                try {
                    UploadService::delete($newPhoto, COLABORADORES_DIR);
                } catch (Throwable) {
                    // Mantém a mensagem principal da operação.
                }
            }
            if ($newPdf !== null) {
                try {
                    UploadService::delete($newPdf, COLABORADORES_PDF_DIR);
                } catch (Throwable) {
                    // Mantém a mensagem principal da operação.
                }
            }
            flash_add('danger', $this->messageForUser($exception));
        }
        redirect('admin.php#tab-colaboradores');
    }

    private function handleEventos(): void
    {
        require_admin();
        csrf_check();
        $action = (string)($_POST['action'] ?? '');
        try {
            if ($action === 'create' || $action === 'update') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = $action === 'update' ? ContentRepository::findEvento($id) : null;
                if ($action === 'update' && !$old) {
                    throw new RuntimeException('Evento não encontrado.');
                }
                $titulo = normalize_text((string)($_POST['titulo'] ?? ''), 200);
                $inicio = $this->dateValue($_POST['data_inicio'] ?? null, false);
                $fim = $this->dateValue($_POST['data_fim'] ?? null, true);
                if ($titulo === '' || $inicio === null) {
                    throw new RuntimeException('Título e data de início são obrigatórios e devem ser válidos.');
                }
                if ($fim !== null && $fim < $inicio) {
                    throw new RuntimeException('A data final não pode ser anterior à data inicial.');
                }
                $banner = UploadService::store($_FILES['banner'] ?? [], EVENTOS_DIR);
                $description = normalize_text((string)($_POST['descricao'] ?? ''));
                $local = normalize_text((string)($_POST['local'] ?? ''), 200);
                if ($action === 'create') {
                    ContentRepository::createEvento($titulo, $description, $local, $inicio, $fim, $this->activeValue(), $banner);
                    flash_add('success', 'Evento adicionado.');
                } else {
                    ContentRepository::updateEvento($id, $titulo, $description, $local, $inicio, $fim, $this->activeValue(), $banner);
                    if ($banner) {
                        UploadService::delete((string)$old['banner'], EVENTOS_DIR);
                    }
                    flash_add('success', 'Evento atualizado.');
                }
            } elseif ($action === 'delete') {
                $id = valid_id($_POST['id'] ?? 0);
                $old = ContentRepository::findEvento($id);
                if (!$old) {
                    throw new RuntimeException('Evento não encontrado.');
                }
                ContentRepository::deleteEvento($id);
                UploadService::delete((string)$old['banner'], EVENTOS_DIR);
                flash_add('success', 'Evento removido.');
            }
        } catch (Throwable $exception) {
            flash_add('danger', $this->messageForUser($exception));
        }
        redirect('admin.php#tab-eventos');
    }

    private function messageForUser(Throwable $exception): string
    {
        if ($exception instanceof RuntimeException && !$exception instanceof PDOException) {
            return $exception->getMessage();
        }
        return 'Não foi possível concluir a operação. Consulte o log do servidor.';
    }

    private function activeValue(): int
    {
        return (int)((int)($_POST['ativo'] ?? 1) === 1);
    }

    private function optionalExternalUrl(mixed $value, string $label): string
    {
        $url = normalize_text((string)$value, 500);
        if ($url !== '' && external_url($url) === '#') {
            throw new RuntimeException($label . ' deve começar com http:// ou https://.');
        }
        return $url;
    }

    private function dateValue(mixed $value, bool $nullable): ?string
    {
        $value = trim((string)$value);
        if ($value === '' && $nullable) {
            return null;
        }
        $date = DateTime::createFromFormat('Y-m-d\\TH:i', $value);
        $errors = DateTime::getLastErrors();
        if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }
        return $date->format('Y-m-d H:i:00');
    }
}
