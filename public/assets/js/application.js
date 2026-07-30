let application;
$(function () {
    application = {
        webRoot: '',
        body: $('body'),
        isMobile: false,
        windowWidth: 0,
        windowHeight: 0,
        documentWidth: 0,
        documentHeight: 0,
        init: function () {
            const isMobile = (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));
            if (isMobile) {
                $('body').addClass('isMobile');
                application.isMobile = true;
            }
            application.setEvents();
        },
        onReady: function () {
            application.setTheme();
            application.setMasks();
            application.checkHash();
        },
        onLoad: function () {
            application.windowWidth = $(window).width();
            application.windowHeight = $(window).height();
            application.documentWidth = $(document).width();
            application.documentHeight = $(document).height();
        },
        onResize: function () {
            application.windowWidth = $(window).width();
            application.windowHeight = $(window).height();
            application.documentWidth = $(document).width();
            application.documentHeight = $(document).height();
        },
        setEvents: function () {
            //- ------------------------------------------------------------------------------------------
            $(document).ready(application.onReady);
            $(window).on('load', application.onLoad);
            $(window).on('resize', application.onResize);
            //- ------------------------------------------------------------------------------------------

            //- ------------------------------------------------------------------------------------------
            $('a[href="#"]').on('click', function (e) {
                e.preventDefault();
            });
            //- ------------------------------------------------------------------------------------------

            //- ------------------------------------------------------------------------------------------
            //- Navibar View
            $(window).on('scroll', function () {
                if(!$('#header').hasClass('dontChange'))
                    if ($(this).scrollTop() > 10) {
                        $('#header').removeClass('navbar-transparent');
                        $('#header').removeClass('shadow-none');
                        $('#header').addClass('bg-index');
                        $('#header').addClass('shadow');
                        $('#header').addClass('blur');
                    } else {
                        $('#header').addClass('navbar-transparent');
                        $('#header').addClass('shadow-none');
                        $('#header').removeClass('bg-index');
                        $('#header').removeClass('shadow');
                        $('#header').removeClass('blur');
                    }
            });
            //- ------------------------------------------------------------------------------------------

            //- ------------------------------------------------------------------------------------------
            //- Navbar links active state on scroll
            $(window).on('scroll', function () {
                let position = window.scrollY + 300;
                let navbarLinks = $(".navbar .scrollto");

                navbarLinks.each(function () {
                    let link = $(this);
                    let hash = this.hash.replace('/', '');

                    if (hash !== '#' && hash && $(hash).length) {
                        let sectionOffsetTop = $(hash).offset().top;
                        let sectionHeight = $(hash).outerHeight();

                        if (position >= sectionOffsetTop && position <= (sectionOffsetTop + sectionHeight)) {
                            link.addClass('active');
                        } else {
                            link.removeClass('active');
                        }
                    }
                });
            });
            //- ------------------------------------------------------------------------------------------

            //- ------------------------------------------------------------------------------------------
            //- Scrool with ofset on links with a class name .scrollto
            $('.scrollto').on('click.smoothscroll', function (e) {
                let hash = this.hash.replace('/', '')
                if (hash && $(hash).length) {
                    e.preventDefault();
                    application.trailPage(hash)
                }
            });
            //- ------------------------------------------------------------------------------------------
        },
        setTheme: function () {
            if (window.bootstrap) {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
                    new bootstrap.Tooltip(element);
                });
                document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function (element) {
                    new bootstrap.Popover(element);
                });
            }

        },
        setMasks: function () {
            document.querySelectorAll('input[name="whatsapp"]').forEach(function (input) {
                input.setAttribute('inputmode', 'tel');
                input.setAttribute('autocomplete', 'tel');
                input.setAttribute('maxlength', '19');
                input.setAttribute('placeholder', '+55 (24) 99999-9999');

                const updateWhatsapp = function () {
                    let digits = (input.value || '').replace(/\D/g, '');
                    if (digits.indexOf('55') === 0) {
                        digits = digits.slice(2);
                    }
                    if (digits.length === 11 && digits.indexOf('0') === 0) {
                        digits = digits.slice(1);
                    }
                    digits = digits.slice(0, 11);

                    if (digits === '') {
                        input.value = '';
                        input.setCustomValidity('');
                        input.classList.remove('is-invalid');
                        return;
                    }

                    const ddd = digits.slice(0, 2);
                    const phone = digits.slice(2);
                    let formatted = '+55 (' + ddd;
                    if (digits.length >= 2) {
                        formatted += ')';
                    }
                    if (phone !== '') {
                        formatted += ' ';
                        formatted += phone.length > 8
                            ? phone.slice(0, 5) + '-' + phone.slice(5)
                            : phone.length > 4
                                ? phone.slice(0, 4) + '-' + phone.slice(4)
                                : phone;
                    }

                    input.value = formatted;
                    const valid = digits.length === 10 || digits.length === 11;
                    input.setCustomValidity(valid ? '' : 'Informe +55, o DDD e um telefone com 8 ou 9 dígitos.');
                    input.classList.toggle('is-invalid', !valid);
                };

                input.addEventListener('input', updateWhatsapp);
                input.addEventListener('blur', updateWhatsapp);
                input.closest('form')?.addEventListener('submit', updateWhatsapp);
                updateWhatsapp();
            });
        },
        checkHash: function () {
            let hash = window.location.hash;
            if (hash !== '') {
                application.trailPage(hash);
            }
        },
        trailPage: function (hash) {
            if (hash && hash.length) {
                hash = hash.replace('/', '');
                if ($(hash).length > 0) {
                    let offset = $('#header').outerHeight();
                    if (!$('#header').hasClass('header-scrolled')) {
                        offset -= 20
                    }

                    $('html, body').stop().animate({
                        scrollTop : parseFloat($(hash).offset().top - offset)
                    }, 900, 'swing');
                }
            }
        },
    };
    application.init();
});
