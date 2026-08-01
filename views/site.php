<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';
$settings = \App\Repositories\SettingsRepository::all();
$instagramUrl = external_url($settings['instagram_url']);
$collaboradores = \App\Repositories\ContentRepository::activeColaboradores();
$collaboratorLogos = array_slice(array_values(array_filter($collaboradores, static fn(array $collaborador): bool => trim((string)($collaborador['foto'] ?? '')) !== '')), 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($settings['site_title']) ?></title>
    <meta name="description" content="<?= e($settings['site_description']) ?>">
    <meta name="theme-color" content="#0b0b0b">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($settings['site_name']) ?>">
    <meta property="og:title" content="<?= e($settings['site_title']) ?>">
    <meta property="og:description" content="<?= e($settings['og_description']) ?>">
    <meta property="og:image" content="<?= e(site_media_url($settings['logo_path'])) ?>">
    <link rel="icon" href="<?= e(site_media_url($settings['favicon_path'])) ?>" type="<?= e($settings['favicon_mime']) ?>">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/site.css?v=7">
    <link rel="stylesheet" href="assets/css/audio.css?v=2">
</head>
<body class="site-page">
<div class="site-navbar-shell">
    <nav id="header" class="navbar navbar-expand-lg site-navbar border-radius-xl top-0 position-absolute py-2 start-0 end-0">
        <div class="container-sm">
            <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarToggler" aria-controls="navbarToggler" aria-expanded="false" aria-label="Abrir menu"><i class="fa-solid fa-bars text-white"></i></button>
            <a class="navbar-brand fw-bold text-white page-title" href="#inicio"><?= e($settings['site_name']) ?></a>
            <div class="collapse navbar-collapse" id="navbarToggler">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link scrollto page-title active" href="#inicio"><?= e($settings['nav_home_label']) ?></a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#sobre"><?= e($settings['nav_about_label']) ?></a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#misao"><?= e($settings['nav_mission_label']) ?></a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#diretoria"><?= e($settings['nav_board_label']) ?></a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#colaboradores">Colaboradores</a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#eventos"><?= e($settings['nav_events_label']) ?></a></li>
                    <li class="nav-item"><a class="nav-link scrollto page-title" href="#galeria"><?= e($settings['nav_gallery_label']) ?></a></li>
                </ul>
            </div>
        </div>
    </nav>
</div>

<header id="inicio" class="site-hero bg-index">
    <div class="page-header min-vh-100 d-flex align-items-center justify-content-center text-center" style="background-image: url('<?= e(site_media_url($settings['banner_path'])) ?>');">
        <span class="mask opacity-6"></span>
        <div class="container site-hero-content">
            <img src="<?= e(site_media_url($settings['logo_path'])) ?>" alt="<?= e($settings['logo_alt']) ?>" class="img-fluid mx-auto d-block site-hero-logo">
            <a class="site-hero-cta scrollto" href="#sobre"><span><?= e($settings['nav_about_label']) ?></span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></a>
        </div>
    </div>
</header>

<?php if ($collaboratorLogos): ?>
<aside class="collaborator-float-rail" id="collaborator-float-rail" aria-label="Colaboradores em destaque">
    <div class="collaborator-float-heading"><i class="fa-solid fa-handshake" aria-hidden="true"></i><span>Parceiros</span></div>
    <div class="collaborator-float-carousel" aria-label="Logos dos colaboradores">
        <div class="collaborator-float-track">
        <?php foreach ($collaboratorLogos as $collaboratorLogo): ?>
            <a class="collaborator-float-item scrollto" href="#colaboradores" aria-label="Ver <?= e($collaboratorLogo['nome']) ?>" data-collaborator-float-link>
                <img src="<?= e(COLABORADORES_WEB . '/' . rawurlencode((string)$collaboratorLogo['foto'])) ?>" alt="<?= e($collaboratorLogo['nome']) ?>" decoding="async">
            </a>
        <?php endforeach; ?>
        </div>
    </div>
    <span class="collaborator-float-progress" id="collaborator-float-progress" aria-live="polite">1 / <?= count($collaboratorLogos) ?></span>
</aside>
<?php endif; ?>

<main>
<section id="sobre" class="site-section site-section-light">
    <div class="container site-container">
        <header class="site-section-heading"><span class="site-section-kicker">Nossa história</span><h1 class="site-section-title page-title"><?= e($settings['about_title']) ?></h1></header>
        <div class="site-copy"><?= paragraphs_html($settings['about_text']) ?></div>
        <div class="site-subsection"><div class="site-subsection-heading"><span class="site-section-kicker">Identidade</span><h2 class="site-subsection-title page-title"><?= e($settings['vest_title']) ?></h2></div><div class="site-copy"><?= paragraphs_html($settings['vest_text']) ?></div></div>
        <div class="site-subsection site-subsection-elements"><div class="site-subsection-heading"><span class="site-section-kicker">Nossa marca</span><h2 class="site-subsection-title page-title"><?= e($settings['elements_title']) ?></h2></div><div class="site-elements-grid"><?php $elementLines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string)$settings['elements_text']) ?: []), static fn(string $line): bool => $line !== '')); for ($column = 0; $column < 2; $column++): ?><ul><?php foreach ($elementLines as $index => $elementLine): if ($index % 2 !== $column) { continue; } ?><li><?= e($elementLine) ?></li><?php endforeach; ?></ul><?php endfor; ?></div></div>
    </div>
