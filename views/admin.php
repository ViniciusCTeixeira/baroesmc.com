<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';
require_once dirname(__DIR__) . '/app/Controllers/AdminController.php';

$controller = new AdminController();
$controller->handle();

$flashes = flash_get();
$user = current_user();
$page = max(1, (int)($_GET['page'] ?? 1));
$allImages = list_images();
[$items, $currentPage, $pages, $total] = paginate($allImages, $page, PAGE_SIZE);
$galleryMetadata = $user && $user['is_admin'] ? \App\Repositories\ContentRepository::galleryMetadata() : [];
$describedGalleryCount = 0;
foreach ($allImages as $galleryFile) {
    if (trim((string)($galleryMetadata[$galleryFile]['description'] ?? '')) !== '') {
        $describedGalleryCount++;
    }
}

$directoria = $user && $user['is_admin'] ? \App\Repositories\ContentRepository::allDirectoria() : [];
$eventos = $user && $user['is_admin'] ? \App\Repositories\ContentRepository::allEventos() : [];
$colaboradores = $user && $user['is_admin'] ? \App\Repositories\ContentRepository::allColaboradores() : [];
$users = $user && $user['is_admin'] ? \App\Repositories\ContentRepository::allUsers() : [];
$activeDirectoria = 0;
foreach ($directoria as $row) {
    $activeDirectoria += ((int)($row['ativo'] ?? 0) === 1) ? 1 : 0;
}
$settings = \App\Repositories\SettingsRepository::all();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Administração — <?= e($settings['site_name'] ?? 'Site') ?></title>
    <link rel="icon" href="<?= e(site_media_url($settings['favicon_path'] ?? '')) ?>" type="<?= e($settings['favicon_mime'] ?? 'image/x-icon') ?>">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="admin-page">
