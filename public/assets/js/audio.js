// /assets/js/audio.js
(function () {
    'use strict';

    // Aguarda DOM pronto mesmo que jQuery ainda não esteja disponível
    function domReady(fn) {
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(fn, 0);
        } else {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
        }
    }

    domReady(function () {
        // Se jQuery não carregou ainda, aborta silenciosamente
        if (typeof window.jQuery === 'undefined') {
            console.warn('[audio.js] jQuery não encontrado. Garanta que jQuery é carregado ANTES de /assets/js/audio.js');
            return;
        }
        var $ = window.jQuery;

        var audio   = document.getElementById('bg-music');
        var $toggle = $('#audio-toggle');
        var $icon   = $('#audio-icon');
        var $widget = $('#bg-audio-widget');
        var $vol    = $('#audio-volume');
        var $consent = $('#audio-consent');
        var $consentAllow = $('#audio-consent-allow');
        var $consentDeny = $('#audio-consent-deny');
        var userPaused = false;

        if (!audio || !$toggle.length || !$icon.length) {
            console.warn('[audio.js] Elementos do player não encontrados. Verifique IDs: bg-music, audio-toggle, audio-icon.');
            return;
        }

        // — Volume inicial (com fallback)
        var savedVol = parseFloat(localStorage.getItem('bgMusicVolume') || '0.2');
        if (isNaN(savedVol)) savedVol = 0.2;
        audio.volume = Math.max(0, Math.min(1, savedVol));
        if ($vol.length) $vol.val(audio.volume);

        // — UI helpers
        function setPlayingUI(playing) {
            $toggle.toggleClass('playing', !!playing)
                .attr('aria-pressed', !!playing);
            // Troca apenas o ícone específico; o "style" base (fas/fa-solid) deve vir do HTML
            $icon.toggleClass('fa-play', !playing)
                .toggleClass('fa-pause', !!playing);
            $toggle.attr('aria-label', playing ? 'Pausar música' : 'Tocar música');
        }

        // Mantém UI sincronizada mesmo se o estado mudar fora do botão
        audio.addEventListener('play',  function(){ setPlayingUI(true);  });
        audio.addEventListener('pause', function(){ setPlayingUI(false); });
        audio.addEventListener('ended', function(){ setPlayingUI(false); });
        audio.addEventListener('error', function(e){
            console.warn('[audio.js] Erro no áudio:', e);
            setPlayingUI(false);
        });

        function hideConsent() {
            if ($consent.length) {
                $consent.attr('hidden', 'hidden').hide();
            }
        }

        function playFromUserGesture() {
            userPaused = false;
            audio.autoplay = false;
            audio.muted = false;
            return audio.play().then(function () {
                setPlayingUI(true);
            }).catch(function () {
                setPlayingUI(false);
            });
        }

        // A reprodução só começa depois de uma autorização explícita.
        $consentAllow.on('click', function () {
            hideConsent();
            playFromUserGesture();
        });

        $consentDeny.on('click', function () {
            userPaused = true;
            audio.autoplay = false;
            audio.pause();
            hideConsent();
            setPlayingUI(false);
        });

        // — Botão play/pause
        $toggle.on('click', function () {
            if (audio.paused) {
                playFromUserGesture();
            } else {
                userPaused = true;
                audio.autoplay = false;
                audio.pause();
                setPlayingUI(false);
            }
        });

        // — Slider de volume (se existir)
        if ($vol.length) {
            $vol.on('input change', function () {
                var v = parseFloat(this.value);
                if (isNaN(v)) v = 0.2;
                audio.volume = Math.max(0, Math.min(1, v));
                localStorage.setItem('bgMusicVolume', String(audio.volume));
            });
        }

        // — Botão fechar (se existir)
        var $close = $('#audio-close');
        if ($close.length) {
            $close.on('click', function () {
                userPaused = true;
                audio.autoplay = false;
                audio.pause();
                setPlayingUI(false);
                $widget.length && $widget.fadeOut(120);
            });
        }

        // Ajusta UI inicial conforme estado
        setPlayingUI(false);
    });
})();