</section>

<section id="misao" class="site-section site-section-dark">
    <div class="container site-container">
        <header class="site-section-heading"><span class="site-section-kicker">Nosso propósito</span><h1 class="site-section-title page-title"><?= e($settings['mission_title']) ?></h1></header>
        <div class="site-copy"><?= paragraphs_html($settings['mission_text']) ?></div>
    </div>
</section>

<section id="diretoria" class="site-section site-section-light">
    <div class="container site-container"><header class="site-section-heading"><span class="site-section-kicker">Quem somos</span><h1 class="site-section-title page-title"><?= e($settings['board_title']) ?></h1></header>
    <div class="board-grid">
        <?php $members = \App\Repositories\ContentRepository::activeDirectoria(); if (!$members): ?>
            <div class="col-12 text-center"><p class="lead"><?= e($settings['board_empty_message']) ?></p></div>
        <?php else: foreach ($members as $member): ?>
            <article class="card board-card shadow-sm border-0">
                <div class="board-card-media"><?php if (!empty($member['foto'])): ?><img src="<?= e(DIRETORIA_WEB . '/' . rawurlencode((string)$member['foto'])) ?>" alt="<?= e($member['nome']) ?>" loading="lazy" decoding="async"><?php else: ?><i class="fa-solid fa-user-tie" aria-hidden="true"></i><?php endif; ?></div>
                <div class="card-body board-card-body"><span class="board-card-role"><?= e($member['cargo']) ?></span><h2 class="board-card-name page-title"><?= e($member['nome']) ?></h2><?php if ($member['bio']): ?><p class="board-card-bio"><?= nl2br(e($member['bio'])) ?></p><?php endif; ?></div>
            </article>
        <?php endforeach; endif; ?>
    </div>
    </div>
</section>