<main class="admin-shell">
    <header class="admin-topbar">
        <a class="admin-brand" href="index.php" aria-label="Voltar para o site">
            <span class="admin-brand-mark"><img src="<?= e(site_media_url($settings['logo_path'] ?? '')) ?>" alt="" aria-hidden="true"></span>
            <span class="admin-brand-copy"><strong><?= e($settings['site_name'] ?? 'Site') ?></strong><small>PAINEL DE CONTROLE</small></span>
        </a>
        <div class="admin-topbar-actions">
            <?php if ($user): ?>
                <span class="admin-user-chip"><span class="admin-user-avatar"><?= e(strtoupper(substr($user['username'], 0, 1))) ?></span><span><small>Logado como</small><strong><?= e($user['username']) ?></strong></span></span>
                <form method="post" action="logout.php" class="m-0">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <button class="btn btn-quiet btn-sm" type="submit"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Sair</button>
                </form>
            <?php endif; ?>
            <a class="btn btn-outline-secondary btn-sm" href="index.php"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Ver site</a>
        </div>
    </header>

    <div class="admin-page-heading"><div><span class="admin-eyebrow">Área restrita</span><h1>Painel administrativo</h1><p>Gerencie o conteúdo e a presença digital de <?= e($settings['site_name'] ?? 'seu site') ?>.</p></div><?php if ($user): ?><span class="admin-status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Sessão protegida</span><?php endif; ?></div>

    <?php foreach ($flashes as $flash):
        $type = in_array($flash[0] ?? '', ['success', 'danger', 'warning', 'info'], true) ? $flash[0] : 'info';
        ?>
        <div class="alert alert-<?= e($type) ?>" role="alert"><?= e($flash[1] ?? '') ?></div>
    <?php endforeach; ?>

    <?php if (!$user): ?>
        <section class="admin-login-layout">
            <div class="admin-login-intro"><span class="admin-eyebrow">Bem-vindo</span><h2>Seu conteúdo, sob controle.</h2><p>Entre no painel para atualizar textos, mídias, eventos, diretoria e galeria.</p><div class="admin-login-line"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Acesso protegido por sessão e autenticação.</span></div></div>
            <div class="card p-4 admin-login-card">
                    <div class="admin-card-heading"><span class="admin-icon-badge"><i class="fa-solid fa-lock" aria-hidden="true"></i></span><div><h2 class="h4 text-white mb-1">Entrar no painel</h2><p class="text-secondary mb-0">Use suas credenciais para continuar.</p></div></div>
                    <form method="post" autocomplete="on">
                        <input type="hidden" name="action" value="login">
                        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <div class="mb-3">
                            <label class="form-label text-white" for="username">Usuário</label>
                            <input id="username" type="text" class="form-control" name="username" maxlength="80" required autofocus autocomplete="username">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-white" for="password">Senha</label>
                            <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">
                        </div>
                        <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Entrar</button>
                    </form>
            </div>
        </section>
    <?php else: ?>
        <div class="alert admin-notice"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>Altere sua senha regularmente e mantenha o acesso administrativo restrito.</span></div>
        <div class="admin-nav-wrap"><span class="admin-nav-title">Gerenciar</span><ul class="nav nav-tabs admin-main-nav" id="adminTabs" role="tablist">
            <?php if ($user['is_admin']): ?>
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-site" role="tab"><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Site</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-gallery" role="tab"><i class="fa-solid fa-images" aria-hidden="true"></i><span>Galeria</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-diretoria" role="tab"><i class="fa-solid fa-people-group" aria-hidden="true"></i><span>Diretoria</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-colaboradores" role="tab"><i class="fa-solid fa-handshake" aria-hidden="true"></i><span>Colaboradores</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-eventos" role="tab"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i><span>Eventos</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-users" role="tab"><i class="fa-solid fa-users" aria-hidden="true"></i><span>Usuários</span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link<?= !$user['is_admin'] ? ' active' : '' ?>" data-bs-toggle="tab" href="#tab-password" role="tab"><i class="fa-solid fa-key" aria-hidden="true"></i><span>Minha senha</span></a></li>
        </ul></div>

        <div class="tab-content">
            <?php if ($user['is_admin']): ?>
                <section class="tab-pane fade show active" id="tab-site" role="tabpanel">
                    <form method="post" enctype="multipart/form-data" class="admin-settings-form">
                        <input type="hidden" name="resource" value="site-settings"><input type="hidden" name="action" value="update"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                        <div class="card p-4 mb-4 site-settings-intro">
                            <div class="site-settings-intro-heading"><div><span class="site-settings-kicker">Configuração do site</span><h2 class="h3 text-white mb-2">Organize a presença do seu site</h2><p class="text-secondary mb-0">Use as seções abaixo para editar identidade, textos e mídias. As alterações só entram no ar depois de clicar em <strong>Salvar alterações</strong>.</p></div><span class="gallery-count admin-summary-counter"><strong>3</strong><small>seções</small><span class="gallery-count-meta"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>identidade <b>·</b> conteúdo <b>·</b> mídia</span></span></div>
                            <nav class="nav nav-pills site-settings-nav mt-4" aria-label="Seções das configurações do site">
                                <button class="nav-link active" type="button" data-site-panel="identity"><span>1</span> Identidade e SEO</button>
                                <button class="nav-link" type="button" data-site-panel="content"><span>2</span> Conteúdo</button>
                                <button class="nav-link" type="button" data-site-panel="media"><span>3</span> Mídia</button>
                            </nav>
                        </div>
                        <div class="card p-4 mb-4 site-settings-panel admin-form-card is-active" data-site-panel="identity">
                            <div class="site-settings-section-heading admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-globe" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Etapa 1</span><h2 class="h4 text-white mb-1">Identidade e SEO</h2><p class="text-secondary mb-0">Como o site aparece no navegador e ao ser compartilhado.</p></div><span class="site-settings-step">1 de 3</span></div>
                            <div class="row g-3">
                                <div class="col-md-4"><label class="form-label" for="site_name">Nome do site</label><input id="site_name" name="site_name" class="form-control" maxlength="255" value="<?= e($settings['site_name']) ?>" required></div>
                                <div class="col-md-8"><label class="form-label" for="site_title">Título da página</label><input id="site_title" name="site_title" class="form-control" maxlength="255" value="<?= e($settings['site_title']) ?>" required></div>
                                <div class="col-md-6"><label class="form-label" for="site_description">Descrição <span class="text-secondary">(opcional)</span></label><textarea id="site_description" name="site_description" class="form-control" rows="2" maxlength="255"><?= e($settings['site_description']) ?></textarea></div>
                                <div class="col-md-6"><label class="form-label" for="og_description">Descrição para compartilhamento <span class="text-secondary">(opcional)</span></label><textarea id="og_description" name="og_description" class="form-control" rows="2" maxlength="255"><?= e($settings['og_description']) ?></textarea></div>
                                <div class="col-md-8"><label class="form-label" for="instagram_url">URL do Instagram <span class="text-secondary">(opcional)</span></label><input id="instagram_url" name="instagram_url" type="url" class="form-control" maxlength="255" value="<?= e($settings['instagram_url']) ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="footer_text">Texto do rodapé <span class="text-secondary">(opcional)</span></label><input id="footer_text" name="footer_text" class="form-control" maxlength="255" value="<?= e($settings['footer_text']) ?>"></div>
                            </div>
                        </div>
                        <div class="card p-4 mb-4 site-settings-panel admin-form-card" data-site-panel="content">
                            <div class="site-settings-section-heading admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Etapa 2</span><h2 class="h4 text-white mb-1">Conteúdo</h2><p class="text-secondary mb-0">Textos e títulos exibidos nas seções da página inicial.</p></div><span class="site-settings-step">2 de 3</span></div>
                            <div class="row g-3">
                                <?php foreach (['nav_home_label'=>'Início','nav_about_label'=>'Sobre','nav_mission_label'=>'Missão','nav_board_label'=>'Diretoria','nav_events_label'=>'Eventos','nav_gallery_label'=>'Galeria'] as $key => $label): ?><div class="col-md-4"><label class="form-label" for="<?= e($key) ?>">Menu: <?= e($label) ?> <span class="text-secondary">(opcional)</span></label><input id="<?= e($key) ?>" name="<?= e($key) ?>" class="form-control" maxlength="255" value="<?= e($settings[$key]) ?>"></div><?php endforeach; ?>
                                <div class="col-md-6"><label class="form-label" for="about_title">Título “Sobre” <span class="text-secondary">(opcional)</span></label><input id="about_title" name="about_title" class="form-control" maxlength="255" value="<?= e($settings['about_title']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="vest_title">Título “Colete” <span class="text-secondary">(opcional)</span></label><input id="vest_title" name="vest_title" class="form-control" maxlength="255" value="<?= e($settings['vest_title']) ?>"></div>
                                <div class="col-12"><label class="form-label" for="about_text">Texto “Sobre” <span class="text-secondary">(opcional)</span></label><textarea id="about_text" name="about_text" class="form-control" rows="7" maxlength="<?= MAX_TEXT_LENGTH ?>"><?= e($settings['about_text']) ?></textarea><small class="text-secondary">Separe parágrafos com uma linha em branco.</small></div>
                                <div class="col-12"><label class="form-label" for="vest_text">Texto “Colete” <span class="text-secondary">(opcional)</span></label><textarea id="vest_text" name="vest_text" class="form-control" rows="4" maxlength="<?= MAX_TEXT_LENGTH ?>"><?= e($settings['vest_text']) ?></textarea></div>
                                <div class="col-md-6"><label class="form-label" for="elements_title">Título dos elementos <span class="text-secondary">(opcional)</span></label><input id="elements_title" name="elements_title" class="form-control" maxlength="255" value="<?= e($settings['elements_title']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="mission_title">Título da missão <span class="text-secondary">(opcional)</span></label><input id="mission_title" name="mission_title" class="form-control" maxlength="255" value="<?= e($settings['mission_title']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="elements_text">Elementos e significados <span class="text-secondary">(opcional)</span></label><textarea id="elements_text" name="elements_text" class="form-control" rows="7" maxlength="<?= MAX_TEXT_LENGTH ?>"><?= e($settings['elements_text']) ?></textarea><small class="text-secondary">Cada linha vira um item da lista.</small></div>
                                <div class="col-md-6"><label class="form-label" for="mission_text">Texto da missão <span class="text-secondary">(opcional)</span></label><textarea id="mission_text" name="mission_text" class="form-control" rows="7" maxlength="<?= MAX_TEXT_LENGTH ?>"><?= e($settings['mission_text']) ?></textarea></div>
                                <div class="col-md-4"><label class="form-label" for="board_title">Título da diretoria <span class="text-secondary">(opcional)</span></label><input id="board_title" name="board_title" class="form-control" maxlength="255" value="<?= e($settings['board_title']) ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="events_title">Título dos eventos <span class="text-secondary">(opcional)</span></label><input id="events_title" name="events_title" class="form-control" maxlength="255" value="<?= e($settings['events_title']) ?>"></div>
                                <div class="col-md-4"><label class="form-label" for="gallery_title">Título da galeria <span class="text-secondary">(opcional)</span></label><input id="gallery_title" name="gallery_title" class="form-control" maxlength="255" value="<?= e($settings['gallery_title']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="board_empty_message">Mensagem sem diretoria <span class="text-secondary">(opcional)</span></label><input id="board_empty_message" name="board_empty_message" class="form-control" maxlength="255" value="<?= e($settings['board_empty_message']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="events_empty_message">Mensagem sem eventos <span class="text-secondary">(opcional)</span></label><input id="events_empty_message" name="events_empty_message" class="form-control" maxlength="255" value="<?= e($settings['events_empty_message']) ?>"></div>
                                <div class="col-md-6"><label class="form-label" for="gallery_button_text">Texto do botão da galeria <span class="text-secondary">(opcional)</span></label><input id="gallery_button_text" name="gallery_button_text" class="form-control" maxlength="255" value="<?= e($settings['gallery_button_text']) ?>"></div>
                            </div>
                        </div>
                        <div class="card p-4 mb-4 site-settings-panel admin-form-card" data-site-panel="media">
                            <div class="site-settings-section-heading admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-photo-film" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Etapa 3</span><h2 class="h4 text-white mb-1">Mídia</h2><p class="text-secondary mb-0">Arquivos visuais e áudio usados pelo site. Você pode visualizar tudo antes de salvar.</p></div><span class="site-settings-step">3 de 3</span></div>
                            <div class="row g-3">
                                <div class="col-md-6"><label class="form-label" for="logo_file">Logo <span class="text-secondary">(opcional)</span></label><div class="site-media-preview site-media-preview-logo"><img id="logo-preview" src="<?= e(site_media_url($settings['logo_path'])) ?>" alt="<?= e($settings['logo_alt']) ?>"></div><input id="logo_file" name="logo_file" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"><label class="form-label mt-3" for="logo_alt">Texto alternativo <span class="text-secondary">(opcional)</span></label><input id="logo_alt" name="logo_alt" class="form-control" maxlength="255" value="<?= e($settings['logo_alt']) ?>"><small class="text-secondary">Atual: <?= e($settings['logo_path']) ?></small></div>
                                <div class="col-md-6"><label class="form-label" for="banner_file">Banner principal <span class="text-secondary">(opcional)</span></label><div class="site-media-preview site-media-preview-banner"><img id="banner-preview" src="<?= e(site_media_url($settings['banner_path'])) ?>" alt="<?= e($settings['banner_alt']) ?>"></div><input id="banner_file" name="banner_file" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,image/x-icon"><label class="form-label mt-3" for="banner_alt">Texto alternativo <span class="text-secondary">(opcional)</span></label><input id="banner_alt" name="banner_alt" class="form-control" maxlength="255" value="<?= e($settings['banner_alt']) ?>"><small class="text-secondary">Atual: <?= e($settings['banner_path']) ?></small></div>
                                <div class="col-md-6"><label class="form-label" for="favicon_file">Favicon <span class="text-secondary">(opcional)</span></label><div class="site-media-preview site-media-preview-favicon"><img id="favicon-preview" src="<?= e(site_media_url($settings['favicon_path'])) ?>" alt="Favicon"></div><input id="favicon_file" name="favicon_file" type="file" class="form-control" accept="image/png,image/x-icon"><small class="text-secondary">Atual: <?= e($settings['favicon_path']) ?></small></div>
                                <div class="col-md-6"><label class="form-label" for="audio_file">Áudio de fundo <span class="text-secondary">(opcional)</span></label><audio id="audio-preview" class="site-audio-preview" controls preload="metadata" src="<?= e(site_media_url($settings['audio_path'])) ?>">Seu navegador não suporta áudio.</audio><input id="audio_file" name="audio_file" type="file" class="form-control" accept="audio/mpeg,audio/wav,audio/ogg"><small class="text-secondary">Atual: <?= e($settings['audio_path']) ?></small></div>
                                <div class="col-md-3"><label class="form-label" for="audio_enabled">Áudio ativo <span class="text-secondary">(opcional)</span></label><select id="audio_enabled" name="audio_enabled" class="form-select"><option value="1"<?= $settings['audio_enabled'] === '1' ? ' selected' : '' ?>>Sim</option><option value="0"<?= $settings['audio_enabled'] !== '1' ? ' selected' : '' ?>>Não</option></select></div>
                                <div class="col-md-3"><label class="form-label" for="audio_volume">Volume inicial <span class="text-secondary">(opcional)</span></label><input id="audio_volume" name="audio_volume" type="number" min="0" max="1" step="0.01" class="form-control" value="<?= e($settings['audio_volume']) ?>"></div>
                            </div>
                        </div>
                        <div class="site-settings-actions admin-form-actions d-flex flex-wrap align-items-center justify-content-between gap-3"><span class="text-secondary">Revise as três seções antes de salvar.</span><button class="btn btn-primary px-4" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar alterações</button></div>
                    </form>
                </section>
                <section class="tab-pane fade" id="tab-gallery" role="tabpanel">
                    <div class="gallery-admin-intro card p-4 mb-4">
                        <div class="gallery-admin-heading">
                            <div>
                                <span class="site-settings-kicker">Biblioteca visual</span>
                                <h2 class="h3 text-white mb-2">Galeria</h2>
                                <p class="text-secondary mb-0">Envie novas imagens e escreva uma descrição para contextualizar cada registro no site.</p>
                            </div>
                            <span class="gallery-count"><strong><?= $total ?></strong><small>imagem(ns)</small><span class="gallery-count-meta"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= $describedGalleryCount ?> descrita(s) <b>·</b> <?= max(0, $total - $describedGalleryCount) ?> pendente(s)</span></span>
                        </div>
                        <form method="post" enctype="multipart/form-data" class="gallery-upload-form admin-form-grid row g-3">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <div class="col-lg-7"><label class="form-label text-white" for="images">Arquivos de imagem</label><input id="images" type="file" name="images[]" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" multiple required><small class="form-text">JPG, PNG, GIF ou WebP · até 8 MB por arquivo</small></div>
                            <div class="col-lg-5"><label class="form-label text-white" for="gallery_description">Descrição padrão <span class="text-secondary">(opcional)</span></label><textarea id="gallery_description" name="gallery_description" class="form-control" rows="2" maxlength="<?= MAX_GALLERY_DESCRIPTION_LENGTH ?>" placeholder="Ex.: Encontro do clube em Petrópolis..."></textarea><small class="form-text">Será aplicada às imagens deste envio; você pode ajustar cada uma depois.</small></div>
                            <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary px-4" type="submit"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Enviar imagens</button></div>
                        </form>
                    </div>
                    <div class="gallery-admin-grid">
                        <?php foreach ($items as $itemIndex => $file):
                            $metadata = $galleryMetadata[$file] ?? [];
                            $description = trim((string)($metadata['description'] ?? ''));
                            $imageNumber = (($currentPage - 1) * PAGE_SIZE) + $itemIndex + 1;
                            ?>
                            <article class="gallery-admin-card">
                                <div class="gallery-card-media">
                                    <img src="<?= e(GALLERY_WEB . '/' . rawurlencode($file)) ?>" alt="<?= e($description !== '' ? $description : 'Imagem sem descrição') ?>" loading="lazy" decoding="async">
                                    <span class="gallery-card-index">#<?= $imageNumber ?></span>
                                    <span class="gallery-card-status<?= $description !== '' ? ' is-complete' : '' ?>"><i class="fa-solid <?= $description !== '' ? 'fa-check' : 'fa-pen' ?>" aria-hidden="true"></i><?= $description !== '' ? 'Descrita' : 'Descrever' ?></span>
                                </div>
                                <div class="gallery-card-body">
                                    <div class="gallery-card-file" title="<?= e($file) ?>"><i class="fa-regular fa-image" aria-hidden="true"></i><span><?= e($file) ?></span></div>
                                    <div class="gallery-card-footer">
                                        <form method="post" class="gallery-description-form admin-form-grid">
                                            <input type="hidden" name="action" value="gallery_update">
                                            <input type="hidden" name="file" value="<?= e($file) ?>">
                                            <input type="hidden" name="page" value="<?= $currentPage ?>">
                                            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                            <label class="form-label" for="description-<?= $imageNumber ?>">Descrição da imagem <span class="text-secondary">(opcional)</span></label>
                                            <textarea id="description-<?= $imageNumber ?>" name="description" class="form-control" rows="3" maxlength="<?= MAX_GALLERY_DESCRIPTION_LENGTH ?>" placeholder="Conte o que aparece nesta foto..."><?= e($description) ?></textarea>
                                            <button class="btn btn-primary btn-sm w-100 mt-3" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar descrição</button>
                                        </form>
                                        <form method="post" class="gallery-delete-form" onsubmit="return confirm('Remover esta imagem e sua descrição?');">
                                            <input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?= e($file) ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Excluir <?= e($file) ?>"><i class="fa-regular fa-trash-can" aria-hidden="true"></i><span class="visually-hidden">Excluir</span></button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($pages > 1): ?>
                        <nav class="mt-4" aria-label="Paginação da galeria"><ul class="pagination">
                            <?php for ($i = 1; $i <= $pages; $i++): ?><li class="page-item<?= $i === $currentPage ? ' active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>#tab-gallery"><?= $i ?></a></li><?php endfor; ?>
                        </ul></nav>
                    <?php endif; ?>
                </section>

                <section class="tab-pane fade" id="tab-diretoria" role="tabpanel">
                    <div class="card p-4 mb-4 admin-form-card">
                        <div class="admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Equipe</span><h2 class="h4 text-white">Adicionar membro</h2><p>Cadastre uma pessoa para exibir na diretoria do site.</p></div><span class="gallery-count admin-summary-counter"><strong><?= count($directoria) ?></strong><small>membro(s)</small><span class="gallery-count-meta"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><?= $activeDirectoria ?> ativo(s) <b>·</b> <?= max(0, count($directoria) - $activeDirectoria) ?> inativo(s)</span></span></div>
                        <form method="post" enctype="multipart/form-data" class="admin-form-grid row g-3">
                            <input type="hidden" name="resource" value="diretoria"><input type="hidden" name="action" value="create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <div class="col-md-4"><label class="form-label" for="nome">Nome</label><input id="nome" name="nome" class="form-control" maxlength="150" required></div>
                            <div class="col-md-4"><label class="form-label" for="cargo">Cargo</label><input id="cargo" name="cargo" class="form-control" maxlength="150" required></div>
                            <div class="col-md-2"><label class="form-label" for="ordem">Ordem <span class="text-secondary">(opcional)</span></label><input id="ordem" name="ordem" type="number" class="form-control" value="0"></div>
                            <div class="col-md-2"><label class="form-label" for="ativo-dir">Status <span class="text-secondary">(opcional)</span></label><select id="ativo-dir" name="ativo" class="form-select"><option value="1">Ativo</option><option value="0">Inativo</option></select></div>
                            <div class="col-md-8"><label class="form-label" for="bio">Biografia <span class="text-secondary">(opcional)</span></label><textarea id="bio" name="bio" class="form-control" rows="3" maxlength="10000"></textarea></div>
                            <div class="col-md-4"><label class="form-label" for="foto">Foto <span class="text-secondary">(opcional)</span></label><input id="foto" name="foto" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"></div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Adicionar membro</button></div>
                        </form>
                    </div>
                    <div class="table-responsive"><table class="table table-dark table-striped align-middle"><thead><tr><th>Nome</th><th>Cargo</th><th>Status</th><th>Ações</th></tr></thead><tbody>
                    <?php foreach ($directoria as $row): ?><tr><td><?= e($row['nome']) ?></td><td><?= e($row['cargo']) ?></td><td><?= $row['ativo'] ? 'Ativo' : 'Inativo' ?></td><td><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#dir-<?= (int)$row['id'] ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar</button> <form method="post" class="d-inline" onsubmit="return confirm('Remover este membro?');"><input type="hidden" name="resource" value="diretoria"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir</button></form></td></tr>
                    <tr class="collapse" id="dir-<?= (int)$row['id'] ?>"><td colspan="4"><form method="post" enctype="multipart/form-data" class="row g-2 admin-inline-form"><input type="hidden" name="resource" value="diretoria"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-md-3"><input name="nome" class="form-control" value="<?= e($row['nome']) ?>" maxlength="150" required aria-label="Nome"></div><div class="col-md-3"><input name="cargo" class="form-control" value="<?= e($row['cargo']) ?>" maxlength="150" required aria-label="Cargo"></div><div class="col-md-2"><input name="ordem" type="number" class="form-control" value="<?= (int)$row['ordem'] ?>" aria-label="Ordem"></div><div class="col-md-2"><select name="ativo" class="form-select" aria-label="Status"><option value="1"<?= $row['ativo'] ? ' selected' : '' ?>>Ativo</option><option value="0"<?= !$row['ativo'] ? ' selected' : '' ?>>Inativo</option></select></div><div class="col-md-6"><textarea name="bio" class="form-control" rows="2" maxlength="10000" aria-label="Biografia"><?= e($row['bio']) ?></textarea></div><div class="col-md-4"><input name="foto" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Nova foto"></div><div class="col-md-2"><button class="btn btn-success w-100" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar</button></div></form></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                </section>

                <section class="tab-pane fade" id="tab-colaboradores" role="tabpanel">
                    <div class="card p-4 mb-4 admin-form-card">
                        <div class="admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-handshake" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Parcerias</span><h2 class="h4 text-white">Adicionar colaborador</h2><p>Cadastre uma empresa ou parceiro para exibir no site.</p></div><span class="gallery-count admin-summary-counter"><strong><?= count($colaboradores) ?></strong><small>colaborador(es)</small><span class="gallery-count-meta"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>exibição pública</span></span></div>
                        <form method="post" enctype="multipart/form-data" class="admin-form-grid row g-3">
                            <input type="hidden" name="resource" value="colaboradores"><input type="hidden" name="action" value="create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                            <div class="col-md-6"><label class="form-label" for="colaborador_nome">Nome</label><input id="colaborador_nome" name="nome" class="form-control" maxlength="150" required placeholder="Ex.: Oficina Barões"></div>
                            <div class="col-md-6"><label class="form-label" for="colaborador_foto">Foto <span class="text-secondary">(opcional)</span></label><input id="colaborador_foto" name="foto" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-text">JPG, PNG, GIF ou WebP · até 8 MB.</small></div>
                            <div class="col-12"><label class="form-label" for="colaborador_pdf">PDF interativo <span class="text-secondary">(opcional)</span></label><input id="colaborador_pdf" name="pdf" type="file" class="form-control" accept="application/pdf,.pdf"><small class="form-text">PDF · até 8 MB. Links e navegação interativa serão preservados.</small></div>
                            <div class="col-12"><label class="form-label" for="colaborador_descricao">Descrição <span class="text-secondary">(opcional)</span></label><textarea id="colaborador_descricao" name="descricao" class="form-control" rows="4" maxlength="<?= MAX_TEXT_LENGTH ?>" placeholder="Apresente o colaborador e sua parceria com o clube."></textarea></div>
                            <div class="col-md-4"><label class="form-label" for="colaborador_site">Link do site <span class="text-secondary">(opcional)</span></label><input id="colaborador_site" name="site_url" type="url" class="form-control" maxlength="500" placeholder="https://exemplo.com"></div>
                            <div class="col-md-4"><label class="form-label" for="colaborador_social">Link da rede social <span class="text-secondary">(opcional)</span></label><input id="colaborador_social" name="social_url" type="url" class="form-control" maxlength="500" placeholder="https://instagram.com/perfil"></div>
                            <div class="col-md-4"><label class="form-label" for="colaborador_whatsapp">WhatsApp <span class="text-secondary">(opcional)</span></label><input id="colaborador_whatsapp" name="whatsapp" type="tel" class="form-control" maxlength="19" placeholder="+55 (24) 99999-9999" autocomplete="tel" inputmode="tel"><small class="form-text">+55 obrigatório · DDD + telefone com 8 ou 9 dígitos.</small></div>
                            <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-handshake" aria-hidden="true"></i> Adicionar colaborador</button></div>
                        </form>
                    </div>
                    <div class="table-responsive"><table class="table table-dark table-striped align-middle"><thead><tr><th>Nome</th><th>Foto</th><th>PDF</th><th>Links</th><th>WhatsApp</th><th>Ações</th></tr></thead><tbody>
                    <?php foreach ($colaboradores as $row): ?><tr><td><?= e($row['nome']) ?></td><td><?= $row['foto'] !== '' ? 'Cadastrada' : 'Sem foto' ?></td><td><?= !empty($row['pdf']) ? 'Cadastrado' : 'Sem PDF' ?></td><td><?= $row['site_url'] !== '' || $row['social_url'] !== '' ? 'Disponíveis' : 'Não informados' ?></td><td><?= e($row['whatsapp'] !== '' ? $row['whatsapp'] : 'Não informado') ?></td><td><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#col-<?= (int)$row['id'] ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar</button> <form method="post" class="d-inline" onsubmit="return confirm('Remover este colaborador?');"><input type="hidden" name="resource" value="colaboradores"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir</button></form></td></tr>
                    <tr class="collapse" id="col-<?= (int)$row['id'] ?>"><td colspan="6"><form method="post" enctype="multipart/form-data" class="row g-3 admin-inline-form"><input type="hidden" name="resource" value="colaboradores"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-md-6"><input name="nome" class="form-control" value="<?= e($row['nome']) ?>" maxlength="150" required aria-label="Nome"></div><div class="col-md-6"><input name="foto" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Nova foto"><small class="form-text">Deixe vazio para manter a foto atual.</small></div><div class="col-12"><input name="pdf" type="file" class="form-control" accept="application/pdf,.pdf" aria-label="Novo PDF"><small class="form-text">Deixe vazio para manter o PDF atual.</small></div><div class="col-12"><textarea name="descricao" class="form-control" rows="4" maxlength="<?= MAX_TEXT_LENGTH ?>" aria-label="Descrição"><?= e($row['descricao']) ?></textarea></div><div class="col-md-4"><input name="site_url" type="url" class="form-control" value="<?= e($row['site_url']) ?>" maxlength="500" placeholder="https://exemplo.com" aria-label="Link do site"></div><div class="col-md-4"><input name="social_url" type="url" class="form-control" value="<?= e($row['social_url']) ?>" maxlength="500" placeholder="https://instagram.com/perfil" aria-label="Link da rede social"></div><div class="col-md-4"><input name="whatsapp" type="tel" class="form-control" value="<?= e($row['whatsapp']) ?>" maxlength="30" placeholder="(24) 99999-9999" aria-label="WhatsApp"></div><div class="col-12"><button class="btn btn-success w-100" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar</button></div></form></td></tr>
                    <?php endforeach; ?></tbody></table></div>
                </section>

                <section class="tab-pane fade" id="tab-eventos" role="tabpanel">
                    <div class="card p-4 mb-4 admin-form-card"><div class="admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Agenda</span><h2 class="h4 text-white">Adicionar evento</h2><p>Publique uma nova data para a comunidade acompanhar.</p></div></div><form method="post" enctype="multipart/form-data" class="row g-3"><input type="hidden" name="resource" value="eventos"><input type="hidden" name="action" value="create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-md-5"><label class="form-label" for="titulo">Título</label><input id="titulo" name="titulo" class="form-control" maxlength="200" required placeholder="Ex.: Encontro mensal do clube"></div><div class="col-md-3"><label class="form-label" for="local">Local <span class="text-secondary">(opcional)</span></label><input id="local" name="local" class="form-control" maxlength="200" placeholder="Cidade ou endereço"></div><div class="col-md-2"><label class="form-label" for="data_inicio">Início</label><input id="data_inicio" name="data_inicio" type="datetime-local" class="form-control" required></div><div class="col-md-2"><label class="form-label" for="data_fim">Fim <span class="text-secondary">(opcional)</span></label><input id="data_fim" name="data_fim" type="datetime-local" class="form-control"></div><div class="col-md-8"><label class="form-label" for="descricao">Descrição <span class="text-secondary">(opcional)</span></label><textarea id="descricao" name="descricao" class="form-control" rows="3" maxlength="10000" placeholder="Explique o que os participantes precisam saber..."></textarea></div><div class="col-md-4"><label class="form-label" for="banner">Banner <span class="text-secondary">(opcional)</span></label><input id="banner" name="banner" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-text">Opcional · JPG, PNG, GIF ou WebP.</small></div><div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Adicionar evento</button></div></form></div>
                    <div class="table-responsive"><table class="table table-dark table-striped align-middle"><thead><tr><th>Título</th><th>Data</th><th>Status</th><th>Ações</th></tr></thead><tbody><?php foreach ($eventos as $row): ?><tr><td><?= e($row['titulo']) ?></td><td><?= e(format_admin_datetime((string)$row['data_inicio'])) ?></td><td><?= $row['ativo'] ? 'Ativo' : 'Inativo' ?></td><td><button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#evt-<?= (int)$row['id'] ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar</button> <form method="post" class="d-inline" onsubmit="return confirm('Remover este evento?');"><input type="hidden" name="resource" value="eventos"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir</button></form></td></tr><tr class="collapse" id="evt-<?= (int)$row['id'] ?>"><td colspan="4"><form method="post" enctype="multipart/form-data" class="row g-2 admin-inline-form"><input type="hidden" name="resource" value="eventos"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-md-4"><input name="titulo" class="form-control" value="<?= e($row['titulo']) ?>" maxlength="200" required aria-label="Título"></div><div class="col-md-3"><input name="local" class="form-control" value="<?= e($row['local']) ?>" maxlength="200" aria-label="Local"></div><div class="col-md-2"><input name="data_inicio" type="datetime-local" class="form-control" value="<?= e(str_replace(' ', 'T', substr((string)$row['data_inicio'], 0, 16))) ?>" required aria-label="Início"></div><div class="col-md-2"><input name="data_fim" type="datetime-local" class="form-control" value="<?= e($row['data_fim'] ? str_replace(' ', 'T', substr((string)$row['data_fim'], 0, 16)) : '') ?>" aria-label="Fim"></div><div class="col-md-1"><select name="ativo" class="form-select" aria-label="Status"><option value="1"<?= $row['ativo'] ? ' selected' : '' ?>>Ativo</option><option value="0"<?= !$row['ativo'] ? ' selected' : '' ?>>Inativo</option></select></div><div class="col-md-6"><textarea name="descricao" class="form-control" rows="2" maxlength="10000" aria-label="Descrição"><?= e($row['descricao']) ?></textarea></div><div class="col-md-4"><input name="banner" type="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" aria-label="Novo banner"></div><div class="col-md-2"><button class="btn btn-success w-100" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar</button></div></form></td></tr><?php endforeach; ?></tbody></table></div>
                </section>

                <section class="tab-pane fade" id="tab-users" role="tabpanel"><div class="card p-4 mb-4 admin-form-card"><div class="admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-user-shield" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Acessos</span><h2 class="h4 text-white">Criar usuário</h2><p>Defina quem poderá acessar e administrar o painel.</p></div></div><form method="post" class="row g-3"><input type="hidden" name="action" value="user_create"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-md-4"><label class="form-label" for="new_username">Usuário</label><input id="new_username" name="new_username" class="form-control" maxlength="80" pattern="[A-Za-z0-9._-]{3,80}" required autocomplete="username" placeholder="Ex.: joao.silva"><small class="form-text">Use letras, números, ponto, hífen ou sublinhado.</small></div><div class="col-md-4"><label class="form-label" for="new_password">Senha</label><input id="new_password" name="new_password" type="password" class="form-control" minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>" required autocomplete="new-password" placeholder="Mínimo de <?= MIN_PASSWORD_LENGTH ?> caracteres"><small class="form-text">Não reutilize senhas pessoais.</small></div><div class="col-md-2 admin-user-option"><div class="form-check"><input id="new_is_admin" name="new_is_admin" type="checkbox" class="form-check-input"><label for="new_is_admin" class="form-check-label">Permitir administração <span class="text-secondary">(opcional)</span></label></div></div><div class="col-12 admin-user-action"><button class="btn btn-success" type="submit"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Criar</button></div></form></div><div class="table-responsive"><table class="table table-dark table-striped"><thead><tr><th>Usuário</th><th>Admin</th><th>Ações</th></tr></thead><tbody><?php foreach ($users as $row): ?><tr><td><?= e($row['username']) ?></td><td><?= $row['is_admin'] ? 'Sim' : 'Não' ?></td><td><form method="post" class="d-inline"><input type="hidden" name="action" value="user_toggle_admin"><input type="hidden" name="uid" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"<?= (int)$row['id'] === (int)$user['id'] ? ' disabled' : '' ?>><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Alternar admin</button></form> <form method="post" class="d-inline" onsubmit="return confirm('Excluir este usuário?');"><input type="hidden" name="action" value="user_delete"><input type="hidden" name="uid" value="<?= (int)$row['id'] ?>"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="btn btn-sm btn-outline-danger" type="submit"<?= (int)$row['id'] === (int)$user['id'] ? ' disabled' : '' ?>><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Excluir</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
            <?php endif; ?>

            <section class="tab-pane fade<?= !$user['is_admin'] ? ' show active' : '' ?>" id="tab-password" role="tabpanel"><div class="card p-4 admin-form-card"><div class="admin-form-heading"><span class="admin-icon-badge"><i class="fa-solid fa-key" aria-hidden="true"></i></span><div><span class="admin-form-kicker">Segurança</span><h2 class="h4 text-white">Alterar minha senha</h2><p>Atualize sua senha para manter o acesso protegido.</p></div></div><form method="post" class="row g-3"><input type="hidden" name="action" value="change_pass"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><div class="col-12"><label class="form-label" for="current">Senha atual</label><input id="current" name="current" type="password" class="form-control" required autocomplete="current-password" placeholder="Digite sua senha atual"></div><div class="col-md-6"><label class="form-label" for="new1">Nova senha</label><input id="new1" name="new1" type="password" class="form-control" minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>" required autocomplete="new-password" placeholder="Mínimo de <?= MIN_PASSWORD_LENGTH ?> caracteres"></div><div class="col-md-6"><label class="form-label" for="new2">Confirmar nova senha</label><input id="new2" name="new2" type="password" class="form-control" minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>" required autocomplete="new-password" placeholder="Repita a nova senha"></div><div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Alterar senha</button></div></form></div></section>
        </div>
    <?php endif; ?>
</main>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/whatsapp-mask.js?v=1"></script>
<script>
document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function (tab) {
    tab.addEventListener('shown.bs.tab', function (event) {
        const target = event.target.getAttribute('href');
        if (target) history.replaceState(null, '', target);
    });
});
const requestedTab = window.location.hash;
if (requestedTab) {
    const tab = document.querySelector('[data-bs-toggle="tab"][href="' + requestedTab + '"]');
    if (tab && window.bootstrap) new bootstrap.Tab(tab).show();
}

function bindImagePreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
        preview.onload = function () { URL.revokeObjectURL(objectUrl); };
    });
}

