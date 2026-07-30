<?php
declare(strict_types=1);

namespace App\Repositories;

final class SettingsRepository
{
    public static function defaults(): array
    {
        return [
            'site_name' => 'Barões MC',
            'site_title' => 'Barões MC — Petrópolis',
            'site_description' => 'Barões MC: irmandade, liberdade e cultura motociclista em Petrópolis-RJ.',
            'og_description' => 'Irmandade, liberdade e cultura motociclista.',
            'logo_path' => 'logo.png',
            'logo_alt' => 'Logo Barões MC',
            'banner_path' => 'header.jpg',
            'banner_alt' => 'Barões MC',
            'favicon_path' => 'favicon.ico',
            'favicon_mime' => 'image/x-icon',
            'audio_path' => 'theme.mp3',
            'audio_enabled' => '1',
            'audio_volume' => '0.2',
            'instagram_url' => 'https://www.instagram.com/baroes.mc/',
            'nav_home_label' => 'INÍCIO',
            'nav_about_label' => 'O MOTO CLUBE',
            'nav_mission_label' => 'MISSÃO',
            'nav_board_label' => 'DIRETORIA',
            'nav_events_label' => 'EVENTOS',
            'nav_gallery_label' => 'GALERIA',
            'about_title' => 'O MOTO CLUBE',
            'about_text' => "A palavra Barão provém do termo franco baro, que significa “homem livre”. Seguindo esse legado de liberdade, evocamos a figura do nobre Barão de Mauá, personalidade que, residindo em nossa Cidade Imperial, foi decisiva para o desenvolvimento nacional e continental. Inspirados por esse espírito de independência e progresso, trazemos esse ideal para o universo motociclista.\n\nNosso propósito é impulsionar o mundo biker em direção a menos centralização de poder, maior alternância e ampla difusão da cultura motociclista, sempre pautados no respeito verdadeiro às opiniões divergentes — sejam elas religiosas, políticas, sexuais ou de qualquer outra natureza.\n\nE para que jamais percamos a virtude, a honra e a honestidade que nos sustentam, aos dezessete dias do mês de abril de dois mil e vinte e quatro, no Município de Petrópolis, estado do Rio de Janeiro, foi fundado o BARÕES MC.",
            'vest_title' => 'O COLETE',
            'vest_text' => "O colete, representação máxima do motoclube, traz consigo toda a história do clube.\n\nNas costas, o brasão; na frente, a vírgula B.S.S.B. — representando Barões Sempre, Sempre Barões — e o lema Irmandade Incontestável.",
            'elements_title' => 'ELEMENTOS E SIGNIFICADOS',
            'elements_text' => "17 MC — alusão à data de fundação do motoclube na Cidade Imperial, Petrópolis-RJ.\nTarjeta — com o nome do integrante e a cidade da Divisão.\nPins nos ombros — um faz alusão à Estrada Real e o outro ao brasão BARÕES MC.\nParte inferior (cartas de baralho) — Ás representa o Presidente e o Vice-Presidente; Rei representa os cargos do MC; Valete representa os demais membros; Dama de Ouro é usada nos coletes das Baronesas.\nBarra do colete dos fundadores — Coroa Imperial, símbolo marcante da monarquia na cidade de Petrópolis-RJ.",
            'mission_title' => 'NOSSA MISSÃO',
            'mission_text' => "Nossa missão é preservar e difundir o verdadeiro espírito de liberdade que inspira a cultura motociclista. Buscamos fortalecer a irmandade biker promovendo respeito, união e diversidade, valorizando opiniões e escolhas diferentes — sejam elas religiosas, políticas, culturais ou pessoais.\n\nGuiados pelos ideais de independência e progresso que marcaram a trajetória do Barão de Mauá, queremos impulsionar o motociclismo em direção a um futuro menos centralizado, mais justo e colaborativo, mantendo sempre a honestidade, a honra e a virtude como fundamentos inegociáveis do BARÕES MC.",
            'board_title' => 'NOSSA DIRETORIA',
            'board_empty_message' => 'Em breve divulgaremos a diretoria atualizada.',
            'events_title' => 'NOSSOS EVENTOS',
            'events_empty_message' => 'Nenhum evento encontrado.',
            'gallery_title' => 'GALERIA',
            'gallery_button_text' => 'Seguir no Instagram',
            'footer_text' => 'Barões MC',
        ];
    }

    public static function all(): array
    {
        $values = self::defaults();
        foreach (db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
            $values[(string)$row['setting_key']] = (string)$row['setting_value'];
        }
        return $values;
    }

    public static function get(string $key): string
    {
        $defaults = self::defaults();
        $stmt = db()->prepare('SELECT setting_value FROM site_settings WHERE setting_key=?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value === false ? (string)($defaults[$key] ?? '') : (string)$value;
    }

    public static function setMany(array $values): void
    {
        $stmt = db()->prepare('INSERT INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value, updated_at=excluded.updated_at');
        foreach ($values as $key => $value) {
            $stmt->execute([(string)$key, (string)$value, date('c')]);
        }
    }

    public static function seedDefaults(): void
    {
        $stmt = db()->prepare('INSERT OR IGNORE INTO site_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)');
        foreach (self::defaults() as $key => $value) {
            $stmt->execute([$key, $value, date('c')]);
        }
    }
}