<section id="colaboradores" class="site-section site-section-dark">
    <div class="container site-container"><header class="site-section-heading"><span class="site-section-kicker">Parcerias</span><h1 class="site-section-title page-title">Colaboradores</h1></header>
    <div class="collaborators-grid">
        <?php if (!$collaboradores): ?>
            <div class="col-12 text-center"><p class="lead">Em breve apresentaremos nossos colaboradores.</p></div>
        <?php else: foreach ($collaboradores as $collaborador): ?>
            <?php $whatsappDigits = preg_replace('/\D+/', '', (string)$collaborador['whatsapp']); ?>
            <?php $collaboradorPdfUrl = !empty($collaborador['pdf']) ? COLABORADORES_PDF_WEB . '/' . rawurlencode((string)$collaborador['pdf']) : ''; ?>
            <article class="card collaborator-card shadow-sm border-0">
                <?php if (!empty($collaborador['foto'])): ?>
                    <div class="collaborator-card-media"><img class="card-img-top" src="<?= e(COLABORADORES_WEB . '/' . rawurlencode((string)$collaborador['foto'])) ?>" alt="<?= e($collaborador['nome']) ?>" loading="lazy" decoding="async"></div>
                <?php elseif ($collaboradorPdfUrl !== ''): ?>
                    <div class="collaborator-card-placeholder collaborator-card-pdf-placeholder" aria-label="PDF interativo disponível"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i><span>PDF interativo</span></div>
                <?php else: ?>
                    <div class="collaborator-card-placeholder" aria-hidden="true"><i class="fa-solid fa-handshake"></i></div>
                <?php endif; ?>
                <div class="card-body text-center d-flex flex-column">
                    <h5 class="card-title text-white page-title mb-2"><?= e($collaborador['nome']) ?></h5>
                    <?php if ($collaborador['descricao'] !== ''): ?><p class="card-text small text-white mb-3"><?= nl2br(e($collaborador['descricao'])) ?></p><?php endif; ?>
                    <?php if ($collaboradorPdfUrl !== ''): ?>
                        <div class="collaborator-pdf-card">
                            <div class="collaborator-pdf-heading"><i class="fa-regular fa-file-pdf" aria-hidden="true"></i><span>Apresentação em PDF</span></div>
                            <p>Consulte a apresentação interativa deste colaborador.</p>
                            <button class="collaborator-pdf-link" type="button" data-bs-toggle="modal" data-bs-target="#collaborator-pdf-modal" data-pdf-url="<?= e($collaboradorPdfUrl) ?>" data-pdf-title="<?= e($collaborador['nome']) ?>"><i class="fa-solid fa-expand" aria-hidden="true"></i> Visualizar PDF</button>
                        </div>
                    <?php endif; ?>
                    <div class="collaborator-links mt-auto">
                        <?php if ($collaborador['site_url'] !== ''): ?><a class="btn btn-sm btn-outline-light" href="<?= e(external_url($collaborador['site_url'])) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-globe" aria-hidden="true"></i> Site</a><?php endif; ?>
                        <?php if ($collaborador['social_url'] !== ''): ?><a class="btn btn-sm btn-outline-light" href="<?= e(external_url($collaborador['social_url'])) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram" aria-hidden="true"></i> Rede social</a><?php endif; ?>
                        <?php if ($whatsappDigits !== ''): ?><a class="btn btn-sm btn-success" href="https://wa.me/<?= e($whatsappDigits) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a><?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; endif; ?>
    </div>
    </div>
</section>

<div class="modal fade collaborator-pdf-modal" id="collaborator-pdf-modal" tabindex="-1" aria-labelledby="collaborator-pdf-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="site-gallery-modal-counter">DOCUMENTO INTERATIVO</span>
                    <h2 class="modal-title page-title" id="collaborator-pdf-modal-title">Apresentação</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar PDF"></button>
            </div>
            <div class="modal-body collaborator-pdf-modal-body">
                <iframe id="collaborator-pdf-modal-frame" title="PDF do colaborador" loading="lazy"></iframe>
            </div>
            <div class="modal-footer collaborator-pdf-modal-footer">
                <a id="collaborator-pdf-modal-open" class="btn btn-primary" href="#" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Abrir em nova aba</a>
            </div>
        </div>
    </div>
</div>

<section id="eventos" class="site-section site-section-light">
    <div class="container site-container"><header class="site-section-heading"><span class="site-section-kicker">Agenda</span><h1 class="site-section-title page-title"><?= e($settings['events_title']) ?></h1></header>
    <div class="events-grid">
        <?php $events = \App\Repositories\ContentRepository::activeEventos(); if (!$events): ?>
            <div class="col-12 text-center"><p class="lead"><?= e($settings['events_empty_message']) ?></p></div>
        <?php else: foreach ($events as $event): ?><article class="card event-card h-100 shadow-sm border-0">
            <?php if ($event['banner']): ?><div class="event-card-media"><img class="card-img-top" src="<?= e(EVENTOS_WEB . '/' . rawurlencode((string)$event['banner'])) ?>" alt="<?= e($event['titulo']) ?>" loading="lazy" decoding="async"></div><?php endif; ?>
            <div class="card-body event-card-body"><h2 class="event-card-title page-title"><?= e($event['titulo']) ?></h2><div class="event-card-meta"><span><i class="fa-regular fa-calendar-days" aria-hidden="true"></i> <?php $start = new DateTime($event['data_inicio']); echo e($start->format('d/m/Y H:i')); if ($event['data_fim']) { $end = new DateTime($event['data_fim']); echo ' → ' . e($end->format('d/m/Y H:i')); } ?></span><?php if ($event['local']): ?><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= e($event['local']) ?></span><?php endif; ?></div><?php if ($event['descricao']): ?><p class="event-card-description"><?= nl2br(e($event['descricao'])) ?></p><?php endif; ?></div>
        </article><?php endforeach; endif; ?>
    </div>
    </div>
</section>