bindImagePreview('logo_file', 'logo-preview');
bindImagePreview('banner_file', 'banner-preview');
bindImagePreview('favicon_file', 'favicon-preview');
const audioInput = document.getElementById('audio_file');
const audioPreview = document.getElementById('audio-preview');
if (audioInput && audioPreview) {
    audioInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const objectUrl = URL.createObjectURL(file);
        audioPreview.src = objectUrl;
    audioPreview.load();
    });
}

const adminRecordData = {
    directoria: <?= json_encode($directoria, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    eventos: <?= json_encode($eventos, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    colaboradores: <?= json_encode($colaboradores, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    users: <?= json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
};
const adminRecordMedia = {
    directoria: <?= json_encode(DIRETORIA_WEB) ?>,
    eventos: <?= json_encode(EVENTOS_WEB) ?>,
    colaboradores: <?= json_encode(COLABORADORES_WEB) ?>
};
const adminRecordPdf = <?= json_encode(COLABORADORES_PDF_WEB) ?>;
const adminCsrfToken = document.querySelector('input[name="csrf"]')?.value || '';

function escapeAdminHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, function (character) {
        return {'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'}[character];
    });
}

function adminRecordDate(value) {
    if (!value) return 'Não informado';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return escapeAdminHtml(value);
    return date.toLocaleString('pt-BR', {dateStyle: 'short', timeStyle: 'short'});
}

function formatAdminWhatsapp(value) {
    const original = String(value ?? '').trim();
    let digits = original.replace(/\D/g, '');
    if (digits.indexOf('55') === 0) digits = digits.slice(2);
    if (digits.length === 11 && digits.indexOf('0') === 0) digits = digits.slice(1);
    if (digits.length !== 10 && digits.length !== 11) return original || 'Não informado';
    const ddd = digits.slice(0, 2);
    const phone = digits.slice(2);
    return '+55 (' + ddd + ') ' + (phone.length === 9
        ? phone.slice(0, 5) + '-' + phone.slice(5)
        : phone.slice(0, 4) + '-' + phone.slice(4));
}

function adminRecordField(label, value, extraClass) {
    return '<div class="admin-record-field' + (extraClass ? ' ' + extraClass : '') + '"><span>' + escapeAdminHtml(label) + '</span><strong>' + escapeAdminHtml(value || 'Não informado') + '</strong></div>';
}

function addAdminModalLabels(modal) {
    modal.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(function (field) {
        if (field.closest('.form-check') || field.previousElementSibling?.matches('label')) return;
        const text = field.getAttribute('aria-label') || field.name;
        if (!text || !field.parentElement) return;
        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = text + (field.required ? '' : ' (opcional)');
        field.parentElement.insertBefore(label, field);
    });
    modal.querySelectorAll('select[name="ativo"] option').forEach(function (option) {
        option.textContent = option.value === '1' ? 'Ativo' : 'Inativo';
    });
}

function addAdminModalMediaPreview(modal, resource, data) {
    const isPdf = resource === 'colaboradores';
    const input = modal.querySelector(isPdf ? 'input[type="file"][name="pdf"]' : 'input[type="file"]');
    if (!input || !input.parentElement) return;
    const fileName = isPdf ? data.pdf : resource === 'directoria' ? data.foto : data.banner;
    const mediaPath = isPdf ? adminRecordPdf : resource === 'directoria' ? adminRecordMedia.directoria : adminRecordMedia.eventos;
    const preview = document.createElement('div');
    preview.className = (isPdf ? 'admin-modal-pdf-preview' : 'admin-modal-media-preview') + (fileName ? '' : ' is-empty');
    const currentSource = fileName ? mediaPath + '/' + encodeURIComponent(fileName) : '';
    preview.innerHTML = currentSource
        ? (isPdf ? '<iframe src="' + escapeAdminHtml(currentSource) + '#view=FitH" title="PDF atual"></iframe><a href="' + escapeAdminHtml(currentSource) + '" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Abrir PDF</a>' : '<img src="' + escapeAdminHtml(currentSource) + '" alt="Imagem atual" loading="lazy">')
        : (isPdf ? '<i class="fa-regular fa-file-pdf" aria-hidden="true"></i><span>Nenhum PDF cadastrado</span>' : '<i class="fa-regular fa-image" aria-hidden="true"></i><span>Nenhuma imagem cadastrada</span>');
    input.parentElement.insertBefore(preview, input);
    input.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const objectUrl = URL.createObjectURL(file);
        preview.classList.remove('is-empty');
        preview.innerHTML = isPdf
            ? '<iframe src="' + objectUrl + '#view=FitH" title="Pré-visualização do PDF selecionado"></iframe><a href="' + objectUrl + '" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Abrir PDF</a>'
            : '<img src="' + objectUrl + '" alt="Pré-visualização do arquivo selecionado">';
        if (!isPdf) preview.querySelector('img').onload = function () { URL.revokeObjectURL(objectUrl); };
    });
}

function buildAdminRecordCard(resource, data, row, editRow, index) {
    const card = document.createElement('article');
    card.className = 'admin-record-card';
    const recordId = String(data.id ?? index + 1);
    const title = resource === 'directoria' ? data.nome : resource === 'eventos' ? data.titulo : resource === 'colaboradores' ? data.nome : data.username;
    const subtitle = resource === 'directoria' ? data.cargo : resource === 'eventos' ? data.local : resource === 'colaboradores' ? 'Colaborador do clube' : (data.is_admin ? 'Administrador' : 'Usuário padrão');
    const active = resource === 'users' ? Number(data.is_admin) === 1 : resource === 'colaboradores' ? true : Number(data.ativo) === 1;
    const statusLabel = resource === 'users' ? (active ? 'Admin' : 'Padrão') : resource === 'colaboradores' ? 'Ativo' : (active ? 'Ativo' : 'Inativo');
    let media = '';
    if (resource === 'directoria' && data.foto) {
        media = '<img src="' + escapeAdminHtml(adminRecordMedia.directoria + '/' + encodeURIComponent(data.foto)) + '" alt="" loading="lazy">';
    } else if (resource === 'eventos' && data.banner) {
        media = '<img src="' + escapeAdminHtml(adminRecordMedia.eventos + '/' + encodeURIComponent(data.banner)) + '" alt="" loading="lazy">';
    } else if (resource === 'colaboradores' && data.foto) {
        media = '<img src="' + escapeAdminHtml(adminRecordMedia.colaboradores + '/' + encodeURIComponent(data.foto)) + '" alt="" loading="lazy">';
    }
    let details = '';
    if (resource === 'directoria') {
        details = adminRecordField('Ordem', data.ordem) + adminRecordField('Biografia', data.bio, 'is-long') + adminRecordField('Foto', data.foto ? 'Cadastrada' : 'Sem foto');
    } else if (resource === 'eventos') {
        const start = row.cells[1] ? row.cells[1].textContent.trim() : data.data_inicio;
        details = adminRecordField('Início', start) + adminRecordField('Fim', adminRecordDate(data.data_fim)) + adminRecordField('Descrição', data.descricao, 'is-long') + adminRecordField('Banner', data.banner ? 'Cadastrado' : 'Sem banner');
    } else if (resource === 'colaboradores') {
        details = adminRecordField('Descrição', data.descricao, 'is-long') + adminRecordField('Site', data.site_url) + adminRecordField('Rede social', data.social_url) + adminRecordField('WhatsApp', formatAdminWhatsapp(data.whatsapp)) + adminRecordField('Foto', data.foto ? 'Cadastrada' : 'Sem foto') + adminRecordField('PDF', data.pdf ? 'Cadastrado' : 'Sem PDF');
    } else {
        details = adminRecordField('Usuário', data.username) + adminRecordField('Perfil', data.is_admin ? 'Administrador' : 'Usuário padrão') + adminRecordField('Identificador', data.id);
    }
    card.innerHTML = '<div class="admin-record-media' + (media ? '' : ' is-empty') + '">' + (media || '<i class="fa-solid ' + (resource === 'directoria' ? 'fa-user' : resource === 'eventos' ? 'fa-calendar-days' : resource === 'colaboradores' ? 'fa-handshake' : 'fa-user-shield') + '" aria-hidden="true"></i>') + '</div><div class="admin-record-card-header"><div><span class="admin-record-kicker">' + escapeAdminHtml(resource === 'directoria' ? 'Equipe' : resource === 'eventos' ? 'Agenda' : resource === 'colaboradores' ? 'Parceria' : 'Acessos') + '</span><h3>' + escapeAdminHtml(title) + '</h3><p>' + escapeAdminHtml(subtitle || 'Sem informação') + '</p></div><span class="admin-record-status ' + (active ? 'is-active' : '') + '">' + escapeAdminHtml(statusLabel) + '</span></div><div class="admin-record-body">' + details + '</div>';

    const actions = document.createElement('div');
    actions.className = 'admin-record-actions';
    actions.innerHTML = row.lastElementChild ? row.lastElementChild.innerHTML : '';
    if (resource === 'users') {
        const toggleForm = actions.querySelector('form input[name="action"][value="user_toggle_admin"]')?.closest('form');
        if (toggleForm) toggleForm.remove();
    }
    card.appendChild(actions);

    if (editRow) {
        const editId = 'admin-record-modal-' + resource + '-' + recordId;
        const editButton = actions.querySelector('[data-bs-toggle="collapse"]');
        const editModal = document.createElement('div');
        editModal.id = editId;
        editModal.className = 'modal fade admin-record-modal admin-record-modal-' + resource;
        editModal.setAttribute('tabindex', '-1');
        editModal.setAttribute('aria-hidden', 'true');
        editModal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><span class="admin-form-kicker">Editar registro</span><h2 class="modal-title">' + escapeAdminHtml(title) + '</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body">' + (editRow.querySelector('td') ? editRow.querySelector('td').innerHTML : '') + '</div></div></div>';
        addAdminModalLabels(editModal);
        addAdminModalMediaPreview(editModal, resource, data);
        if (editButton) {
            editButton.removeAttribute('data-bs-toggle');
            editButton.setAttribute('data-bs-toggle', 'modal');
            editButton.setAttribute('data-bs-target', '#' + editId);
        }
        document.body.appendChild(editModal);
    }

    if (resource === 'users') {
        const editId = 'admin-record-modal-users-' + recordId;
        const editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn btn-sm btn-outline-secondary';
        editButton.setAttribute('data-bs-toggle', 'modal');
        editButton.setAttribute('data-bs-target', '#' + editId);
        editButton.innerHTML = '<i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Editar';
        actions.prepend(editButton);

        const userModal = document.createElement('div');
        userModal.id = editId;
        userModal.className = 'modal fade admin-record-modal admin-record-modal-users';
        userModal.setAttribute('tabindex', '-1');
        userModal.setAttribute('aria-hidden', 'true');
        userModal.innerHTML = '<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><span class="admin-form-kicker">Acessos</span><h2 class="modal-title">Editar usuário</h2><p class="modal-description">Atualize os dados e as permissões deste acesso.</p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div><div class="modal-body"><form method="post" class="row g-3 admin-record-modal-form"><input type="hidden" name="action" value="user_update"><input type="hidden" name="uid" value="' + escapeAdminHtml(recordId) + '"><input type="hidden" name="csrf" value="' + escapeAdminHtml(adminCsrfToken) + '"><div class="col-12"><label class="form-label" for="edit_username_' + escapeAdminHtml(recordId) + '">Usuário</label><input id="edit_username_' + escapeAdminHtml(recordId) + '" name="edit_username" class="form-control" maxlength="80" pattern="[A-Za-z0-9._-]{3,80}" required value="' + escapeAdminHtml(data.username) + '"><small class="form-text">Use letras, números, ponto, hífen ou sublinhado.</small></div><div class="col-md-6"><label class="form-label" for="edit_password_' + escapeAdminHtml(recordId) + '">Nova senha <span class="text-secondary">(opcional)</span></label><input id="edit_password_' + escapeAdminHtml(recordId) + '" name="edit_password" type="password" class="form-control" minlength="<?= MIN_PASSWORD_LENGTH ?>" maxlength="<?= MAX_PASSWORD_LENGTH ?>" autocomplete="new-password"><small class="form-text">Deixe em branco para manter a senha atual.</small></div><div class="col-md-6 d-flex align-items-end"><div class="form-check w-100"><input id="edit_is_admin_' + escapeAdminHtml(recordId) + '" name="edit_is_admin" type="checkbox" class="form-check-input"' + (Number(data.is_admin) === 1 ? ' checked' : '') + '><label for="edit_is_admin_' + escapeAdminHtml(recordId) + '" class="form-check-label">Permitir administração <span class="text-secondary">(opcional)</span></label></div></div><div class="col-12"><div class="admin-form-actions"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Salvar usuário</button></div></div></form></div></div></div>';
        document.body.appendChild(userModal);
    }
    return card;
}