<section id="galeria" class="site-section site-section-dark">
    <div class="container site-container"><header class="site-section-heading"><span class="site-section-kicker">Momentos</span><h1 class="site-section-title page-title"><?= e($settings['gallery_title']) ?></h1></header>
    <div class="gallery clearfix"><?php foreach (random_gallery_items(30) as $galleryItem): ?><button class="gallery-item" type="button" data-gallery-image="<?= e(GALLERY_WEB . '/' . rawurlencode($galleryItem['file'])) ?>" data-gallery-description="<?= e($galleryItem['description']) ?>" aria-label="Abrir imagem: <?= e($galleryItem['description']) ?>" data-bs-toggle="modal" data-bs-target="#site-gallery-modal"><img class="img-fluid mx-auto d-block" src="<?= e(GALLERY_WEB . '/' . rawurlencode($galleryItem['file'])) ?>" alt="<?= e($galleryItem['description']) ?>" loading="lazy" decoding="async"></button><?php endforeach; ?></div>
    <div class="text-center site-section-action"><a href="<?= e($instagramUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info page-title"><i class="fa-brands fa-instagram" aria-hidden="true"></i> <?= e($settings['gallery_button_text']) ?></a></div>
    </div>
</section>
</main>

<div class="modal fade site-gallery-modal" id="site-gallery-modal" tabindex="-1" aria-labelledby="site-gallery-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="site-gallery-modal-counter" id="site-gallery-modal-counter">Imagem 1 de 1</span>
                    <h2 class="modal-title page-title" id="site-gallery-modal-title">Galeria</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar galeria"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="site-gallery-modal-nav site-gallery-modal-prev" id="site-gallery-prev" aria-label="Imagem anterior"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                <figure class="site-gallery-modal-figure">
                    <img id="site-gallery-modal-image" src="" alt="">
                    <figcaption id="site-gallery-modal-description"></figcaption>
                </figure>
                <button type="button" class="site-gallery-modal-nav site-gallery-modal-next" id="site-gallery-next" aria-label="Próxima imagem"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
</div>

<footer id="rodape" class="site-footer"><div class="container site-container"><img src="<?= e(site_media_url($settings['logo_path'])) ?>" alt="<?= e($settings['logo_alt']) ?>" class="img-fluid d-block site-footer-logo" width="100" loading="lazy"><p class="site-footer-text page-title"><?= e($settings['footer_text']) ?></p><a class="site-footer-top scrollto" href="#inicio"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i> Voltar ao topo</a></div></footer>

<?php if ($settings['audio_enabled'] === '1' && $settings['audio_path'] !== ''): ?>
<div class="audio-widget" id="bg-audio-widget" aria-live="polite"><div class="audio-consent" id="audio-consent" role="dialog" aria-labelledby="audio-consent-title"><strong id="audio-consent-title">Ativar música de fundo?</strong><span>O site possui uma trilha sonora opcional.</span><div class="audio-consent-actions"><button id="audio-consent-allow" class="btn btn-sm btn-primary" type="button"><i class="fa-solid fa-volume-high" aria-hidden="true"></i> Ativar música</button><button id="audio-consent-deny" class="btn btn-sm btn-outline-light" type="button">Agora não</button></div></div><div class="audio-card"><div class="audio-controls"><button id="audio-toggle" class="btn btn-sm btn-light audio-toggle" type="button" aria-label="Tocar música"><i class="fa-solid fa-play" id="audio-icon" aria-hidden="true"></i></button><input type="range" min="0" max="1" step="0.01" id="audio-volume" value="<?= e($settings['audio_volume']) ?>" aria-label="Volume"><button id="audio-close" class="btn btn-sm btn-outline-light" type="button" title="Desativar música" aria-label="Desativar música">×</button></div></div></div>
<audio id="bg-music" src="<?= e(site_media_url($settings['audio_path'])) ?>" preload="auto" loop playsinline></audio>
<?php endif; ?>
<script src="assets/js/jquery.min.js"></script><script src="assets/js/bootstrap.bundle.min.js"></script><script src="assets/js/fontawesome.min.js" data-auto-replace-svg="false"></script><script src="assets/js/gallery.js?v=1"></script><script src="assets/js/collaborator-pdf.js?v=1"></script><script src="assets/js/collaborator-float.js?v=1"></script><?php if ($settings['audio_enabled'] === '1' && $settings['audio_path'] !== ''): ?><script src="assets/js/audio.js?v=2"></script><?php endif; ?><script src="assets/js/application.js"></script>
</body>
</html>