function convertAdminTableToCards(tabSelector, resource) {
    const tab = document.querySelector(tabSelector);
    const source = tab && tab.querySelector('.table-responsive');
    const table = source && source.querySelector('table');
    if (!source || !table || !table.tBodies[0]) return;
    const rows = Array.from(table.tBodies[0].children).filter(function (row) { return !row.classList.contains('collapse'); });
    const records = adminRecordData[resource] || [];
    const grid = document.createElement('div');
    grid.className = 'admin-record-grid';
    rows.forEach(function (row, index) {
        const idInput = row.querySelector('input[name="id"], input[name="uid"]');
        const id = idInput ? String(idInput.value) : '';
        const data = records.find(function (record) { return String(record.id) === id; }) || records[index] || {};
        const editRow = row.nextElementSibling && row.nextElementSibling.classList.contains('collapse') ? row.nextElementSibling : null;
        grid.appendChild(buildAdminRecordCard(resource, data, row, editRow, index));
    });
    source.parentNode.insertBefore(grid, source);
    source.classList.add('admin-record-source');
}

function appendAdminSummary(tabSelector, unitLabel, activeLabel, inactiveLabel, statusIndex, activeValue) {
    const tab = document.querySelector(tabSelector);
    const heading = tab && tab.querySelector('.admin-form-heading');
    if (!heading || heading.querySelector('.admin-summary-counter')) return;
    const rows = Array.from(heading.closest('.tab-pane').querySelectorAll('tbody tr:not(.collapse)'));
    const activeCount = rows.filter(function (row) {
        return row.cells[statusIndex] && row.cells[statusIndex].textContent.trim() === activeValue;
    }).length;
    const counter = document.createElement('span');
    counter.className = 'gallery-count admin-summary-counter';
    counter.innerHTML = '<strong>' + rows.length + '</strong><small>' + unitLabel + '</small><span class="gallery-count-meta"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>' + activeCount + ' ' + activeLabel + ' <b>·</b> ' + (rows.length - activeCount) + ' ' + inactiveLabel + '</span>';
    heading.appendChild(counter);
}

appendAdminSummary('#tab-eventos', 'evento(s)', 'ativo(s)', 'inativo(s)', 2, 'Ativo');
appendAdminSummary('#tab-users', 'usuário(s)', 'admin(s)', 'padrão', 1, 'Sim');
convertAdminTableToCards('#tab-diretoria', 'directoria');
convertAdminTableToCards('#tab-eventos', 'eventos');
convertAdminTableToCards('#tab-colaboradores', 'colaboradores');
convertAdminTableToCards('#tab-users', 'users');

const sitePanelButtons = document.querySelectorAll('[data-site-panel].nav-link');
const sitePanels = document.querySelectorAll('.site-settings-panel');
sitePanelButtons.forEach(function (button) {
    button.addEventListener('click', function () {
        const panelName = this.getAttribute('data-site-panel');
        sitePanelButtons.forEach(function (item) { item.classList.toggle('active', item === button); });
        sitePanels.forEach(function (panel) { panel.classList.toggle('is-active', panel.getAttribute('data-site-panel') === panelName); });
    });
});
</script>
</body>
</html>
